<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable()->after('password');
            $table->string('blood_type', 16)->nullable()->after('date_of_birth');
            $table->string('phone', 32)->nullable()->after('blood_type');
            $table->text('address')->nullable()->after('phone');
            $table->string('avatar_path', 512)->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['date_of_birth', 'blood_type', 'phone', 'address', 'avatar_path']);
        });
    }
};
