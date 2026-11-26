<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Alter findings table to support MINOR, MEDIUM, MAJOR
            DB::statement("ALTER TABLE `findings` MODIFY COLUMN `severity` ENUM('MINOR', 'MEDIUM', 'MAJOR', 'CRITICAL', 'OBSERVATION') NOT NULL DEFAULT 'MINOR'");

            return;
        }

        // Other drivers (SQLite for tests): drop the enum CHECK constraint by turning the
        // column into a plain string, so MINOR/MEDIUM/MAJOR/CONTINUOUS and the legacy
        // CRITICAL/OBSERVATION values are all accepted.
        Schema::table('findings', function (Blueprint $table) {
            $table->string('severity', 20)->default('MINOR')->change();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `findings` MODIFY COLUMN `severity` ENUM('CRITICAL', 'MAJOR', 'MINOR', 'OBSERVATION') NOT NULL DEFAULT 'MINOR'");
        }
    }
};
