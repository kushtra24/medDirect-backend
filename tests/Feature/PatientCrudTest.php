<?php

namespace Tests\Feature;

use App\Authorization\OrganizationAbilities;
use App\Models\LabResultReport;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PatientCrudTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrgWithOwner(): array
    {
        $owner = User::factory()->create(['role' => User::ROLE_STAFF]);
        $org = Organization::query()->create([
            'name' => 'CRUD Clinic',
            'slug' => 'crud-clinic-'.uniqid(),
            'type' => Organization::TYPE_CLINIC,
            'created_by_user_id' => $owner->id,
        ]);
        $org->members()->create(['user_id' => $owner->id, 'is_owner' => true, 'abilities' => null]);

        return [$owner, $org];
    }

    public function test_owner_can_update_a_patient(): void
    {
        [$owner, $org] = $this->makeOrgWithOwner();
        $patient = Patient::query()->create([
            'organization_id' => $org->id,
            'first_name' => 'Jane',
            'surname' => 'Doe',
        ]);

        Sanctum::actingAs($owner);

        $response = $this->patchJson("/api/organizations/{$org->id}/patients/{$patient->id}", [
            'first_name' => 'Janet',
            'surname' => 'Doe',
            'phone' => '+15551234',
        ]);

        $response->assertOk()
            ->assertJsonPath('patient.first_name', 'Janet')
            ->assertJsonPath('patient.phone', '+15551234');

        $this->assertDatabaseHas('patients', ['id' => $patient->id, 'first_name' => 'Janet']);
    }

    public function test_staff_without_edit_ability_cannot_update_or_delete_patient(): void
    {
        [$owner, $org] = $this->makeOrgWithOwner();
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $org->members()->create([
            'user_id' => $staff->id,
            'is_owner' => false,
            'abilities' => [OrganizationAbilities::CAN_VIEW_PATIENTS => true, OrganizationAbilities::CAN_EDIT_PATIENT => false],
        ]);

        $patient = Patient::query()->create([
            'organization_id' => $org->id,
            'first_name' => 'Jane',
            'surname' => 'Doe',
        ]);

        Sanctum::actingAs($staff);

        $this->patchJson("/api/organizations/{$org->id}/patients/{$patient->id}", ['first_name' => 'X', 'surname' => 'Y'])
            ->assertForbidden();

        $this->deleteJson("/api/organizations/{$org->id}/patients/{$patient->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('patients', ['id' => $patient->id, 'first_name' => 'Jane', 'deleted_at' => null]);
    }

    public function test_staff_with_edit_ability_can_update_and_delete_patient(): void
    {
        [$owner, $org] = $this->makeOrgWithOwner();
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $org->members()->create([
            'user_id' => $staff->id,
            'is_owner' => false,
            'abilities' => [OrganizationAbilities::CAN_VIEW_PATIENTS => true, OrganizationAbilities::CAN_EDIT_PATIENT => true],
        ]);

        $patient = Patient::query()->create([
            'organization_id' => $org->id,
            'first_name' => 'Jane',
            'surname' => 'Doe',
        ]);

        Sanctum::actingAs($staff);

        $this->patchJson("/api/organizations/{$org->id}/patients/{$patient->id}", ['first_name' => 'Janet', 'surname' => 'Doe'])
            ->assertOk()
            ->assertJsonPath('patient.first_name', 'Janet');

        $this->deleteJson("/api/organizations/{$org->id}/patients/{$patient->id}")
            ->assertOk();

        $this->assertSoftDeleted('patients', ['id' => $patient->id]);
    }

    public function test_deleted_patient_disappears_from_search_and_show_but_keeps_lab_reports(): void
    {
        [$owner, $org] = $this->makeOrgWithOwner();

        $patient = Patient::query()->create([
            'organization_id' => $org->id,
            'first_name' => 'Jane',
            'surname' => 'Doe',
        ]);
        $report = LabResultReport::query()->create([
            'patient_id' => $patient->id,
            'organization_id' => $org->id,
            'status' => 'finalized',
        ]);

        Sanctum::actingAs($owner);

        $this->deleteJson("/api/organizations/{$org->id}/patients/{$patient->id}")->assertOk();

        $this->getJson("/api/organizations/{$org->id}/patients/{$patient->id}")->assertNotFound();
        $this->getJson("/api/organizations/{$org->id}/patients")
            ->assertOk()
            ->assertJsonCount(0, 'patients');

        // The report row is untouched — only the patient was soft-deleted, not cascade-removed.
        $this->assertDatabaseHas('lab_result_reports', ['id' => $report->id, 'patient_id' => $patient->id]);
    }

    public function test_updating_a_patient_in_another_organization_returns_not_found(): void
    {
        [$owner, $org] = $this->makeOrgWithOwner();
        [, $otherOrg] = $this->makeOrgWithOwner();

        $patient = Patient::query()->create([
            'organization_id' => $otherOrg->id,
            'first_name' => 'Jane',
            'surname' => 'Doe',
        ]);

        Sanctum::actingAs($owner);

        $this->patchJson("/api/organizations/{$org->id}/patients/{$patient->id}", ['first_name' => 'X', 'surname' => 'Y'])
            ->assertNotFound();
    }
}
