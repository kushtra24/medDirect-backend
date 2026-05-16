<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->text('description')->nullable()->after('type');
            $table->json('opening_hours')->nullable()->after('description');
            $table->json('health_insurances')->nullable()->after('opening_hours');
            $table->json('languages_spoken')->nullable()->after('health_insurances');
            $table->json('specialties')->nullable()->after('languages_spoken');
            $table->json('diplomas')->nullable()->after('specialties');
            $table->boolean('allows_appointments')->default(true)->after('diplomas');
            $table->boolean('certified')->default(false)->after('allows_appointments');
            $table->decimal('rating', 2, 1)->nullable()->after('certified');
            $table->string('neighborhood')->nullable()->after('rating');
            $table->decimal('distance_km', 5, 1)->nullable()->after('neighborhood');
            $table->decimal('latitude', 10, 7)->nullable()->after('distance_km');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });

        Schema::create('clinic_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('line');
            $table->string('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        Schema::create('clinic_phones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('number');
            $table->string('label')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('clinic_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->text('url');
            $table->string('type', 32);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('clinic_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_service');
        Schema::dropIfExists('clinic_images');
        Schema::dropIfExists('clinic_phones');
        Schema::dropIfExists('clinic_addresses');

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
};
