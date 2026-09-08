<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrganizationMemberTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrgWithOwner(): array
    {
        $owner = User::factory()->create(['role' => User::ROLE_STAFF]);
        $org = Organization::query()->create([
            'name' => 'Member Clinic',
            'slug' => 'member-clinic-'.uniqid(),
            'type' => Organization::TYPE_CLINIC,
            'created_by_user_id' => $owner->id,
        ]);
        $org->members()->create(['user_id' => $owner->id, 'is_owner' => true, 'abilities' => null]);

        return [$owner, $org];
    }

    /**
     * Regression test: role isn't in User's mass-assignable attribute list, so passing it
     * inside User::create() silently drops it and the row is left at the 'patient' column
     * default — the new staff member could log in but would land on the patient portal.
     */
    public function test_adding_a_brand_new_staff_member_sets_their_role_to_staff(): void
    {
        [$owner, $org] = $this->makeOrgWithOwner();
        Sanctum::actingAs($owner);

        $response = $this->postJson("/api/organizations/{$org->id}/members", [
            'name' => 'New Hire',
            'email' => 'new-hire@example.com',
            'password' => 'password123',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('users', [
            'email' => 'new-hire@example.com',
            'role' => User::ROLE_STAFF,
        ]);
    }

    public function test_the_new_staff_member_can_log_in_and_lands_on_the_control_panel_role(): void
    {
        [$owner, $org] = $this->makeOrgWithOwner();
        Sanctum::actingAs($owner);

        $this->postJson("/api/organizations/{$org->id}/members", [
            'name' => 'New Hire',
            'email' => 'new-hire@example.com',
            'password' => 'password123',
        ])->assertCreated();

        $login = $this->postJson('/api/login', [
            'email' => 'new-hire@example.com',
            'password' => 'password123',
        ]);

        $login->assertOk()->assertJsonPath('user.role', User::ROLE_STAFF);
    }

    public function test_adding_an_existing_patient_as_staff_upgrades_their_role(): void
    {
        [$owner, $org] = $this->makeOrgWithOwner();
        $existingPatient = User::factory()->create(['role' => User::ROLE_PATIENT, 'email' => 'patient@example.com']);

        Sanctum::actingAs($owner);

        $this->postJson("/api/organizations/{$org->id}/members", [
            'email' => 'patient@example.com',
        ])->assertCreated();

        $this->assertDatabaseHas('users', [
            'id' => $existingPatient->id,
            'role' => User::ROLE_STAFF,
        ]);
    }
}
