<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\PatientOrder */
class PatientOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'patient_name' => $this->whenLoaded('patient', fn () => $this->patient->fullName()),
            'type' => $this->type,
            'status' => $this->status,
            'reason' => $this->reason,
            'assigned_to' => $this->whenLoaded('assignedTo', fn () => $this->assignedTo ? [
                'id' => $this->assignedTo->id,
                'name' => $this->assignedTo->name,
            ] : null),
            'created_by' => $this->whenLoaded('createdBy', fn () => $this->createdBy?->name),
            'lab_result_report_id' => $this->lab_result_report_id,
            'examination_result_report_id' => $this->examination_result_report_id,
            'completed_at' => $this->completed_at,
            'created_at' => $this->created_at,
        ];
    }
}
