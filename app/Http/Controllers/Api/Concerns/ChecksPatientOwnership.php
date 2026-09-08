<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Organization;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared 404 guards for the patient sub-resource controllers (allergies, problems,
 * medications, immunizations, vitals) — each nests under /patients/{patient}/... and
 * route-model-binds independently, so the parent relationships aren't verified for free.
 */
trait ChecksPatientOwnership
{
    private function assertPatientInOrganization(Patient $patient, Organization $organization): void
    {
        if ($patient->organization_id !== $organization->id) {
            abort(404);
        }
    }

    private function assertBelongsToPatient(Model $record, Patient $patient): void
    {
        if ($record->patient_id !== $patient->id) {
            abort(404);
        }
    }
}
