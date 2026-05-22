<?php

namespace Database\Seeders;

use App\Models\LabResultItem;
use App\Models\LabResultReport;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Seeder;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::query()->where('slug', 'demo-clinic')->first();
        $staff = User::query()->where('email', 'owner@demo-clinic.local')->first();

        if ($org === null || $staff === null) {
            return;
        }

        $patient = Patient::query()->updateOrCreate(
            [
                'organization_id' => $org->id,
                'email' => 'test@example.com',
            ],
            [
                'created_by_user_id' => $staff->id,
                'first_name' => 'Test',
                'surname' => 'User',
                'date_of_birth' => '1990-05-15',
                'gender' => 'male',
                'blood_group' => 'O+',
                'phone' => '+38344123456',
                'access_pin' => '482910',
            ],
        );

        $report = LabResultReport::query()->updateOrCreate(
            [
                'patient_id' => $patient->id,
                'organization_id' => $org->id,
                'status' => LabResultReport::STATUS_FINALIZED,
            ],
            [
                'created_by_user_id' => $staff->id,
                'doctor_note' => 'Fasting glucose elevated; recommend follow-up.',
                'reported_at' => now()->subDays(3),
            ],
        );

        $report->items()->delete();

        LabResultItem::query()->create([
            'lab_result_report_id' => $report->id,
            'test_name' => 'Glucose, fasting',
            'value' => '118',
            'status' => LabResultItem::STATUS_HIGH,
            'reference_point' => '< 100 mg/dL',
            'sort_order' => 0,
        ]);

        LabResultItem::query()->create([
            'lab_result_report_id' => $report->id,
            'test_name' => 'HbA1c',
            'value' => '5.4',
            'status' => LabResultItem::STATUS_NORMAL,
            'reference_point' => '< 5.7 %',
            'sort_order' => 1,
        ]);
    }
}
