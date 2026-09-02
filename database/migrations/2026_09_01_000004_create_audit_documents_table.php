<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained('audits')->cascadeOnDelete();
            $table->foreignId('finding_id')->nullable()->constrained('findings')->nullOnDelete();
            $table->string('document_type', 30); // LHP, BAP, OTHER
            $table->string('title');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_size')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_documents');
    }
};
