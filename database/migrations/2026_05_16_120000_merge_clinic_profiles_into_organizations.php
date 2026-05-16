<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addProfileColumnsToOrganizations();

        if (! Schema::hasTable('clinic_profiles')) {
            return;
        }

        $rows = DB::table('clinic_profiles')->get();

        foreach ($rows as $row) {
            DB::table('organizations')
                ->where('id', $row->organization_id)
                ->update([
                    'description' => $row->description,
                    'opening_hours' => $row->opening_hours,
                    'health_insurances' => $row->health_insurances,
                    'languages_spoken' => $row->languages_spoken,
                    'specialties' => $row->specialties,
                    'diplomas' => $row->diplomas,
                    'allows_appointments' => $row->allows_appointments,
                    'certified' => $row->certified,
                    'rating' => $row->rating,
                    'neighborhood' => $row->neighborhood,
                    'distance_km' => $row->distance_km,
                    'latitude' => $row->latitude,
                    'longitude' => $row->longitude,
                ]);
        }

        Schema::dropIfExists('clinic_profiles');
    }

    public function down(): void
    {
        if (! Schema::hasColumn('organizations', 'description')) {
            return;
        }

        Schema::create('clinic_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->json('opening_hours')->nullable();
            $table->json('health_insurances')->nullable();
            $table->json('languages_spoken')->nullable();
            $table->json('specialties')->nullable();
            $table->json('diplomas')->nullable();
            $table->boolean('allows_appointments')->default(true);
            $table->boolean('certified')->default(false);
            $table->decimal('rating', 2, 1)->nullable();
            $table->string('neighborhood')->nullable();
            $table->decimal('distance_km', 5, 1)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
        });

        $organizations = DB::table('organizations')
            ->whereNotNull('description')
            ->get();

        foreach ($organizations as $org) {
            DB::table('clinic_profiles')->insert([
                'organization_id' => $org->id,
                'description' => $org->description,
                'opening_hours' => $org->opening_hours,
                'health_insurances' => $org->health_insurances,
                'languages_spoken' => $org->languages_spoken,
                'specialties' => $org->specialties,
                'diplomas' => $org->diplomas,
                'allows_appointments' => $org->allows_appointments,
                'certified' => $org->certified,
                'rating' => $org->rating,
                'neighborhood' => $org->neighborhood,
                'distance_km' => $org->distance_km,
                'latitude' => $org->latitude,
                'longitude' => $org->longitude,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'description',
                'opening_hours',
                'health_insurances',
                'languages_spoken',
                'specialties',
                'diplomas',
                'allows_appointments',
                'certified',
                'rating',
                'neighborhood',
                'distance_km',
                'latitude',
                'longitude',
            ]);
        });
    }

    private function addProfileColumnsToOrganizations(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            if (! Schema::hasColumn('organizations', 'description')) {
                $table->text('description')->nullable()->after('type');
            }
            if (! Schema::hasColumn('organizations', 'opening_hours')) {
                $table->json('opening_hours')->nullable()->after('description');
            }
            if (! Schema::hasColumn('organizations', 'health_insurances')) {
                $table->json('health_insurances')->nullable()->after('opening_hours');
            }
            if (! Schema::hasColumn('organizations', 'languages_spoken')) {
                $table->json('languages_spoken')->nullable()->after('health_insurances');
            }
            if (! Schema::hasColumn('organizations', 'specialties')) {
                $table->json('specialties')->nullable()->after('languages_spoken');
            }
            if (! Schema::hasColumn('organizations', 'diplomas')) {
                $table->json('diplomas')->nullable()->after('specialties');
            }
            if (! Schema::hasColumn('organizations', 'allows_appointments')) {
                $table->boolean('allows_appointments')->default(true)->after('diplomas');
            }
            if (! Schema::hasColumn('organizations', 'certified')) {
                $table->boolean('certified')->default(false)->after('allows_appointments');
            }
            if (! Schema::hasColumn('organizations', 'rating')) {
                $table->decimal('rating', 2, 1)->nullable()->after('certified');
            }
            if (! Schema::hasColumn('organizations', 'neighborhood')) {
                $table->string('neighborhood')->nullable()->after('rating');
            }
            if (! Schema::hasColumn('organizations', 'distance_km')) {
                $table->decimal('distance_km', 5, 1)->nullable()->after('neighborhood');
            }
            if (! Schema::hasColumn('organizations', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('distance_km');
            }
            if (! Schema::hasColumn('organizations', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
        });
    }
};
