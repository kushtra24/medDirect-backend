<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ImportPatientsRequest;
use App\Http\Requests\Api\StorePatientRequest;
use App\Http\Resources\PatientResource;
use App\Models\Organization;
use App\Models\Patient;
use App\Services\PatientClaimService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PatientController extends Controller
{
    /**
     * Maximum number of data rows accepted in one CSV import.
     */
    private const MAX_IMPORT_ROWS = 500;

    public function __construct(
        private readonly PatientClaimService $claimService,
    ) {}

    /**
     * Maps a normalized CSV header cell to the canonical patient field it represents.
     *
     * @var array<string, list<string>>
     */
    private const COLUMN_ALIASES = [
        'first_name' => ['first_name', 'firstname', 'name', 'given_name'],
        'surname' => ['surname', 'last_name', 'lastname', 'family_name'],
        'date_of_birth' => ['date_of_birth', 'dob', 'birth_date', 'birthdate'],
        'gender' => ['gender', 'sex'],
        'national_id' => ['national_id', 'nationalid', 'passport', 'national_id_passport'],
        'insurance_provider' => ['insurance_provider', 'insurance'],
        'policy_number' => ['policy_number', 'policy'],
        'blood_group' => ['blood_group', 'bloodgroup', 'blood_type'],
        'allergies' => ['allergies'],
        'chronic_conditions' => ['chronic_conditions', 'chronic'],
        'phone' => ['phone', 'phone_number', 'mobile'],
        'email' => ['email', 'email_address'],
    ];

    public function index(Request $request, Organization $organization): JsonResponse
    {
        $query = Patient::query()
            ->where('organization_id', $organization->id)
            ->with([
                'organization',
                'labResultReports' => fn ($q) => $q
                    ->with(['items', 'createdBy'])
                    ->orderByDesc('reported_at')
                    ->orderByDesc('id')
                    ->limit(20),
                'examinationResultReports' => fn ($q) => $q
                    ->with(['items', 'createdBy'])
                    ->orderByDesc('reported_at')
                    ->orderByDesc('id')
                    ->limit(20),
            ]);

        if ($request->filled('first_name')) {
            $query->where('first_name', 'like', '%'.$request->string('first_name')->trim().'%');
        }

        if ($request->filled('surname')) {
            $query->where('surname', 'like', '%'.$request->string('surname')->trim().'%');
        }

        if ($request->filled('date_of_birth')) {
            $query->whereDate('date_of_birth', $request->string('date_of_birth'));
        }

        if ($request->filled('gender')) {
            $query->where('gender', $request->string('gender'));
        }

        $patients = $query
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json([
            'patients' => PatientResource::collection($patients),
        ]);
    }

    public function store(StorePatientRequest $request, Organization $organization): JsonResponse
    {
        $data = $request->validated();
        $pin = (string) random_int(100000, 999999);

        $patient = Patient::query()->create([
            ...$data,
            ...$this->linkedAccountFields($data),
            'organization_id' => $organization->id,
            'created_by_user_id' => $request->user()->id,
            'access_pin' => $pin,
        ]);

        $patient->load('organization');

        return response()->json([
            'patient' => new PatientResource($patient),
            'access_pin' => $pin,
            'message' => 'Patient registered successfully.',
        ], 201);
    }

    /**
     * Preview whether the identity being typed into the "add patient" form already has a
     * patient-portal account, so the clinic staff can autofill from it before saving.
     * Does not create or modify anything.
     */
    public function matchUser(Request $request, Organization $organization): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['nullable', 'string', 'max:120'],
            'surname' => ['nullable', 'string', 'max:120'],
            'date_of_birth' => ['nullable', 'date'],
            'national_id' => ['nullable', 'string', 'max:64'],
        ]);

        $user = $this->claimService->findExactMatchUser($validated);

        if ($user === null) {
            return response()->json(['matched' => false]);
        }

        $existingPatientId = Patient::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->value('id');

        [$firstName, $surname] = $this->splitName($user->name);

        return response()->json([
            'matched' => true,
            'user_id' => $user->id,
            'already_registered_patient_id' => $existingPatientId,
            'suggested' => [
                'first_name' => $firstName,
                'surname' => $surname,
                'date_of_birth' => $user->date_of_birth?->format('Y-m-d'),
                'national_id' => $user->national_id,
                'phone' => $user->phone,
                'email' => $user->email,
                'blood_group' => $user->blood_type,
            ],
        ]);
    }

    /**
     * Best-effort split of a portal account's single `name` field into first/surname,
     * matching how {@see \App\Http\Controllers\Api\AuthController::register} joins them.
     *
     * @return array{0: string, 1: string}
     */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2) ?: [];

        return [$parts[0] ?? '', $parts[1] ?? ''];
    }

    /**
     * If the patient being created/imported matches an existing patient-portal account
     * (same national ID, name, and date of birth), link it via `user_id` immediately
     * instead of waiting for the "claim" flow.
     *
     * @param  array<string, mixed>  $data
     * @return array{user_id?: int, claimed_at?: \Illuminate\Support\Carbon}
     */
    private function linkedAccountFields(array $data): array
    {
        $user = $this->claimService->findExactMatchUser($data);

        if ($user === null) {
            return [];
        }

        return [
            'user_id' => $user->id,
            'claimed_at' => now(),
        ];
    }

    public function import(ImportPatientsRequest $request, Organization $organization): JsonResponse
    {
        $parsed = $this->parseCsv($request->file('file')->getRealPath());

        if ($parsed === null) {
            return response()->json([
                'message' => 'The uploaded file could not be read as CSV.',
            ], 422);
        }

        [$header, $dataRows] = $parsed;
        $columnMap = $this->mapColumns($header);

        if (! isset($columnMap['first_name'], $columnMap['surname'])) {
            return response()->json([
                'message' => 'The CSV must include "first_name" and "surname" columns.',
            ], 422);
        }

        if (count($dataRows) > self::MAX_IMPORT_ROWS) {
            return response()->json([
                'message' => 'The CSV cannot contain more than '.self::MAX_IMPORT_ROWS.' patient rows.',
            ], 422);
        }

        $imported = 0;
        $errors = [];

        foreach ($dataRows as $index => $row) {
            $data = $this->extractRowData($row, $columnMap);

            if ($this->isBlankRow($data)) {
                continue;
            }

            $validator = Validator::make($data, StorePatientRequest::baseRules());

            if ($validator->fails()) {
                $errors[] = [
                    // +2 accounts for the header row and 1-based row numbering.
                    'row' => $index + 2,
                    'messages' => $validator->errors()->all(),
                ];

                continue;
            }

            $rowData = $validator->validated();
            Patient::query()->create([
                ...$rowData,
                ...$this->linkedAccountFields($rowData),
                'organization_id' => $organization->id,
                'created_by_user_id' => $request->user()->id,
                'access_pin' => (string) random_int(100000, 999999),
            ]);
            $imported++;
        }

        return response()->json([
            'imported' => $imported,
            'skipped' => count($errors),
            'total' => count($dataRows),
            'errors' => array_slice($errors, 0, 50),
        ]);
    }

    /**
     * @return array{0: list<string>, 1: list<list<string|null>>}|null
     */
    private function parseCsv(string $path): ?array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return null;
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);

            return null;
        }

        $rows = [];
        while (($line = fgetcsv($handle)) !== false) {
            $rows[] = $line;
        }
        fclose($handle);

        return [$header, $rows];
    }

    /**
     * @param  list<string>  $header
     * @return array<string, int>
     */
    private function mapColumns(array $header): array
    {
        $map = [];

        foreach ($header as $index => $rawLabel) {
            $key = strtolower(trim((string) $rawLabel));
            $key = trim((string) preg_replace('/[^a-z0-9]+/', '_', $key), '_');

            foreach (self::COLUMN_ALIASES as $field => $aliases) {
                if (in_array($key, $aliases, true)) {
                    $map[$field] = $index;

                    break;
                }
            }
        }

        return $map;
    }

    /**
     * @param  list<string|null>  $row
     * @param  array<string, int>  $columnMap
     * @return array<string, string|null>
     */
    private function extractRowData(array $row, array $columnMap): array
    {
        $data = [];

        foreach ($columnMap as $field => $index) {
            $value = trim((string) ($row[$index] ?? ''));
            $data[$field] = $value === '' ? null : $value;
        }

        if (isset($data['gender'])) {
            $data['gender'] = strtolower($data['gender']);
        }

        return $data;
    }

    /**
     * @param  array<string, string|null>  $data
     */
    private function isBlankRow(array $data): bool
    {
        return $data === array_filter($data, fn ($value) => $value === null);
    }

    public function show(Request $request, Organization $organization, Patient $patient): JsonResponse
    {
        if ($patient->organization_id !== $organization->id) {
            abort(404);
        }

        $patient->load([
            'organization',
            'labResultReports' => fn ($q) => $q
                ->with(['items', 'createdBy'])
                ->orderByDesc('reported_at')
                ->orderByDesc('id'),
            'examinationResultReports' => fn ($q) => $q
                ->with(['items', 'createdBy'])
                ->orderByDesc('reported_at')
                ->orderByDesc('id'),
        ]);

        return response()->json([
            'patient' => new PatientResource($patient),
        ]);
    }
}
