<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained('audits')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('audit_categories')->restrictOnDelete();
            $table->foreignId('sop_id')->nullable()->constrained('sops')->nullOnDelete();
            $table->text('finding');
            $table->decimal('loss_amount', 15, 2)->nullable();
            $table->text('auditor_opinion')->nullable();
            $table->text('recommendation')->nullable();
            $table->enum('severity', ['CRITICAL', 'MAJOR', 'MINOR', 'OBSERVATION'])->default('MINOR');
            $table->enum('status', ['OPEN', 'IN_PROGRESS', 'WAITING_VERIFICATION', 'VERIFIED', 'CLOSED'])->default('OPEN');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['audit_id', 'status']);
            $table->index(['status', 'severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('findings');
    }
};
