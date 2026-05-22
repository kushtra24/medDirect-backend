<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('first_name');
            $table->string('surname');
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 16)->nullable();
            $table->string('national_id', 64)->nullable();
            $table->string('insurance_provider', 64)->nullable();
            $table->string('policy_number', 64)->nullable();
            $table->string('blood_group', 8)->nullable();
            $table->text('allergies')->nullable();
            $table->text('chronic_conditions')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('email')->nullable();
            $table->string('access_pin')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();

            $table->index(['email', 'date_of_birth']);
            $table->index(['national_id', 'date_of_birth']);
            $table->index('user_id');
        });

        Schema::create('lab_result_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('doctor_note')->nullable();
            $table->string('status', 16)->default('draft');
            $table->timestamp('reported_at')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'organization_id']);
        });

        Schema::create('lab_result_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_result_report_id')->constrained()->cascadeOnDelete();
            $table->string('test_name');
            $table->string('value')->nullable();
            $table->string('status', 16)->default('normal');
            $table->string('reference_point')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_result_items');
        Schema::dropIfExists('lab_result_reports');
        Schema::dropIfExists('patients');
    }
};
