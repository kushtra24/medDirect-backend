<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE patients MODIFY access_pin TEXT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE patients MODIFY access_pin VARCHAR(255) NULL');
    }
};
