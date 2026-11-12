<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * NBL (Nilai Barang Lebih) — nilai barang lebih hasil pemeriksaan,
     * berdampingan dengan loss_amount yang kini ditampilkan sebagai NBH (Nilai Barang Hilang).
     */
    public function up(): void
    {
        Schema::table('findings', function (Blueprint $table) {
            $table->decimal('surplus_amount', 15, 2)->nullable()->after('loss_amount');
        });
    }

    public function down(): void
    {
        Schema::table('findings', function (Blueprint $table) {
            $table->dropColumn('surplus_amount');
        });
    }
};
