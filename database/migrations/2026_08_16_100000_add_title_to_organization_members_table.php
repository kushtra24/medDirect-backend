<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_members', function (Blueprint $table) {
            $table->string('title', 120)->nullable()->after('is_owner');
        });
    }

    public function down(): void
    {
        Schema::table('organization_members', function (Blueprint $table) {
            $table->dropColumn('title');
        });
    }
};
