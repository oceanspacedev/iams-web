<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained('audits')->onDelete('cascade');
            $table->foreignId('notification_rule_id')->constrained('notification_rules')->onDelete('cascade');
            $table->dateTime('scheduled_at');
            $table->dateTime('sent_at')->nullable();
            $table->string('channel')->default('whatsapp');
            $table->string('recipient')->nullable();
            $table->string('status')->default('PENDING'); // PENDING, SENT, FAILED, INACTIVE
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['audit_id', 'notification_rule_id'], 'audit_rule_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_notifications');
    }
};
