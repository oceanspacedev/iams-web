<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_auditor', function (Blueprint $table) {
            $table->foreignId('audit_id')->constrained('audits')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['audit_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_auditor');
    }
};
