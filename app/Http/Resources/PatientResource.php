<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Patient */
class PatientResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'user_id' => $this->user_id,
            'first_name' => $this->first_name,
            'surname' => $this->surname,
            'full_name' => $this->fullName(),
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'gender' => $this->gender,
            'national_id' => $this->national_id,
            'insurance_provider' => $this->insurance_provider,
            'policy_number' => $this->policy_number,
            'blood_group' => $this->blood_group,
            'allergies' => $this->allergies,
            'chronic_conditions' => $this->chronic_conditions,
            'phone' => $this->phone,
            'email' => $this->email,
            'access_pin' => $this->access_pin,
            'is_claimed' => $this->isClaimed(),
            'claimed_at' => $this->claimed_at,
            'organization' => $this->whenLoaded('organization', fn () => [
                'id' => $this->organization->id,
                'name' => $this->organization->name,
                'slug' => $this->organization->slug,
                'type' => $this->organization->type,
            ]),
            'created_at' => $this->created_at,
            'lab_result_reports' => LabResultReportResource::collection($this->whenLoaded('labResultReports')),
            'examination_result_reports' => ExaminationResultReportResource::collection($this->whenLoaded('examinationResultReports')),
            'allergy_records' => PatientAllergyResource::collection($this->whenLoaded('allergyRecords')),
            'problems' => PatientProblemResource::collection($this->whenLoaded('problems')),
            'medications' => PatientMedicationResource::collection($this->whenLoaded('medications')),
            'immunizations' => PatientImmunizationResource::collection($this->whenLoaded('immunizations')),
            'vitals' => PatientVitalResource::collection($this->whenLoaded('vitals')),
            'orders' => PatientOrderResource::collection($this->whenLoaded('orders')),
        ];
    }
}
