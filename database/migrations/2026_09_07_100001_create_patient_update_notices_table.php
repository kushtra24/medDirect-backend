<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_update_notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('changed_fields');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_update_notices');
    }
};
