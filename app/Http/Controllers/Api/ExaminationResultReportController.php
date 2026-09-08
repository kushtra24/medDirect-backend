<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreExaminationResultReportRequest;
use App\Http\Resources\ExaminationResultReportResource;
use App\Models\ExaminationResultItem;
use App\Models\ExaminationResultReport;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\PatientOrder;
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

        if (! empty($validated['order_id'])) {
            $this->fulfillOrder((int) $validated['order_id'], $organization, $patient, $report);
        }

        $report->load(['patient.organization', 'organization', 'items']);

        return response()->json([
            'report' => new ExaminationResultReportResource($report),
        ], 201);
    }

    /**
     * Marks the order that prompted this report as resulted — an examination order
     * fulfilled by anything other than an exam report (or belonging to a different
     * patient/org) is left untouched rather than silently mis-linked.
     */
    private function fulfillOrder(int $orderId, Organization $organization, Patient $patient, ExaminationResultReport $report): void
    {
        $order = PatientOrder::query()->find($orderId);

        if (
            $order === null
            || $order->organization_id !== $organization->id
            || $order->patient_id !== $patient->id
            || $order->type !== PatientOrder::TYPE_EXAMINATION
        ) {
            return;
        }

        $order->update([
            'examination_result_report_id' => $report->id,
            'status' => PatientOrder::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }
}
