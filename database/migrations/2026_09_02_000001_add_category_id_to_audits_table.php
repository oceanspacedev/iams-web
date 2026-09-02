<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('store_id')->constrained('audit_categories')->nullOnDelete();
            $table->index(['category_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropIndex(['category_id', 'status']);
            $table->dropColumn('category_id');
        });
    }
};
