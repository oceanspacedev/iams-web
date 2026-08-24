<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('findings', function (Blueprint $table) {
            $table->string('severity_status')->default('PENDING_REVIEW')->after('severity'); // PENDING_REVIEW, APPROVED, ADJUSTED
            $table->foreignId('severity_reviewed_by')->nullable()->after('severity_status')->constrained('users')->nullOnDelete();
            $table->timestamp('severity_reviewed_at')->nullable()->after('severity_reviewed_by');
            $table->text('severity_notes')->nullable()->after('severity_reviewed_at');
            $table->boolean('is_severity_locked')->default(false)->after('severity_notes');
        });
    }

    public function down(): void
    {
        Schema::table('findings', function (Blueprint $table) {
            $table->dropForeign(['severity_reviewed_by']);
            $table->dropColumn([
                'severity_status',
                'severity_reviewed_by',
                'severity_reviewed_at',
                'severity_notes',
                'is_severity_locked',
            ]);
        });
    }
};
