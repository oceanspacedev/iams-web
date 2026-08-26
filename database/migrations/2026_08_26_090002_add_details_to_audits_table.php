<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->string('title')->nullable()->after('audit_number');
            $table->string('audit_time')->default('09:00')->after('audit_date');
            $table->string('location')->nullable()->after('store_id');
        });
    }

    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->dropColumn(['title', 'audit_time', 'location']);
        });
    }
};
