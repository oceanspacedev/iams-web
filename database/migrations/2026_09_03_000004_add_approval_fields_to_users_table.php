<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('approval_status', 20)->default('approved')->after('is_active');
            $table->string('requested_role', 50)->nullable()->after('approval_status');
            $table->text('rejection_reason')->nullable()->after('requested_role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['approval_status', 'requested_role', 'rejection_reason']);
        });
    }
};
