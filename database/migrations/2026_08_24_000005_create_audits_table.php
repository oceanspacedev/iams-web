<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audits', function (Blueprint $table) {
            $table->id();
            $table->string('audit_number', 50)->unique()->index();
            $table->foreignId('store_id')->constrained('stores')->restrictOnDelete();
            $table->foreignId('auditor_id')->constrained('users')->restrictOnDelete();
            $table->date('audit_date');
            $table->enum('status', ['PLANNED', 'IN_PROGRESS', 'COMPLETED', 'CLOSED'])->default('PLANNED');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['store_id', 'status']);
            $table->index(['auditor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audits');
    }
};
