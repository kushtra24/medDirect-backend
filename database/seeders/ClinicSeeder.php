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
                'type' => Organization::TYPE_CLINIC,
                'description' => 'A full-service diagnostic clinic offering blood work, pathology, and preventive screenings with same-day results for many tests.',
                'neighborhood' => 'Karrada',
                'distance_km' => 0.8,
                'rating' => 4.9,
                'certified' => true,
                'allows_appointments' => true,
                'health_insurances' => ['Iraqi Social Security', 'Al-Amin Insurance', 'National Health Card'],
                'languages_spoken' => ['Arabic', 'English', 'Kurdish'],
                'specialties' => ['Internal Medicine', 'Laboratory Diagnostics', 'Preventive Care'],
                'diplomas' => ['ISO 15189 Accredited Lab', 'Board-Certified Pathologists'],
                'service_slugs' => ['blood-tests', 'dna-analysis', 'pathology', 'covid-testing'],
                'latitude' => 33.3130,
                'longitude' => 44.4346,
                'addresses' => [
                    ['line' => 'Karrada Dakhil Street, near Sadeer Tower, Karrada, Baghdad', 'description' => 'Main entrance & reception', 'is_primary' => true],
                    ['line' => 'Karrada Dakhil Street, Parking Level -1', 'description' => 'Patient drop-off', 'is_primary' => false],
                ],
                'phones' => [
                    ['number' => '+964 770 123 4567', 'label' => 'Reception'],
                    ['number' => '+964 770 123 4568', 'label' => 'Lab desk'],
                ],
            ],
            [
                'slug' => 'karrada-diagnostics-lab',
                'name' => 'Karrada Diagnostics Lab',
                'type' => Organization::TYPE_LAB,
                'description' => 'Independent diagnostics lab a short walk from Demo Clinic & Lab, specializing in fast-turnaround blood work and PCR testing.',
                'neighborhood' => 'Karrada',
                'distance_km' => 0.9,
                'rating' => 4.6,
                'certified' => true,
                'allows_appointments' => true,
                'health_insurances' => ['Iraqi Social Security', 'National Health Card'],
                'languages_spoken' => ['Arabic', 'English'],
                'specialties' => ['Laboratory Diagnostics', 'Molecular Testing'],
                'diplomas' => ['ISO 15189 Accredited Lab'],
                'service_slugs' => ['blood-tests', 'covid-testing', 'dna-analysis'],
                'latitude' => 33.3115,
                'longitude' => 44.4368,
                'addresses' => [
                    ['line' => 'Karrada Kharij, 62nd Street, near Al-Wathba Square, Baghdad', 'description' => 'Sample collection desk', 'is_primary' => true],
                ],
                'phones' => [
                    ['number' => '+964 770 234 5678', 'label' => 'Reception'],
                ],
            ],
            [
                'slug' => 'mercy-general',
                'name' => 'Mercy General Clinic',
                'type' => Organization::TYPE_CLINIC,
                'description' => 'Family medicine and urgent care with extended weekday hours and on-site imaging.',
                'neighborhood' => 'Al-Mansour',
                'distance_km' => 0.8,
                'rating' => 4.9,
                'certified' => true,
                'allows_appointments' => true,
                'health_insurances' => ['Iraqi Social Security', 'Al-Amin Insurance'],
                'languages_spoken' => ['Arabic', 'English'],
                'specialties' => ['Family Medicine', 'Urgent Care'],
                'diplomas' => ['Joint Commission Accredited'],
                'service_slugs' => ['general-checkup', 'vaccination', 'blood-tests'],
                'latitude' => 33.3237,
                'longitude' => 44.3123,
                'addresses' => [
                    ['line' => '14 Ramadan Street, Al-Mansour, Baghdad', 'description' => 'Front desk', 'is_primary' => true],
                ],
                'phones' => [
                    ['number' => '+964 770 555 0100', 'label' => 'Main line'],
                ],
            ],
            [
                'slug' => 'mansour-family-clinic',
                'name' => 'Al-Mansour Family Clinic',
                'type' => Organization::TYPE_CLINIC,
                'description' => 'Neighborhood family clinic a few blocks from Mercy General, offering same-day appointments for common conditions.',
                'neighborhood' => 'Al-Mansour',
                'distance_km' => 1.0,
                'rating' => 4.4,
                'certified' => false,
                'allows_appointments' => true,
                'health_insurances' => ['National Health Card'],
                'languages_spoken' => ['Arabic', 'English'],
                'specialties' => ['Family Medicine', 'Pediatrics'],
                'diplomas' => ['Iraqi Ministry of Health Licensed'],
                'service_slugs' => ['general-checkup', 'vaccination'],
                'latitude' => 33.3250,
                'longitude' => 44.3145,
                'addresses' => [
                    ['line' => 'Street 14, Al-Mansour, near Al-Mansour Mall, Baghdad', 'description' => null, 'is_primary' => true],
                ],
                'phones' => [
                    ['number' => '+964 770 555 0110', 'label' => 'Appointments'],
                ],
            ],
            [
                'slug' => 'wellness-hub',
                'name' => 'Wellness Hub Care',
                'type' => Organization::TYPE_CLINIC,
                'description' => 'Holistic primary care focused on nutrition, chronic disease management, and preventive labs.',
                'neighborhood' => 'Al-Adhamiyah',
                'distance_km' => 1.2,
                'rating' => 4.7,
                'certified' => false,
                'allows_appointments' => true,
                'health_insurances' => ['Al-Amin Insurance'],
                'languages_spoken' => ['Arabic', 'English', 'Kurdish'],
                'specialties' => ['Primary Care', 'Nutrition'],
                'diplomas' => ['DGNB Green Clinic Certification'],
                'service_slugs' => ['general-checkup', 'blood-tests'],
                'latitude' => 33.3667,
                'longitude' => 44.3639,
                'addresses' => [
                    ['line' => 'Al-Imam Al-Adham Street, Al-Adhamiyah, Baghdad', 'description' => null, 'is_primary' => true],
                ],
                'phones' => [
                    ['number' => '+964 770 555 0200', 'label' => 'Appointments'],
                    ['number' => '+964 770 555 0201', 'label' => 'After hours'],
                ],
            ],
            [
                'slug' => 'horizon-health',
                'name' => 'Horizon Health Center',
                'type' => Organization::TYPE_CLINIC,
                'description' => 'Multi-specialty center with cardiology consults and advanced laboratory services.',
                'neighborhood' => 'Jadriya',
                'distance_km' => 2.5,
                'rating' => 4.8,
                'certified' => true,
                'allows_appointments' => true,
                'health_insurances' => ['Iraqi Social Security', 'Al-Amin Insurance', 'National Health Card'],
                'languages_spoken' => ['Arabic', 'English', 'French'],
                'specialties' => ['Cardiology', 'Laboratory', 'Radiology'],
                'diplomas' => ['European Society of Cardiology Partner Clinic'],
                'service_slugs' => ['blood-tests', 'pathology', 'general-checkup'],
                'latitude' => 33.2788,
                'longitude' => 44.3676,
                'addresses' => [
                    ['line' => 'Al-Jadriya, near Baghdad University Bridge, Baghdad', 'description' => 'Cardiology wing', 'is_primary' => true],
                    ['line' => 'Al-Jadriya, Building A, Baghdad', 'description' => 'Laboratory entrance', 'is_primary' => false],
                ],
                'phones' => [
                    ['number' => '+964 770 555 0300', 'label' => 'Reception'],
                ],
            ],
            [
                'slug' => 'city-life-medical',
                'name' => 'City Life Medical',
                'type' => Organization::TYPE_CLINIC,
                'description' => 'Walk-in friendly clinic for travelers and locals with rapid COVID and flu testing.',
                'neighborhood' => 'Zayouna',
                'distance_km' => 3.1,
                'rating' => 4.5,
                'certified' => false,
                'allows_appointments' => false,
                'health_insurances' => ['Private pay'],
                'languages_spoken' => ['Arabic', 'English', 'Spanish'],
                'specialties' => ['Travel Medicine', 'Infectious Disease Screening'],
                'diplomas' => ['WHO Travel Clinic Network'],
                'service_slugs' => ['covid-testing', 'vaccination', 'general-checkup'],
                'latitude' => 33.3378,
                'longitude' => 44.4544,
                'addresses' => [
                    ['line' => 'Zayouna Square, 5th Street, Baghdad', 'description' => 'Walk-in entrance', 'is_primary' => true],
                ],
                'phones' => [
                    ['number' => '+964 770 555 0400', 'label' => 'Walk-in desk'],
                ],
            ],
        ];

        foreach ($clinics as $data) {
            $org = Organization::query()->updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'type' => $data['type'],
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
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
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
