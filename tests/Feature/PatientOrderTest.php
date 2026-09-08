<?php

namespace Tests\Feature;

use App\Authorization\OrganizationAbilities;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\PatientOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PatientOrderTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrgWithOwnerAndPatient(): array
    {
        $owner = User::factory()->create(['role' => User::ROLE_STAFF]);
        $org = Organization::query()->create([
            'name' => 'Orders Clinic',
            'slug' => 'orders-clinic-'.uniqid(),
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

    /**
     * Adds a bare staff member with no explicit abilities stored, so effectiveAbilities()
     * falls entirely back to OrganizationAbilities::defaults().
     */
    private function makeDefaultStaff(Organization $org): User
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $org->members()->create(['user_id' => $staff->id, 'is_owner' => false, 'abilities' => null]);

        return $staff;
    }

    public function test_front_desk_staff_can_create_an_order_without_any_explicit_grant(): void
    {
        [, $org, $patient] = $this->makeOrgWithOwnerAndPatient();
        $frontDesk = $this->makeDefaultStaff($org);

        Sanctum::actingAs($frontDesk);

        $response = $this->postJson("/api/organizations/{$org->id}/patients/{$patient->id}/orders", [
            'type' => 'lab',
            'reason' => 'Routine bloodwork requested by patient.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('order.type', 'lab')
            ->assertJsonPath('order.status', 'pending')
            ->assertJsonPath('order.assigned_to', null);

        $this->assertDatabaseHas('patient_orders', [
            'patient_id' => $patient->id,
            'organization_id' => $org->id,
            'type' => 'lab',
            'status' => 'pending',
        ]);
    }

    public function test_default_abilities_grant_create_patient_and_create_order_but_not_view_patients(): void
    {
        [, $org] = $this->makeOrgWithOwnerAndPatient();
        $staff = $this->makeDefaultStaff($org);

        $abilities = $staff->fresh()->membershipFor($org)->effectiveAbilities();

        $this->assertTrue($abilities[OrganizationAbilities::CAN_CREATE_PATIENT]);
        $this->assertTrue($abilities[OrganizationAbilities::CAN_CREATE_ORDER]);
        $this->assertFalse($abilities[OrganizationAbilities::CAN_VIEW_PATIENTS]);
        $this->assertFalse($abilities[OrganizationAbilities::CAN_CREATE_ADD_RESULTS]);
    }

    public function test_staff_with_order_ability_explicitly_revoked_cannot_create_an_order(): void
    {
        [, $org, $patient] = $this->makeOrgWithOwnerAndPatient();
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $org->members()->create([
            'user_id' => $staff->id,
            'is_owner' => false,
            'abilities' => [OrganizationAbilities::CAN_CREATE_ORDER => false],
        ]);

        Sanctum::actingAs($staff);

        $this->postJson("/api/organizations/{$org->id}/patients/{$patient->id}/orders", ['type' => 'lab'])
            ->assertForbidden();
    }

    public function test_order_can_be_routed_to_a_specific_doctor_and_appears_in_their_queue(): void
    {
        [$owner, $org, $patient] = $this->makeOrgWithOwnerAndPatient();
        $doctor = $this->makeDefaultStaff($org);
        $frontDesk = $this->makeDefaultStaff($org);

        Sanctum::actingAs($frontDesk);
        $this->postJson("/api/organizations/{$org->id}/patients/{$patient->id}/orders", [
            'type' => 'examination',
            'reason' => 'Follow-up for chest pain.',
            'assigned_to_user_id' => $doctor->id,
        ])->assertCreated();

        Sanctum::actingAs($owner);

        $mine = $this->getJson("/api/organizations/{$org->id}/orders?scope=mine");
        $mine->assertOk()->assertJsonCount(0, 'orders');

        $doctorsQueue = $this->getJson("/api/organizations/{$org->id}/orders?scope=all");
        $doctorsQueue->assertOk()
            ->assertJsonCount(1, 'orders')
            ->assertJsonPath('orders.0.assigned_to.id', $doctor->id)
            ->assertJsonPath('orders.0.type', 'examination');
    }

    public function test_assigning_an_order_to_a_non_member_is_rejected(): void
    {
        [$owner, $org, $patient] = $this->makeOrgWithOwnerAndPatient();
        $outsider = User::factory()->create(['role' => User::ROLE_STAFF]);

        Sanctum::actingAs($owner);

        $this->postJson("/api/organizations/{$org->id}/patients/{$patient->id}/orders", [
            'type' => 'lab',
            'assigned_to_user_id' => $outsider->id,
        ])->assertUnprocessable();
    }

    public function test_unassigned_scope_and_status_filters_work(): void
    {
        [$owner, $org, $patient] = $this->makeOrgWithOwnerAndPatient();

        $order = $patient->orders()->create([
            'organization_id' => $org->id,
            'created_by_user_id' => $owner->id,
            'type' => PatientOrder::TYPE_LAB,
            'status' => PatientOrder::STATUS_PENDING,
        ]);
        $patient->orders()->create([
            'organization_id' => $org->id,
            'created_by_user_id' => $owner->id,
            'type' => PatientOrder::TYPE_LAB,
            'status' => PatientOrder::STATUS_CANCELLED,
        ]);

        Sanctum::actingAs($owner);

        $this->getJson("/api/organizations/{$org->id}/orders?scope=unassigned&status=open")
            ->assertOk()
            ->assertJsonCount(1, 'orders')
            ->assertJsonPath('orders.0.id', $order->id);

        $this->getJson("/api/organizations/{$org->id}/orders?status=cancelled")
            ->assertOk()
            ->assertJsonCount(1, 'orders')
            ->assertJsonPath('orders.0.status', 'cancelled');
    }

    public function test_lab_tech_can_claim_and_the_doctor_can_cancel_an_order(): void
    {
        [$owner, $org, $patient] = $this->makeOrgWithOwnerAndPatient();
        $labTech = User::factory()->create(['role' => User::ROLE_STAFF]);
        $org->members()->create([
            'user_id' => $labTech->id,
            'is_owner' => false,
            'abilities' => [OrganizationAbilities::CAN_CREATE_ADD_RESULTS => true, OrganizationAbilities::CAN_CREATE_ORDER => false],
        ]);

        $order = $patient->orders()->create([
            'organization_id' => $org->id,
            'created_by_user_id' => $owner->id,
            'type' => PatientOrder::TYPE_LAB,
            'status' => PatientOrder::STATUS_PENDING,
        ]);

        Sanctum::actingAs($labTech);
        $this->patchJson("/api/organizations/{$org->id}/orders/{$order->id}", [
            'assigned_to_user_id' => $labTech->id,
            'status' => 'in_progress',
        ])->assertOk()->assertJsonPath('order.status', 'in_progress');

        Sanctum::actingAs($owner);
        $this->patchJson("/api/organizations/{$org->id}/orders/{$order->id}", ['status' => 'cancelled'])
            ->assertOk()
            ->assertJsonPath('order.status', 'cancelled');
    }

    public function test_staff_with_neither_ability_cannot_update_an_order(): void
    {
        [$owner, $org, $patient] = $this->makeOrgWithOwnerAndPatient();
        $bystander = User::factory()->create(['role' => User::ROLE_STAFF]);
        $org->members()->create([
            'user_id' => $bystander->id,
            'is_owner' => false,
            'abilities' => [OrganizationAbilities::CAN_CREATE_ORDER => false],
        ]);

        $order = $patient->orders()->create([
            'organization_id' => $org->id,
            'created_by_user_id' => $owner->id,
            'type' => PatientOrder::TYPE_LAB,
        ]);

        Sanctum::actingAs($bystander);
        $this->patchJson("/api/organizations/{$org->id}/orders/{$order->id}", ['status' => 'cancelled'])
            ->assertForbidden();
    }

    public function test_finalizing_a_lab_result_with_order_id_fulfills_the_order(): void
    {
        [$owner, $org, $patient] = $this->makeOrgWithOwnerAndPatient();
        $order = $patient->orders()->create([
            'organization_id' => $org->id,
            'created_by_user_id' => $owner->id,
            'type' => PatientOrder::TYPE_LAB,
        ]);

        Sanctum::actingAs($owner);

        $response = $this->postJson("/api/organizations/{$org->id}/lab-results", [
            'patient_id' => $patient->id,
            'order_id' => $order->id,
            'status' => 'finalized',
            'items' => [
                ['test_name' => 'Glucose, fasting', 'value' => '92', 'status' => 'normal'],
            ],
        ]);
        $response->assertCreated();
        $reportId = $response->json('report.id');

        $this->assertDatabaseHas('patient_orders', [
            'id' => $order->id,
            'status' => 'completed',
            'lab_result_report_id' => $reportId,
        ]);
    }

    public function test_fulfilling_with_mismatched_order_type_leaves_the_order_untouched(): void
    {
        [$owner, $org, $patient] = $this->makeOrgWithOwnerAndPatient();
        $examOrder = $patient->orders()->create([
            'organization_id' => $org->id,
            'created_by_user_id' => $owner->id,
            'type' => PatientOrder::TYPE_EXAMINATION,
        ]);

        Sanctum::actingAs($owner);

        $this->postJson("/api/organizations/{$org->id}/lab-results", [
            'patient_id' => $patient->id,
            'order_id' => $examOrder->id,
            'status' => 'finalized',
            'items' => [
                ['test_name' => 'Glucose, fasting', 'value' => '92', 'status' => 'normal'],
            ],
        ])->assertCreated();

        $this->assertDatabaseHas('patient_orders', [
            'id' => $examOrder->id,
            'status' => 'pending',
            'lab_result_report_id' => null,
        ]);
    }
}
