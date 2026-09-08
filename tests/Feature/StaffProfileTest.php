<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StaffProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_member_can_update_their_own_name(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF, 'name' => 'Original Name']);

        Sanctum::actingAs($staff);

        $response = $this->patchJson('/api/user/profile', ['name' => 'Updated Name']);

        $response->assertOk()->assertJsonPath('user.name', 'Updated Name');
        $this->assertDatabaseHas('users', ['id' => $staff->id, 'name' => 'Updated Name']);
    }

    public function test_owner_can_set_their_own_title(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_STAFF]);
        $org = Organization::query()->create([
            'name' => 'Owner Clinic',
            'slug' => 'owner-clinic',
            'type' => Organization::TYPE_CLINIC,
            'created_by_user_id' => $owner->id,
        ]);
        $org->members()->create([
            'user_id' => $owner->id,
            'is_owner' => true,
            'abilities' => null,
        ]);

        Sanctum::actingAs($owner);

        $response = $this->patchJson("/api/organizations/{$org->id}/membership/title", [
            'title' => 'Clinic Director',
        ]);

        $response->assertOk()->assertJsonPath('title', 'Clinic Director');
        $this->assertDatabaseHas('organization_members', [
            'organization_id' => $org->id,
            'user_id' => $owner->id,
            'title' => 'Clinic Director',
        ]);

        $userResponse = $this->getJson('/api/user');
        $userResponse->assertOk()
            ->assertJsonPath('user.organizations.0.title', 'Clinic Director');
    }

    public function test_staff_without_manage_staff_ability_cannot_set_their_own_title(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_STAFF]);
        $org = Organization::query()->create([
            'name' => 'Restricted Clinic',
            'slug' => 'restricted-clinic',
            'type' => Organization::TYPE_CLINIC,
            'created_by_user_id' => $owner->id,
        ]);
        $org->members()->create(['user_id' => $owner->id, 'is_owner' => true, 'abilities' => null]);

        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $org->members()->create([
            'user_id' => $staff->id,
            'is_owner' => false,
            'abilities' => ['canManageStaff' => false],
        ]);

        Sanctum::actingAs($staff);

        $this->patchJson("/api/organizations/{$org->id}/membership/title", ['title' => 'Self-Appointed Lead'])
            ->assertForbidden();
    }
}
