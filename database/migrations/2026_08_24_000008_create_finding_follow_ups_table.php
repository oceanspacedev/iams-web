<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finding_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finding_id')->unique()->constrained('findings')->cascadeOnDelete();
            $table->enum('retail_status', ['OPEN', 'IN_PROGRESS', 'CLOSED'])->default('OPEN');
            $table->enum('autoev_status', ['OPEN', 'IN_PROGRESS', 'CLOSED'])->default('OPEN');
            $table->enum('depo_ho_tokomas_status', ['OPEN', 'IN_PROGRESS', 'CLOSED'])->default('OPEN');
            $table->enum('csn_status', ['OPEN', 'IN_PROGRESS', 'CLOSED'])->default('OPEN');
            $table->string('team')->nullable();
            $table->string('category')->nullable();
            $table->date('tanggal_so')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->string('status_penyelesaian')->nullable();
            $table->date('tanggal_update')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finding_follow_ups');
    }
};
