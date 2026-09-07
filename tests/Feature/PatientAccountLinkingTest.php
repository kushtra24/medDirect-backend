<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PatientAccountLinkingTest extends TestCase
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
        $org->members()->create([
            'user_id' => $staff->id,
            'is_owner' => true,
            'abilities' => null,
        ]);

        return [$staff, $org];
    }

    public function test_matching_identity_autolinks_new_patient_to_portal_account(): void
    {
        [$staff, $org] = $this->makeStaffOrg();

        $portalUser = User::factory()->create([
            'role' => User::ROLE_PATIENT,
            'name' => 'Alex Rivera',
            'national_id' => 'ID-12345',
            'date_of_birth' => '1992-03-10',
        ]);

        Sanctum::actingAs($staff);

        $response = $this->postJson("/api/organizations/{$org->id}/patients", [
            'first_name' => 'Alex',
            'surname' => 'Rivera',
            'date_of_birth' => '1992-03-10',
            'national_id' => 'ID-12345',
        ]);

        $response->assertCreated()
            ->assertJsonPath('patient.user_id', $portalUser->id)
            ->assertJsonPath('patient.is_claimed', true);

        $this->assertDatabaseHas('patients', [
            'organization_id' => $org->id,
            'national_id' => 'ID-12345',
            'user_id' => $portalUser->id,
        ]);
    }

    public function test_mismatched_identity_does_not_link(): void
    {
        [$staff, $org] = $this->makeStaffOrg();

        User::factory()->create([
            'role' => User::ROLE_PATIENT,
            'name' => 'Alex Rivera',
            'national_id' => 'ID-12345',
            'date_of_birth' => '1992-03-10',
        ]);

        Sanctum::actingAs($staff);

        // Same name and DOB, but different national ID — must not link.
        $response = $this->postJson("/api/organizations/{$org->id}/patients", [
            'first_name' => 'Alex',
            'surname' => 'Rivera',
            'date_of_birth' => '1992-03-10',
            'national_id' => 'ID-99999',
        ]);

        $response->assertCreated()
            ->assertJsonPath('patient.user_id', null)
            ->assertJsonPath('patient.is_claimed', false);
    }

    public function test_match_endpoint_returns_autofill_suggestion(): void
    {
        [$staff, $org] = $this->makeStaffOrg();

        $portalUser = User::factory()->create([
            'role' => User::ROLE_PATIENT,
            'name' => 'Alex Rivera',
            'national_id' => 'ID-12345',
            'date_of_birth' => '1992-03-10',
            'phone' => '+15550001',
            'blood_type' => 'O+',
        ]);

        Sanctum::actingAs($staff);

        $response = $this->postJson("/api/organizations/{$org->id}/patients/match", [
            'first_name' => 'Alex',
            'surname' => 'Rivera',
            'date_of_birth' => '1992-03-10',
            'national_id' => 'ID-12345',
        ]);

        $response->assertOk()
            ->assertJsonPath('matched', true)
            ->assertJsonPath('user_id', $portalUser->id)
            ->assertJsonPath('suggested.phone', '+15550001')
            ->assertJsonPath('suggested.blood_group', 'O+')
            ->assertJsonPath('already_registered_patient_id', null);
    }

    public function test_patient_profile_update_creates_notice_for_each_linked_clinic(): void
    {
        [, $orgA] = $this->makeStaffOrg();
        [, $orgB] = $this->makeStaffOrg();

        $patientUser = User::factory()->create([
            'role' => User::ROLE_PATIENT,
            'name' => 'Jamie Lee',
            'phone' => '+15550000',
        ]);

        $patientInA = Patient::query()->create([
            'organization_id' => $orgA->id,
            'user_id' => $patientUser->id,
            'first_name' => 'Jamie',
            'surname' => 'Lee',
            'phone' => '+15550000',
        ]);

        // Org B's copy already has the phone number the patient is about to set — no notice expected there.
        Patient::query()->create([
            'organization_id' => $orgB->id,
            'user_id' => $patientUser->id,
            'first_name' => 'Jamie',
            'surname' => 'Lee',
            'phone' => '+15551111',
        ]);

        Sanctum::actingAs($patientUser);

        $response = $this->patchJson('/api/user/profile', [
            'phone' => '+15551111',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('patient_update_notices', [
            'patient_id' => $patientInA->id,
            'organization_id' => $orgA->id,
            'user_id' => $patientUser->id,
        ]);
        $this->assertDatabaseCount('patient_update_notices', 1);
    }

    public function test_staff_can_list_and_mark_notices_read_scoped_to_their_organization(): void
    {
        [$staffA, $orgA] = $this->makeStaffOrg();
        [$staffB, $orgB] = $this->makeStaffOrg();

        $patientUser = User::factory()->create(['role' => User::ROLE_PATIENT, 'name' => 'Jamie Lee']);

        $patient = Patient::query()->create([
            'organization_id' => $orgA->id,
            'user_id' => $patientUser->id,
            'first_name' => 'Jamie',
            'surname' => 'Lee',
        ]);

        $notice = $patient->updateNotices()->create([
            'organization_id' => $orgA->id,
            'user_id' => $patientUser->id,
            'changed_fields' => ['phone' => ['old' => null, 'new' => '+15551111']],
        ]);

        Sanctum::actingAs($staffB);
        $this->getJson("/api/organizations/{$orgB->id}/patient-notices")
            ->assertOk()
            ->assertJsonCount(0, 'notices');
        $this->postJson("/api/organizations/{$orgB->id}/patient-notices/{$notice->id}/read")
            ->assertNotFound();

        Sanctum::actingAs($staffA);
        $this->getJson("/api/organizations/{$orgA->id}/patient-notices")
            ->assertOk()
            ->assertJsonCount(1, 'notices')
            ->assertJsonPath('notices.0.patient_name', 'Jamie Lee')
            ->assertJsonPath('notices.0.is_read', false)
            ->assertJsonPath('unread_count', 1);

        $this->postJson("/api/organizations/{$orgA->id}/patient-notices/{$notice->id}/read")
            ->assertOk()
            ->assertJsonPath('notice.is_read', true);

        $this->getJson("/api/organizations/{$orgA->id}/patient-notices")
            ->assertOk()
            ->assertJsonPath('unread_count', 0);
    }
}
