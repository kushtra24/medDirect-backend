<?php

namespace Database\Seeders;

use App\Models\ClinicAddress;
use App\Models\ClinicImage;
use App\Models\ClinicPhone;
use App\Models\Organization;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClinicSeeder extends Seeder
{
    private const IMG_CARD = 'https://lh3.googleusercontent.com/aida-public/AB6AXuCxYtcVZLS7fIvBemRINhlSF5Juhm271599O4ury3vdtYb_4PUe3gxqINlrvuiKn8B7gCbOIu8XXpPevsGOdSXY2IP3eNsbow5tB7plDl-suUCUtGeZLiO7WJJNC_T682c29vgyFOxKeoSHyLTb1_mQ_xD1SakPzKCIIi_KHVDVQZPAFyZv9gab8vNz3bPCAHDVAvW4sN9U-1djS-YcNygPokSwpRL6BVikaKzdOAcsNMJbo_qNdCGvyJth2_gi7xCo9IVd1X13nAs';

    private const IMG_HERO = 'https://lh3.googleusercontent.com/aida-public/AB6AXuCppFH1yhJ_wOWmK-7x9MitDrsq4NPdTakSSPQHAfW0Qxvm3Sa72WgJjg-jTfBxlb-EJ6ZoLbl0i-LkMOrecl74IfB6qIw3-Q9e5Mm7-amBbbUk64lS8tBFA3vA2K5Gj_ktBOSTLZgFBNZXAr92uWIass1S2A-4sP09Q5e-aG7kpczLQ6Na_zuPpoDhghUdLEntOV8nEy9s7XKty8iX8DdChTDJK8c7BMWaAweD7w2k5OK4uNiXSo64HpNDO8g7G8hmDLJLLWzDWy8';

    private const IMG_LOGO = 'https://lh3.googleusercontent.com/aida-public/AB6AXuDFaAofXuyaV7ORARWOR0qNttT1cGqFZ1N9O55nMYJsuYjWGvqO-7Jb3FmtbJ9NXf7Q2RQXAjLWHEx1sA6a9mmYJxaNwpRREo4wh7PRUThPxi3jDSTqa_1P1_9iQNH12wSfZTtrMMOGs-mH5TPuisnxv45e90YoOICyzudVNCJYBM6jJwZUT3wGZml-kvEi10FCZv_0znx5rCzzjnehFAReGtx32Gn-W4yvCxOyEvSgDQowIm95_umJAVUojC0L8p3GYvcE71ATYB8';

    private const IMG_MAP = 'https://lh3.googleusercontent.com/aida-public/AB6AXuCSf5BBdsYwPReDnSsJr3dVDcf1nxMr-qZEN17vZcmlTR2Be90xNrb7FqyokhSwfi8ZAe7yHondfISRZomYnF3RwnOw2WONWF4KUDJZTSJpu-7-_d5ODfbkKubQDyCxwq5iV_HkylUyTyyCxGdfGkGKJjmi303MhN8Fs8tGMInjBrMD0sFNI8LrlM-B5taegpg9iea1GCw84gPg9JiZIMXPPrCiUbER1WEQrXPF8wgbqtp57HTssKqPg0VWiG8yGj9ETUUPWNA50m4';

    public function run(): void
    {
        $owner = User::query()->where('email', 'owner@demo-clinic.local')->first();

        $services = collect([
            ['slug' => 'blood-tests', 'name' => 'Blood Tests', 'icon' => 'droplet', 'icon_color' => 'text-secondary'],
            ['slug' => 'dna-analysis', 'name' => 'DNA Analysis', 'icon' => 'microscope', 'icon_color' => 'text-tertiary'],
            ['slug' => 'pathology', 'name' => 'Pathology', 'icon' => 'magnifying-glass', 'icon_color' => 'text-secondary'],
            ['slug' => 'covid-testing', 'name' => 'COVID Testing', 'icon' => 'virus', 'icon_color' => 'text-error'],
            ['slug' => 'general-checkup', 'name' => 'General Check-up', 'icon' => 'stethoscope', 'icon_color' => 'text-secondary'],
            ['slug' => 'vaccination', 'name' => 'Vaccination', 'icon' => 'syringe', 'icon_color' => 'text-tertiary'],
        ])->mapWithKeys(fn (array $row) => [
            $row['slug'] => Service::query()->updateOrCreate(['slug' => $row['slug']], $row),
        ]);

        $weekdayHours = [
            ['days' => 'Mon–Fri', 'time' => '8:00 AM – 6:00 PM', 'closed' => false, 'highlight' => false],
            ['days' => 'Saturday', 'time' => '9:00 AM – 1:00 PM', 'closed' => false, 'highlight' => false],
            ['days' => 'Sunday', 'time' => 'Closed', 'closed' => true, 'highlight' => true],
        ];

        $clinics = [
            [
                'slug' => 'demo-clinic',
                'name' => 'Demo Clinic & Lab',
                'description' => 'A full-service diagnostic clinic offering blood work, pathology, and preventive screenings with same-day results for many tests.',
                'neighborhood' => 'Medical District',
                'distance_km' => 0.8,
                'rating' => 4.9,
                'certified' => true,
                'allows_appointments' => true,
                'health_insurances' => ['AOK', 'TK', 'Barmer', 'DAK'],
                'languages_spoken' => ['English', 'German', 'Arabic'],
                'specialties' => ['Internal Medicine', 'Laboratory Diagnostics', 'Preventive Care'],
                'diplomas' => ['ISO 15189 Accredited Lab', 'Board-Certified Pathologists'],
                'service_slugs' => ['blood-tests', 'dna-analysis', 'pathology', 'covid-testing'],
                'addresses' => [
                    ['line' => '42 Wellness Blvd, Medical District', 'description' => 'Main entrance & reception', 'is_primary' => true],
                    ['line' => 'Parking Garage B, Level 2', 'description' => 'Patient drop-off', 'is_primary' => false],
                ],
                'phones' => [
                    ['number' => '+49 30 1234 5678', 'label' => 'Reception'],
                    ['number' => '+49 30 1234 5679', 'label' => 'Lab desk'],
                ],
            ],
            [
                'slug' => 'mercy-general',
                'name' => 'Mercy General Clinic',
                'description' => 'Family medicine and urgent care with extended weekday hours and on-site imaging.',
                'neighborhood' => 'Downtown',
                'distance_km' => 0.8,
                'rating' => 4.9,
                'certified' => true,
                'allows_appointments' => true,
                'health_insurances' => ['AOK', 'IKK classic', 'BARMER'],
                'languages_spoken' => ['English', 'German'],
                'specialties' => ['Family Medicine', 'Urgent Care'],
                'diplomas' => ['Joint Commission Accredited'],
                'service_slugs' => ['general-checkup', 'vaccination', 'blood-tests'],
                'addresses' => [
                    ['line' => '100 Main Street, Downtown', 'description' => 'Front desk', 'is_primary' => true],
                ],
                'phones' => [
                    ['number' => '+49 30 555 0100', 'label' => 'Main line'],
                ],
            ],
            [
                'slug' => 'wellness-hub',
                'name' => 'Wellness Hub Care',
                'description' => 'Holistic primary care focused on nutrition, chronic disease management, and preventive labs.',
                'neighborhood' => 'North Side',
                'distance_km' => 1.2,
                'rating' => 4.7,
                'certified' => false,
                'allows_appointments' => true,
                'health_insurances' => ['TK', 'DAK'],
                'languages_spoken' => ['English', 'German', 'Kurdish'],
                'specialties' => ['Primary Care', 'Nutrition'],
                'diplomas' => ['DGNB Green Clinic Certification'],
                'service_slugs' => ['general-checkup', 'blood-tests'],
                'addresses' => [
                    ['line' => '18 Green Park Ave, North Side', 'description' => null, 'is_primary' => true],
                ],
                'phones' => [
                    ['number' => '+49 30 555 0200', 'label' => 'Appointments'],
                    ['number' => '+49 30 555 0201', 'label' => 'After hours'],
                ],
            ],
            [
                'slug' => 'horizon-health',
                'name' => 'Horizon Health Center',
                'description' => 'Multi-specialty center with cardiology consults and advanced laboratory services.',
                'neighborhood' => 'Riverside',
                'distance_km' => 2.5,
                'rating' => 4.8,
                'certified' => true,
                'allows_appointments' => true,
                'health_insurances' => ['AOK', 'Barmer', 'HEK'],
                'languages_spoken' => ['English', 'German', 'French'],
                'specialties' => ['Cardiology', 'Laboratory', 'Radiology'],
                'diplomas' => ['European Society of Cardiology Partner Clinic'],
                'service_slugs' => ['blood-tests', 'pathology', 'general-checkup'],
                'addresses' => [
                    ['line' => '5 Riverside Quay, Building C', 'description' => 'Cardiology wing', 'is_primary' => true],
                    ['line' => '5 Riverside Quay, Building A', 'description' => 'Laboratory entrance', 'is_primary' => false],
                ],
                'phones' => [
                    ['number' => '+49 30 555 0300', 'label' => 'Reception'],
                ],
            ],
            [
                'slug' => 'city-life-medical',
                'name' => 'City Life Medical',
                'description' => 'Walk-in friendly clinic for travelers and locals with rapid COVID and flu testing.',
                'neighborhood' => 'East End',
                'distance_km' => 3.1,
                'rating' => 4.5,
                'certified' => false,
                'allows_appointments' => false,
                'health_insurances' => ['Private pay', 'Allianz', 'AXA'],
                'languages_spoken' => ['English', 'German', 'Spanish'],
                'specialties' => ['Travel Medicine', 'Infectious Disease Screening'],
                'diplomas' => ['WHO Travel Clinic Network'],
                'service_slugs' => ['covid-testing', 'vaccination', 'general-checkup'],
                'addresses' => [
                    ['line' => '220 East End Plaza, Suite 4', 'description' => 'Walk-in entrance', 'is_primary' => true],
                ],
                'phones' => [
                    ['number' => '+49 30 555 0400', 'label' => 'Walk-in desk'],
                ],
            ],
        ];

        foreach ($clinics as $index => $data) {
            $org = Organization::query()->updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'type' => Organization::TYPE_CLINIC,
                    'created_by_user_id' => $owner?->id,
                    'description' => $data['description'],
                    'opening_hours' => $weekdayHours,
                    'health_insurances' => $data['health_insurances'],
                    'languages_spoken' => $data['languages_spoken'],
                    'specialties' => $data['specialties'],
                    'diplomas' => $data['diplomas'],
                    'allows_appointments' => $data['allows_appointments'],
                    'certified' => $data['certified'],
                    'rating' => $data['rating'],
                    'neighborhood' => $data['neighborhood'],
                    'distance_km' => $data['distance_km'],
                    'latitude' => 52.52 + ($index * 0.01),
                    'longitude' => 13.405 + ($index * 0.01),
                ],
            );

            $org->clinicAddresses()->delete();
            foreach ($data['addresses'] as $sort => $address) {
                ClinicAddress::query()->create([
                    'organization_id' => $org->id,
                    'line' => $address['line'],
                    'description' => $address['description'],
                    'sort_order' => $sort,
                    'is_primary' => $address['is_primary'],
                ]);
            }

            $org->clinicPhones()->delete();
            foreach ($data['phones'] as $sort => $phone) {
                ClinicPhone::query()->create([
                    'organization_id' => $org->id,
                    'number' => $phone['number'],
                    'label' => $phone['label'],
                    'sort_order' => $sort,
                ]);
            }

            $org->clinicImages()->delete();
            foreach (
                [
                    ['type' => ClinicImage::TYPE_CARD, 'url' => self::IMG_CARD],
                    ['type' => ClinicImage::TYPE_HERO, 'url' => self::IMG_HERO],
                    ['type' => ClinicImage::TYPE_LOGO, 'url' => self::IMG_LOGO],
                    ['type' => ClinicImage::TYPE_MAP, 'url' => self::IMG_MAP],
                ] as $sort => $image
            ) {
                ClinicImage::query()->create([
                    'organization_id' => $org->id,
                    'type' => $image['type'],
                    'url' => $image['url'],
                    'sort_order' => $sort,
                ]);
            }

            $serviceIds = collect($data['service_slugs'])
                ->map(fn (string $slug) => $services[$slug]->id)
                ->all();
            $org->services()->sync($serviceIds);
        }
    }
}
