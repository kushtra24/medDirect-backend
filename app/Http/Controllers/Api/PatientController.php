<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StorePatientRequest;
use App\Http\Resources\PatientResource;
use App\Models\Organization;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientController extends Controller
{
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

    public function show(Request $request, Organization $organization, Patient $patient): JsonResponse
    {
        if ($patient->organization_id !== $organization->id) {
            abort(404);
        }

        $patient->load(['organization', 'labResultReports.items']);

        return response()->json([
            'patient' => new PatientResource($patient),
            'lab_result_reports' => $patient->labResultReports->load(['items', 'organization']),
        ]);
    }
}
