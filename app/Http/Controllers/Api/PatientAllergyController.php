<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ChecksPatientOwnership;
use App\Http\Controllers\Controller;
use App\Http\Resources\PatientAllergyResource;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\PatientAllergy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientAllergyController extends Controller
{
    use ChecksPatientOwnership;

    public function store(Request $request, Organization $organization, Patient $patient): JsonResponse
    {
        $this->assertPatientInOrganization($patient, $organization);

        $validated = $request->validate(self::rules());

        $allergy = $patient->allergyRecords()->create([
            ...$validated,
            'organization_id' => $organization->id,
            'created_by_user_id' => $request->user()->id,
        ]);

        return response()->json([
            'allergy' => new PatientAllergyResource($allergy),
        ], 201);
    }

    public function update(Request $request, Organization $organization, Patient $patient, PatientAllergy $allergy): JsonResponse
    {
        $this->assertPatientInOrganization($patient, $organization);
        $this->assertBelongsToPatient($allergy, $patient);

        $validated = $request->validate(self::rules(sometimes: true));
        $allergy->update($validated);

        return response()->json([
            'allergy' => new PatientAllergyResource($allergy),
        ]);
    }

    public function destroy(Organization $organization, Patient $patient, PatientAllergy $allergy): JsonResponse
    {
        $this->assertPatientInOrganization($patient, $organization);
        $this->assertBelongsToPatient($allergy, $patient);

        $allergy->delete();

        return response()->json(['message' => 'Allergy removed.']);
    }

    /**
     * @return array<string, mixed>
     */
    private static function rules(bool $sometimes = false): array
    {
        $required = $sometimes ? 'sometimes' : 'required';

        return [
            'substance' => [$required, 'string', 'max:255'],
            'reaction' => ['nullable', 'string', 'max:255'],
            'severity' => ['nullable', 'string', 'in:mild,moderate,severe'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'noted_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
