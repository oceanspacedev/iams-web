<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Alter findings table to support MINOR, MEDIUM, MAJOR
        DB::statement("ALTER TABLE `findings` MODIFY COLUMN `severity` ENUM('MINOR', 'MEDIUM', 'MAJOR', 'CRITICAL', 'OBSERVATION') NOT NULL DEFAULT 'MINOR'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `findings` MODIFY COLUMN `severity` ENUM('CRITICAL', 'MAJOR', 'MINOR', 'OBSERVATION') NOT NULL DEFAULT 'MINOR'");
    }
};
