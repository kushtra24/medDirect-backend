<?php

namespace Tests\Feature;

use App\Models\ClinicAddress;
use App\Models\Organization;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_keyword_search_ranks_higher_occurrence_first(): void
    {
        $blood = Service::query()->create([
            'slug' => 'blood-tests',
            'name' => 'Blood Tests',
            'icon' => 'droplet',
            'icon_color' => 'text-secondary',
        ]);

        $low = Organization::query()->create([
            'name' => 'Alpha Clinic',
            'slug' => 'alpha-clinic',
            'type' => Organization::TYPE_CLINIC,
            'description' => 'General care with blood work available.',
        ]);

        $high = Organization::query()->create([
            'name' => 'Blood Blood Lab',
            'slug' => 'blood-blood-lab',
            'type' => Organization::TYPE_LAB,
            'description' => 'Blood diagnostics and blood screening.',
            'specialties' => ['Blood Pathology'],
        ]);

        $high->services()->attach($blood);

        $response = $this->getJson('/api/med?q=blood');

        $response->assertOk();
        $slugs = collect($response->json('institutes'))->pluck('slug')->all();

        $this->assertSame(['blood-blood-lab', 'alpha-clinic'], $slugs);
    }

    public function test_city_filter_matches_neighborhood_and_address(): void
    {
        Organization::query()->create([
            'name' => 'Downtown Clinic',
            'slug' => 'downtown-clinic',
            'type' => Organization::TYPE_CLINIC,
            'description' => 'City center care.',
            'neighborhood' => 'Downtown',
        ]);

        $north = Organization::query()->create([
            'name' => 'North Clinic',
            'slug' => 'north-clinic',
            'type' => Organization::TYPE_CLINIC,
            'description' => 'North side care.',
            'neighborhood' => 'North Side',
        ]);

        ClinicAddress::query()->create([
            'organization_id' => $north->id,
            'line' => '5 Riverside Ave, North Side',
            'is_primary' => true,
        ]);

        $response = $this->getJson('/api/med?city=downtown');

        $response->assertOk();
        $this->assertCount(1, $response->json('institutes'));
        $this->assertSame('downtown-clinic', $response->json('institutes.0.slug'));
    }
}
