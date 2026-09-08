<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreLabResultReportRequest;
use App\Http\Resources\LabResultReportResource;
use App\Models\LabResultItem;
use App\Models\LabResultReport;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\PatientOrder;
use Illuminate\Http\JsonResponse;
class LabResultReportController extends Controller
{
    public function store(StoreLabResultReportRequest $request, Organization $organization): JsonResponse
    {
        $validated = $request->validated();
        $status = $validated['status'] ?? LabResultReport::STATUS_FINALIZED;
        $isFinalized = $status === LabResultReport::STATUS_FINALIZED;

        $patient = $this->resolvePatient($request, $organization, $validated);

        $report = LabResultReport::query()->create([
            'patient_id' => $patient->id,
            'organization_id' => $organization->id,
            'created_by_user_id' => $request->user()->id,
            'doctor_note' => $validated['doctor_note'] ?? null,
            'status' => $status,
            'reported_at' => $isFinalized ? now() : null,
        ]);

        foreach ($validated['items'] as $index => $item) {
            LabResultItem::query()->create([
                'lab_result_report_id' => $report->id,
                'test_name' => $item['test_name'],
                'value' => $item['value'] ?? null,
                'status' => $item['status'] ?? LabResultItem::STATUS_NORMAL,
                'reference_point' => $item['reference_point'] ?? null,
                'sort_order' => $index,
            ]);
        }

        if (! empty($validated['order_id'])) {
            $this->fulfillOrder((int) $validated['order_id'], $organization, $patient, $report);
        }

        $report->load(['patient.organization', 'organization', 'items']);

        return response()->json([
            'report' => new LabResultReportResource($report),
        ], 201);
    }

    /**
     * Marks the order that prompted this report as resulted — a lab order fulfilled by
     * anything other than a lab report (or belonging to a different patient/org) is left
     * untouched rather than silently mis-linked.
     */
    private function fulfillOrder(int $orderId, Organization $organization, Patient $patient, LabResultReport $report): void
    {
        $order = PatientOrder::query()->find($orderId);

        if (
            $order === null
            || $order->organization_id !== $organization->id
            || $order->patient_id !== $patient->id
            || $order->type !== PatientOrder::TYPE_LAB
        ) {
            return;
        }

        $order->update([
            'lab_result_report_id' => $report->id,
            'status' => PatientOrder::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolvePatient(StoreLabResultReportRequest $request, Organization $organization, array $validated): Patient
    {
        if (! empty($validated['patient_id'])) {
            $patient = Patient::query()->findOrFail($validated['patient_id']);
            if ($patient->organization_id !== $organization->id) {
                abort(422, 'Patient does not belong to this organization.');
            }

            return $patient;
        }

        $pin = (string) random_int(100000, 999999);

        return Patient::query()->create([
            'organization_id' => $organization->id,
            'created_by_user_id' => $request->user()->id,
            'first_name' => $validated['first_name'],
            'surname' => $validated['surname'],
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'access_pin' => $pin,
        ]);
    }
}
