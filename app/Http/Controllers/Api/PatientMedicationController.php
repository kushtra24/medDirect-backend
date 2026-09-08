<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ChecksPatientOwnership;
use App\Http\Controllers\Controller;
use App\Http\Resources\PatientMedicationResource;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\PatientMedication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientMedicationController extends Controller
{
    use ChecksPatientOwnership;

    public function store(Request $request, Organization $organization, Patient $patient): JsonResponse
    {
        $this->assertPatientInOrganization($patient, $organization);

        $validated = $request->validate(self::rules());

        $medication = $patient->medications()->create([
            ...$validated,
            'organization_id' => $organization->id,
            'created_by_user_id' => $request->user()->id,
        ]);

        return response()->json([
            'medication' => new PatientMedicationResource($medication),
        ], 201);
    }

    public function update(Request $request, Organization $organization, Patient $patient, PatientMedication $medication): JsonResponse
    {
        $this->assertPatientInOrganization($patient, $organization);
        $this->assertBelongsToPatient($medication, $patient);

        $validated = $request->validate(self::rules(sometimes: true));
        $medication->update($validated);

        return response()->json([
            'medication' => new PatientMedicationResource($medication),
        ]);
    }

    public function destroy(Organization $organization, Patient $patient, PatientMedication $medication): JsonResponse
    {
        $this->assertPatientInOrganization($patient, $organization);
        $this->assertBelongsToPatient($medication, $patient);

        $medication->delete();

        return response()->json(['message' => 'Medication removed.']);
    }

    /**
     * @return array<string, mixed>
     */
    private static function rules(bool $sometimes = false): array
    {
        $required = $sometimes ? 'sometimes' : 'required';

        return [
            'name' => [$required, 'string', 'max:255'],
            'dosage' => ['nullable', 'string', 'max:120'],
            'frequency' => ['nullable', 'string', 'max:120'],
            'route' => ['nullable', 'string', 'max:32'],
            'status' => ['nullable', 'string', 'in:active,discontinued'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'prescriber' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
