<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examination_result_reports', function (Blueprint $table) {
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

        Schema::create('examination_result_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examination_result_report_id')->constrained()->cascadeOnDelete();
            $table->string('finding_name');
            $table->string('value')->nullable();
            $table->string('status', 16)->default('normal');
            $table->string('reference_point')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examination_result_items');
        Schema::dropIfExists('examination_result_reports');
    }
};