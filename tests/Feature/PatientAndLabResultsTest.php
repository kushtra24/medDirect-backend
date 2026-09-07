<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PatientAndLabResultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_create_patient_for_organization(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $org = Organization::query()->create([
            'name' => 'Test Clinic',
            'slug' => 'test-clinic',
            'type' => Organization::TYPE_CLINIC,
            'created_by_user_id' => $staff->id,
        ]);
        $org->members()->create([
            'user_id' => $staff->id,
            'is_owner' => true,
            'abilities' => null,
        ]);

        Sanctum::actingAs($staff);

        $response = $this->postJson("/api/organizations/{$org->id}/patients", [
            'first_name' => 'Jane',
            'surname' => 'Doe',
            'email' => 'jane@example.com',
            'date_of_birth' => '1985-01-20',
            'gender' => 'female',
        ]);

        $response->assertCreated()
            ->assertJsonPath('patient.first_name', 'Jane')
            ->assertJsonPath('patient.organization_id', $org->id)
            ->assertJsonStructure(['access_pin']);

        $this->assertDatabaseHas('patients', [
            'organization_id' => $org->id,
            'email' => 'jane@example.com',
        ]);
    }

    public function test_patient_registration_claims_matching_records(): void
    {
        $org = Organization::query()->create([
            'name' => 'Lab',
            'slug' => 'lab-x',
            'type' => Organization::TYPE_LAB,
        ]);

        Patient::query()->create([
            'organization_id' => $org->id,
            'first_name' => 'Alex',
            'surname' => 'Rivera',
            'email' => 'alex@example.com',
            'date_of_birth' => '1992-03-10',
        ]);

        $response = $this->postJson('/api/register', [
            'name' => 'Alex',
            'surname' => 'Rivera',
            'email' => 'alex@example.com',
            'password' => 'password123',
            'date_of_birth' => '1992-03-10',
        ]);

        $response->assertCreated()
            ->assertJsonPath('claimed_records', 1);

        $userId = User::query()->where('email', 'alex@example.com')->value('id');

        $this->assertDatabaseHas('patients', [
            'email' => 'alex@example.com',
            'user_id' => $userId,
        ]);
    }

    public function test_staff_can_search_patients_by_criteria(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $org = Organization::query()->create([
            'name' => 'Search Lab',
            'slug' => 'search-lab',
            'type' => Organization::TYPE_LAB,
            'created_by_user_id' => $staff->id,
        ]);
        $org->members()->create([
            'user_id' => $staff->id,
            'is_owner' => true,
            'abilities' => null,
        ]);

        Patient::query()->create([
            'organization_id' => $org->id,
            'first_name' => 'Jane',
            'surname' => 'Doe',
            'date_of_birth' => '1985-01-20',
            'gender' => 'female',
        ]);
        Patient::query()->create([
            'organization_id' => $org->id,
            'first_name' => 'John',
            'surname' => 'Smith',
            'gender' => 'male',
        ]);

        Sanctum::actingAs($staff);

        $response = $this->getJson("/api/organizations/{$org->id}/patients?".http_build_query([
            'first_name' => 'Jan',
            'surname' => 'Doe',
            'gender' => 'female',
        ]));

        $response->assertOk()
            ->assertJsonCount(1, 'patients')
            ->assertJsonPath('patients.0.first_name', 'Jane')
            ->assertJsonPath('patients.0.surname', 'Doe');
    }

    public function test_staff_can_import_patients_from_csv(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $org = Organization::query()->create([
            'name' => 'Import Clinic',
            'slug' => 'import-clinic',
            'type' => Organization::TYPE_CLINIC,
            'created_by_user_id' => $staff->id,
        ]);
        $org->members()->create([
            'user_id' => $staff->id,
            'is_owner' => true,
            'abilities' => null,
        ]);

        $csv = <<<'CSV'
        first_name,surname,date_of_birth,gender,email
        Jane,Doe,1985-01-20,female,jane@example.com
        John,Smith,1990-05-14,male,john@example.com
        ,Missing First Name,1990-01-01,male,noname@example.com
        CSV;

        $file = UploadedFile::fake()->createWithContent('patients.csv', $csv);

        Sanctum::actingAs($staff);

        $response = $this->post("/api/organizations/{$org->id}/patients/import", [
            'file' => $file,
        ], ['Accept' => 'application/json']);

        $response->assertOk()
            ->assertJsonPath('imported', 2)
            ->assertJsonPath('skipped', 1)
            ->assertJsonPath('total', 3)
            ->assertJsonPath('errors.0.row', 4);

        $this->assertDatabaseHas('patients', [
            'organization_id' => $org->id,
            'email' => 'jane@example.com',
        ]);
        $this->assertDatabaseHas('patients', [
            'organization_id' => $org->id,
            'email' => 'john@example.com',
        ]);
        $this->assertDatabaseMissing('patients', [
            'organization_id' => $org->id,
            'email' => 'noname@example.com',
        ]);
    }

    public function test_staff_can_add_lab_results_for_patient(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $org = Organization::query()->create([
            'name' => 'Lab Org',
            'slug' => 'lab-org',
            'type' => Organization::TYPE_LAB,
            'created_by_user_id' => $staff->id,
        ]);
        $org->members()->create([
            'user_id' => $staff->id,
            'is_owner' => true,
            'abilities' => null,
        ]);

        $patient = Patient::query()->create([
            'organization_id' => $org->id,
            'first_name' => 'Sam',
            'surname' => 'Lee',
        ]);

        Sanctum::actingAs($staff);

        $response = $this->postJson("/api/organizations/{$org->id}/lab-results", [
            'patient_id' => $patient->id,
            'doctor_note' => 'Routine panel.',
            'status' => 'finalized',
            'items' => [
                [
                    'test_name' => 'Glucose, fasting',
                    'value' => '92',
                    'status' => 'normal',
                    'reference_point' => '< 100 mg/dL',
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('report.patient_id', $patient->id);

        $this->assertDatabaseCount('lab_result_items', 1);
    }
}
