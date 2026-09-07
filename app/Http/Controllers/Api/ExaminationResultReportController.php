<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreExaminationResultReportRequest;
use App\Http\Resources\ExaminationResultReportResource;
use App\Models\ExaminationResultItem;
use App\Models\ExaminationResultReport;
use App\Models\Organization;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;

class ExaminationResultReportController extends Controller
{
    public function store(StoreExaminationResultReportRequest $request, Organization $organization): JsonResponse
    {
        $validated = $request->validated();
        $status = $validated['status'] ?? ExaminationResultReport::STATUS_FINALIZED;
        $isFinalized = $status === ExaminationResultReport::STATUS_FINALIZED;

        $patient = Patient::query()->findOrFail($validated['patient_id']);
        if ($patient->organization_id !== $organization->id) {
            abort(422, 'Patient does not belong to this organization.');
        }

        $report = ExaminationResultReport::query()->create([
            'patient_id' => $patient->id,
            'organization_id' => $organization->id,
            'created_by_user_id' => $request->user()->id,
            'doctor_note' => $validated['doctor_note'] ?? null,
            'status' => $status,
            'reported_at' => $isFinalized ? now() : null,
        ]);

        foreach ($validated['items'] as $index => $item) {
            ExaminationResultItem::query()->create([
                'examination_result_report_id' => $report->id,
                'finding_name' => $item['finding_name'],
                'value' => $item['value'] ?? null,
                'status' => $item['status'] ?? ExaminationResultItem::STATUS_NORMAL,
                'reference_point' => $item['reference_point'] ?? null,
                'sort_order' => $index,
            ]);
        }

        $report->load(['patient.organization', 'organization', 'items']);

        return response()->json([
            'report' => new ExaminationResultReportResource($report),
        ], 201);
    }
}
