<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\PatientUpdateNotice */
class PatientUpdateNoticeResource extends JsonResource
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
            'changed_fields' => $this->changed_fields,
            'is_read' => $this->isRead(),
            'created_at' => $this->created_at,
        ];
    }
}
