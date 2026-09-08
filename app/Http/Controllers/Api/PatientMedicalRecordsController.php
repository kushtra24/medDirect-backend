<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExaminationResultReportResource;
use App\Http\Resources\LabResultReportResource;
use App\Models\ExaminationResultReport;
use App\Models\LabResultReport;
use App\Models\User;
use App\Services\PatientClaimService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientMedicalRecordsController extends Controller
{
    public function __construct(
        private readonly PatientClaimService $claimService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->isPatient()) {
            abort(403, 'Only patient accounts can view medical records here.');
        }

        $reports = $this->reportsForUser($user);

        return response()->json([
            'reports' => LabResultReportResource::collection($reports),
        ]);
    }

    public function claim(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->isPatient()) {
            abort(403, 'Only patient accounts can claim records.');
        }

        $claimed = $this->claimService->claimForUser($user);
        $reports = $this->reportsForUser($user->fresh());

        return response()->json([
            'claimed_count' => $claimed->count(),
            'reports' => LabResultReportResource::collection($reports),
        ]);
    }

    /**
     * Claim the patient record a logged-in patient just found via the guest name+PIN lookup, so
     * it shows on their profile permanently. Re-verifies the name+PIN (rather than trusting a
     * patient_id) so a signed-in user can't claim an arbitrary record by guessing IDs.
     */
    public function claimSearched(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->isPatient()) {
            abort(403, 'Only patient accounts can claim records.');
        }

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'access_pin' => ['required', 'string', 'size:6'],
        ]);

        $patient = $this->claimService->findByNameAndPin(
            $validated['full_name'],
            $validated['access_pin'],
        );

        if ($patient === null) {
            return response()->json([
                'message' => 'No matching patient record found.',
            ], 404);
        }

        if ($patient->user_id !== null && $patient->user_id !== $user->id) {
            return response()->json([
                'message' => 'These results have already been claimed by another account.',
            ], 409);
        }

        if ($patient->user_id === null) {
            $patient->user_id = $user->id;
            $patient->claimed_at = now();
            $patient->save();
        }

        return response()->json([
            'claimed' => true,
            'patient' => [
                'id' => $patient->id,
                'is_claimed' => true,
            ],
        ]);
    }

    public function lookup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'access_pin' => ['required', 'string', 'size:6'],
        ]);

        $patient = $this->claimService->findByNameAndPin(
            $validated['full_name'],
            $validated['access_pin'],
        );

        if ($patient === null) {
            return response()->json([
                'message' => 'No matching patient record found.',
            ], 404);
        }

        $reports = LabResultReport::query()
            ->where('patient_id', $patient->id)
            ->where('status', LabResultReport::STATUS_FINALIZED)
            ->with(['items', 'organization', 'patient'])
            ->orderByDesc('reported_at')
            ->get();

        $examinationReports = ExaminationResultReport::query()
            ->where('patient_id', $patient->id)
            ->where('status', ExaminationResultReport::STATUS_FINALIZED)
            ->with(['items', 'organization', 'patient', 'createdBy'])
            ->orderByDesc('reported_at')
            ->get();

        return response()->json([
            'patient' => [
                'id' => $patient->id,
                'full_name' => $patient->fullName(),
                'date_of_birth' => $patient->date_of_birth?->format('Y-m-d'),
                'gender' => $patient->gender,
                'access_pin' => $patient->access_pin,
                'is_claimed' => $patient->isClaimed(),
            ],
            'reports' => LabResultReportResource::collection($reports),
            'examination_reports' => ExaminationResultReportResource::collection($examinationReports),
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, LabResultReport>
     */
    private function reportsForUser(User $user)
    {
        return LabResultReport::query()
            ->whereHas('patient', fn ($q) => $q->where('user_id', $user->id))
            ->where('status', LabResultReport::STATUS_FINALIZED)
            ->with(['items', 'organization', 'patient'])
            ->orderByDesc('reported_at')
            ->get();
    }
}
