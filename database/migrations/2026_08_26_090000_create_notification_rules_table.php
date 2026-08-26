<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');                      // e.g. 'H-7', 'H-3', 'H-1', 'Hari H'
            $table->integer('days_before')->default(0);  // 7, 3, 1, 0
            $table->string('send_time')->default('08:00'); // '08:00'
            $table->string('channel')->default('whatsapp'); // 'whatsapp', 'email', 'dashboard'
            $table->string('recipient_type')->default('all'); // 'all', 'auditee', 'auditor'
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_rules');
    }
};
