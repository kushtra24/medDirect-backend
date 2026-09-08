<?php

namespace Tests\Feature;

use App\Authorization\OrganizationAbilities;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PatientClinicalHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrgWithOwnerAndPatient(): array
    {
        $owner = User::factory()->create(['role' => User::ROLE_STAFF]);
        $org = Organization::query()->create([
            'name' => 'Clinical Clinic',
            'slug' => 'clinical-clinic-'.uniqid(),
            'type' => Organization::TYPE_CLINIC,
            'created_by_user_id' => $owner->id,
        ]);
        $org->members()->create(['user_id' => $owner->id, 'is_owner' => true, 'abilities' => null]);

        $patient = Patient::query()->create([
            'organization_id' => $org->id,
            'first_name' => 'Jane',
            'surname' => 'Doe',
        ]);

        return [$owner, $org, $patient];
    }

    public function test_staff_can_add_update_and_remove_allergy(): void
    {
        [$owner, $org, $patient] = $this->makeOrgWithOwnerAndPatient();
        Sanctum::actingAs($owner);

        $create = $this->postJson("/api/organizations/{$org->id}/patients/{$patient->id}/allergies", [
            'substance' => 'Penicillin',
            'reaction' => 'Hives',
            'severity' => 'moderate',
        ]);
        $create->assertCreated()->assertJsonPath('allergy.substance', 'Penicillin');
        $id = $create->json('allergy.id');

        $this->patchJson("/api/organizations/{$org->id}/patients/{$patient->id}/allergies/{$id}", [
            'status' => 'inactive',
        ])->assertOk()->assertJsonPath('allergy.status', 'inactive');

        $this->deleteJson("/api/organizations/{$org->id}/patients/{$patient->id}/allergies/{$id}")
            ->assertOk();
        $this->assertDatabaseMissing('patient_allergies', ['id' => $id]);
    }

    public function test_staff_can_add_update_and_remove_problem(): void
    {
        [$owner, $org, $patient] = $this->makeOrgWithOwnerAndPatient();
        Sanctum::actingAs($owner);

        $create = $this->postJson("/api/organizations/{$org->id}/patients/{$patient->id}/problems", [
            'condition' => 'Type 2 diabetes',
            'onset_date' => '2020-01-01',
        ]);
        $create->assertCreated()->assertJsonPath('problem.condition', 'Type 2 diabetes');
        $id = $create->json('problem.id');

        $this->patchJson("/api/organizations/{$org->id}/patients/{$patient->id}/problems/{$id}", [
            'status' => 'resolved',
            'resolved_date' => '2024-06-01',
        ])->assertOk()->assertJsonPath('problem.status', 'resolved');

        $this->deleteJson("/api/organizations/{$org->id}/patients/{$patient->id}/problems/{$id}")
            ->assertOk();
        $this->assertDatabaseMissing('patient_problems', ['id' => $id]);
    }

    public function test_staff_can_add_update_and_remove_medication(): void
    {
        [$owner, $org, $patient] = $this->makeOrgWithOwnerAndPatient();
        Sanctum::actingAs($owner);

        $create = $this->postJson("/api/organizations/{$org->id}/patients/{$patient->id}/medications", [
            'name' => 'Metformin',
            'dosage' => '500mg',
            'frequency' => 'twice daily',
        ]);
        $create->assertCreated()->assertJsonPath('medication.name', 'Metformin');
        $id = $create->json('medication.id');

        $this->patchJson("/api/organizations/{$org->id}/patients/{$patient->id}/medications/{$id}", [
            'status' => 'discontinued',
            'end_date' => '2026-01-01',
        ])->assertOk()->assertJsonPath('medication.status', 'discontinued');

        $this->deleteJson("/api/organizations/{$org->id}/patients/{$patient->id}/medications/{$id}")
            ->assertOk();
        $this->assertDatabaseMissing('patient_medications', ['id' => $id]);
    }

    public function test_staff_can_add_update_and_remove_immunization(): void
    {
        [$owner, $org, $patient] = $this->makeOrgWithOwnerAndPatient();
        Sanctum::actingAs($owner);

        $create = $this->postJson("/api/organizations/{$org->id}/patients/{$patient->id}/immunizations", [
            'vaccine' => 'Influenza',
            'dose_number' => 1,
            'administered_at' => '2026-01-15',
        ]);
        $create->assertCreated()->assertJsonPath('immunization.vaccine', 'Influenza');
        $id = $create->json('immunization.id');

        $this->patchJson("/api/organizations/{$org->id}/patients/{$patient->id}/immunizations/{$id}", [
            'administered_by' => 'Dr. Smith',
        ])->assertOk()->assertJsonPath('immunization.administered_by', 'Dr. Smith');

        $this->deleteJson("/api/organizations/{$org->id}/patients/{$patient->id}/immunizations/{$id}")
            ->assertOk();
        $this->assertDatabaseMissing('patient_immunizations', ['id' => $id]);
    }

    public function test_staff_can_add_update_and_remove_vital(): void
    {
        [$owner, $org, $patient] = $this->makeOrgWithOwnerAndPatient();
        Sanctum::actingAs($owner);

        $create = $this->postJson("/api/organizations/{$org->id}/patients/{$patient->id}/vitals", [
            'recorded_at' => '2026-01-15T10:00:00Z',
            'height_cm' => 180,
            'weight_kg' => 81,
            'blood_pressure_systolic' => 120,
            'blood_pressure_diastolic' => 80,
        ]);
        $create->assertCreated()
            ->assertJsonPath('vital.heart_rate', null)
            ->assertJsonPath('vital.bmi', 25);
        $id = $create->json('vital.id');

        $this->patchJson("/api/organizations/{$org->id}/patients/{$patient->id}/vitals/{$id}", [
            'heart_rate' => 72,
        ])->assertOk()->assertJsonPath('vital.heart_rate', 72);

        $this->deleteJson("/api/organizations/{$org->id}/patients/{$patient->id}/vitals/{$id}")
            ->assertOk();
        $this->assertDatabaseMissing('patient_vitals', ['id' => $id]);
    }

    public function test_staff_without_edit_ability_cannot_add_clinical_records(): void
    {
        [, $org, $patient] = $this->makeOrgWithOwnerAndPatient();

        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $org->members()->create([
            'user_id' => $staff->id,
            'is_owner' => false,
            'abilities' => [OrganizationAbilities::CAN_VIEW_PATIENTS => true, OrganizationAbilities::CAN_EDIT_PATIENT => false],
        ]);

        Sanctum::actingAs($staff);

        $this->postJson("/api/organizations/{$org->id}/patients/{$patient->id}/allergies", ['substance' => 'Latex'])
            ->assertForbidden();
        $this->postJson("/api/organizations/{$org->id}/patients/{$patient->id}/vitals", ['heart_rate' => 70])
            ->assertForbidden();
    }

    public function test_patient_show_includes_nested_clinical_history(): void
    {
        [$owner, $org, $patient] = $this->makeOrgWithOwnerAndPatient();
        Sanctum::actingAs($owner);

        $this->postJson("/api/organizations/{$org->id}/patients/{$patient->id}/allergies", ['substance' => 'Penicillin'])
            ->assertCreated();
        $this->postJson("/api/organizations/{$org->id}/patients/{$patient->id}/problems", ['condition' => 'Asthma'])
            ->assertCreated();
        $this->postJson("/api/organizations/{$org->id}/patients/{$patient->id}/medications", ['name' => 'Ibuprofen'])
            ->assertCreated();
        $this->postJson("/api/organizations/{$org->id}/patients/{$patient->id}/immunizations", ['vaccine' => 'Tetanus'])
            ->assertCreated();
        $this->postJson("/api/organizations/{$org->id}/patients/{$patient->id}/vitals", ['heart_rate' => 65])
            ->assertCreated();

        $response = $this->getJson("/api/organizations/{$org->id}/patients/{$patient->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'patient.allergy_records')
            ->assertJsonCount(1, 'patient.problems')
            ->assertJsonCount(1, 'patient.medications')
            ->assertJsonCount(1, 'patient.immunizations')
            ->assertJsonCount(1, 'patient.vitals');
    }
}
