<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finding_id')->constrained('findings')->cascadeOnDelete();
            $table->foreignId('audit_id')->constrained('audits')->cascadeOnDelete();
            $table->string('quality_category', 50); // impact_50m, fraud_risk, system_control, org_structure
            $table->string('title');
            $table->decimal('impact_amount', 15, 2)->nullable();
            $table->text('root_cause')->nullable();
            $table->text('systemic_issue')->nullable();
            $table->text('recommendation')->nullable();
            $table->text('auditor_notes')->nullable();
            $table->foreignId('reported_by')->constrained('users')->cascadeOnDelete();
            $table->string('status', 30)->default('REPORTED'); // REPORTED, REVIEWED, APPROVED
            $table->timestamps();
            $table->softDeletes();

            $table->index(['audit_id', 'quality_category']);
            $table->index('quality_category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_findings');
    }
};
