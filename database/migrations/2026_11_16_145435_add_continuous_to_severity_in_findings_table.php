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
        // On other drivers (SQLite for tests) severity is already a plain string
        // since 2026_09_01_000005, so CONTINUOUS is accepted without changes.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `findings` MODIFY COLUMN `severity` ENUM('MINOR', 'MEDIUM', 'MAJOR', 'CRITICAL', 'OBSERVATION', 'CONTINUOUS') NOT NULL DEFAULT 'MINOR'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `findings` MODIFY COLUMN `severity` ENUM('MINOR', 'MEDIUM', 'MAJOR', 'CRITICAL', 'OBSERVATION') NOT NULL DEFAULT 'MINOR'");
        }
    }
};
