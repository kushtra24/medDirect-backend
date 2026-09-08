<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ChecksPatientOwnership;
use App\Http\Controllers\Controller;
use App\Http\Resources\PatientImmunizationResource;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\PatientImmunization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientImmunizationController extends Controller
{
    use ChecksPatientOwnership;

    public function store(Request $request, Organization $organization, Patient $patient): JsonResponse
    {
        $this->assertPatientInOrganization($patient, $organization);

        $validated = $request->validate(self::rules());

        $immunization = $patient->immunizations()->create([
            ...$validated,
            'organization_id' => $organization->id,
            'created_by_user_id' => $request->user()->id,
        ]);

        return response()->json([
            'immunization' => new PatientImmunizationResource($immunization),
        ], 201);
    }

    public function update(Request $request, Organization $organization, Patient $patient, PatientImmunization $immunization): JsonResponse
    {
        $this->assertPatientInOrganization($patient, $organization);
        $this->assertBelongsToPatient($immunization, $patient);

        $validated = $request->validate(self::rules(sometimes: true));
        $immunization->update($validated);

        return response()->json([
            'immunization' => new PatientImmunizationResource($immunization),
        ]);
    }

    public function destroy(Organization $organization, Patient $patient, PatientImmunization $immunization): JsonResponse
    {
        $this->assertPatientInOrganization($patient, $organization);
        $this->assertBelongsToPatient($immunization, $patient);

        $immunization->delete();

        return response()->json(['message' => 'Immunization removed.']);
    }

    /**
     * @return array<string, mixed>
     */
    private static function rules(bool $sometimes = false): array
    {
        $required = $sometimes ? 'sometimes' : 'required';

        return [
            'vaccine' => [$required, 'string', 'max:255'],
            'dose_number' => ['nullable', 'integer', 'min:1', 'max:99'],
            'administered_at' => ['nullable', 'date'],
            'administered_by' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
