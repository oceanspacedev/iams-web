<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE `findings` MODIFY COLUMN `severity` ENUM('MINOR', 'MEDIUM', 'MAJOR', 'CRITICAL', 'OBSERVATION', 'CONTINUOUS') NOT NULL DEFAULT 'MINOR'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `findings` MODIFY COLUMN `severity` ENUM('MINOR', 'MEDIUM', 'MAJOR', 'CRITICAL', 'OBSERVATION') NOT NULL DEFAULT 'MINOR'");
    }
};
