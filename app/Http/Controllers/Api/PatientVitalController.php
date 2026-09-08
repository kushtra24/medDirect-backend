<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ChecksPatientOwnership;
use App\Http\Controllers\Controller;
use App\Http\Resources\PatientVitalResource;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\PatientVital;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientVitalController extends Controller
{
    use ChecksPatientOwnership;

    public function store(Request $request, Organization $organization, Patient $patient): JsonResponse
    {
        $this->assertPatientInOrganization($patient, $organization);

        $validated = $request->validate(self::rules());

        $vital = $patient->vitals()->create([
            ...$validated,
            'recorded_at' => $validated['recorded_at'] ?? now(),
            'organization_id' => $organization->id,
            'created_by_user_id' => $request->user()->id,
        ]);

        return response()->json([
            'vital' => new PatientVitalResource($vital),
        ], 201);
    }

    public function update(Request $request, Organization $organization, Patient $patient, PatientVital $vital): JsonResponse
    {
        $this->assertPatientInOrganization($patient, $organization);
        $this->assertBelongsToPatient($vital, $patient);

        $validated = $request->validate(self::rules());
        $vital->update($validated);

        return response()->json([
            'vital' => new PatientVitalResource($vital),
        ]);
    }

    public function destroy(Organization $organization, Patient $patient, PatientVital $vital): JsonResponse
    {
        $this->assertPatientInOrganization($patient, $organization);
        $this->assertBelongsToPatient($vital, $patient);

        $vital->delete();

        return response()->json(['message' => 'Vitals entry removed.']);
    }

    /**
     * @return array<string, mixed>
     */
    private static function rules(): array
    {
        return [
            'recorded_at' => ['nullable', 'date'],
            'height_cm' => ['nullable', 'numeric', 'min:0', 'max:300'],
            'weight_kg' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'blood_pressure_systolic' => ['nullable', 'integer', 'min:0', 'max:300'],
            'blood_pressure_diastolic' => ['nullable', 'integer', 'min:0', 'max:300'],
            'heart_rate' => ['nullable', 'integer', 'min:0', 'max:300'],
            'temperature_c' => ['nullable', 'numeric', 'min:20', 'max:45'],
            'respiratory_rate' => ['nullable', 'integer', 'min:0', 'max:100'],
            'oxygen_saturation' => ['nullable', 'integer', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
