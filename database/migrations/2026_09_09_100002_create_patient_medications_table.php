<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_medications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('dosage')->nullable();
            $table->string('frequency')->nullable();
            $table->string('route', 32)->nullable();
            $table->string('status', 16)->default('active');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('prescriber')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'organization_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_medications');
    }
};
