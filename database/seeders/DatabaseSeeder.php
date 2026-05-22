<?php

namespace Database\Seeders;

use App\Authorization\OrganizationAbilities;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@med-direct.local'],
            [
                'name' => 'Platform Admin',
                'email_verified_at' => now(),
                'password' => 'password',
            ],
        );
        $admin->role = User::ROLE_ADMIN;
        $admin->save();

        $patient = User::query()->updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'email_verified_at' => now(),
                'password' => 'password',
            ],
        );
        $patient->role = User::ROLE_PATIENT;
        $patient->save();

        $owner = User::query()->updateOrCreate(
            ['email' => 'owner@demo-clinic.local'],
            [
                'name' => 'Clinic Owner',
                'email_verified_at' => now(),
                'password' => 'password',
            ],
        );
        $owner->role = User::ROLE_STAFF;
        $owner->save();

        $technician = User::query()->updateOrCreate(
            ['email' => 'tech@demo-clinic.local'],
            [
                'name' => 'Lab Technician',
                'email_verified_at' => now(),
                'password' => 'password',
            ],
        );
        $technician->role = User::ROLE_STAFF;
        $technician->save();

        $org = Organization::query()->updateOrCreate(
            ['slug' => 'demo-clinic'],
            [
                'name' => 'Demo Clinic & Lab',
                'type' => Organization::TYPE_CLINIC,
                'created_by_user_id' => $owner->id,
            ],
        );

        OrganizationMember::query()->updateOrCreate(
            [
                'organization_id' => $org->id,
                'user_id' => $owner->id,
            ],
            [
                'is_owner' => true,
                'abilities' => null,
            ],
        );

        OrganizationMember::query()->updateOrCreate(
            [
                'organization_id' => $org->id,
                'user_id' => $technician->id,
            ],
            [
                'is_owner' => false,
                'abilities' => [
                    OrganizationAbilities::CAN_VIEW_PATIENTS => true,
                    OrganizationAbilities::CAN_EDIT_LAB_RESULTS => true,
                    OrganizationAbilities::CAN_CREATE_ADD_RESULTS => true,
                ],
            ],
        );

        $this->call(ClinicSeeder::class);
        $this->call(PatientSeeder::class);
    }
}
