<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Waktu temuan ditutup (CLOSED), dipakai untuk menghitung closed on time / overdue.
     * Data lama: diisi dari updated_at untuk temuan yang sudah CLOSED.
     */
    public function up(): void
    {
        Schema::table('findings', function (Blueprint $table) {
            $table->timestamp('closed_at')->nullable()->after('status');
        });

        DB::table('findings')
            ->where('status', 'CLOSED')
            ->whereNull('closed_at')
            ->update(['closed_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('findings', function (Blueprint $table) {
            $table->dropColumn('closed_at');
        });
    }
};
