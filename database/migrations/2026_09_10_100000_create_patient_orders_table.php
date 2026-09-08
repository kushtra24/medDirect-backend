<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 16);
            $table->string('status', 16)->default('pending');
            $table->text('reason')->nullable();
            $table->foreignId('lab_result_report_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('examination_result_report_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['assigned_to_user_id', 'status']);
            $table->index(['patient_id', 'organization_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_orders');
    }
};
