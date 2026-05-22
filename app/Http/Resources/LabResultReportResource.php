<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\LabResultReport */
class LabResultReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'organization_id' => $this->organization_id,
            'doctor_note' => $this->doctor_note,
            'status' => $this->status,
            'reported_at' => $this->reported_at?->toIso8601String(),
            'patient' => new PatientResource($this->whenLoaded('patient')),
            'organization' => $this->whenLoaded('organization', fn () => [
                'id' => $this->organization->id,
                'name' => $this->organization->name,
                'slug' => $this->organization->slug,
                'type' => $this->organization->type,
            ]),
            'items' => LabResultItemResource::collection($this->whenLoaded('items')),
            'created_by' => $this->whenLoaded('createdBy', fn () => [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
