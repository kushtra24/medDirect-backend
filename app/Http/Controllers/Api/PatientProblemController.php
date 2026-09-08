<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ChecksPatientOwnership;
use App\Http\Controllers\Controller;
use App\Http\Resources\PatientProblemResource;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\PatientProblem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientProblemController extends Controller
{
    use ChecksPatientOwnership;

    public function store(Request $request, Organization $organization, Patient $patient): JsonResponse
    {
        $this->assertPatientInOrganization($patient, $organization);

        $validated = $request->validate(self::rules());

        $problem = $patient->problems()->create([
            ...$validated,
            'organization_id' => $organization->id,
            'created_by_user_id' => $request->user()->id,
        ]);

        return response()->json([
            'problem' => new PatientProblemResource($problem),
        ], 201);
    }

    public function update(Request $request, Organization $organization, Patient $patient, PatientProblem $problem): JsonResponse
    {
        $this->assertPatientInOrganization($patient, $organization);
        $this->assertBelongsToPatient($problem, $patient);

        $validated = $request->validate(self::rules(sometimes: true));
        $problem->update($validated);

        return response()->json([
            'problem' => new PatientProblemResource($problem),
        ]);
    }

    public function destroy(Organization $organization, Patient $patient, PatientProblem $problem): JsonResponse
    {
        $this->assertPatientInOrganization($patient, $organization);
        $this->assertBelongsToPatient($problem, $patient);

        $problem->delete();

        return response()->json(['message' => 'Problem removed.']);
    }

    /**
     * @return array<string, mixed>
     */
    private static function rules(bool $sometimes = false): array
    {
        $required = $sometimes ? 'sometimes' : 'required';

        return [
            'condition' => [$required, 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:active,resolved'],
            'onset_date' => ['nullable', 'date'],
            'resolved_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
