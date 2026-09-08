<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PatientRecordClaimTest extends TestCase
{
    use RefreshDatabase;

    private function makeStaffOrg(): array
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $org = Organization::query()->create([
            'name' => 'Test Clinic',
            'slug' => 'test-clinic-'.uniqid(),
            'type' => Organization::TYPE_CLINIC,
            'created_by_user_id' => $staff->id,
        ]);

        return [$staff, $org];
    }

    private function makeUnclaimedPatient(Organization $org, array $overrides = []): Patient
    {
        return Patient::query()->create(array_merge([
            'organization_id' => $org->id,
            'first_name' => 'Jamie',
            'surname' => 'Lee',
            'access_pin' => '123456',
        ], $overrides));
    }

    public function test_guest_lookup_reports_claim_status(): void
    {
        [, $org] = $this->makeStaffOrg();
        $this->makeUnclaimedPatient($org);

        $response = $this->postJson('/api/patient-records/lookup', [
            'full_name' => 'Jamie Lee',
            'access_pin' => '123456',
        ]);

        $response->assertOk()->assertJsonPath('patient.is_claimed', false);
    }

    public function test_logged_in_patient_can_claim_a_found_record(): void
    {
        [, $org] = $this->makeStaffOrg();
        $patient = $this->makeUnclaimedPatient($org);

        $portalUser = User::factory()->create(['role' => User::ROLE_PATIENT]);
        Sanctum::actingAs($portalUser);

        $response = $this->postJson('/api/patient-records/claim', [
            'full_name' => 'Jamie Lee',
            'access_pin' => '123456',
        ]);

        $response->assertOk()->assertJsonPath('claimed', true);

        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'user_id' => $portalUser->id,
        ]);
    }

    public function test_a_record_already_claimed_by_another_account_cannot_be_claimed_again(): void
    {
        [, $org] = $this->makeStaffOrg();
        $firstClaimer = User::factory()->create(['role' => User::ROLE_PATIENT]);
        $patient = $this->makeUnclaimedPatient($org, [
            'user_id' => $firstClaimer->id,
            'claimed_at' => now(),
        ]);

        $secondUser = User::factory()->create(['role' => User::ROLE_PATIENT]);
        Sanctum::actingAs($secondUser);

        $response = $this->postJson('/api/patient-records/claim', [
            'full_name' => 'Jamie Lee',
            'access_pin' => '123456',
        ]);

        $response->assertStatus(409);

        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'user_id' => $firstClaimer->id,
        ]);
    }

    public function test_reclaiming_ones_own_record_is_idempotent(): void
    {
        [, $org] = $this->makeStaffOrg();
        $owner = User::factory()->create(['role' => User::ROLE_PATIENT]);
        $this->makeUnclaimedPatient($org, [
            'user_id' => $owner->id,
            'claimed_at' => now(),
        ]);

        Sanctum::actingAs($owner);

        $this->postJson('/api/patient-records/claim', [
            'full_name' => 'Jamie Lee',
            'access_pin' => '123456',
        ])->assertOk()->assertJsonPath('claimed', true);
    }

    public function test_only_patient_accounts_can_claim(): void
    {
        [$staff, $org] = $this->makeStaffOrg();
        $this->makeUnclaimedPatient($org);

        Sanctum::actingAs($staff);

        $this->postJson('/api/patient-records/claim', [
            'full_name' => 'Jamie Lee',
            'access_pin' => '123456',
        ])->assertForbidden();
    }
}
