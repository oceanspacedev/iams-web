<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('action_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finding_id')->unique()->constrained('findings')->cascadeOnDelete();
            $table->text('action_plan')->nullable();
            $table->text('response')->nullable();
            $table->string('pic')->nullable();
            $table->date('deadline')->nullable();
            $table->enum('status', ['OPEN', 'IN_PROGRESS', 'COMPLETED', 'OVERDUE'])->default('OPEN');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('action_plans');
    }
};
