<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('business_entity', 100)->nullable()->after('name'); // PT, CV, Koperasi, etc.
            $table->string('type', 50)->default('toko')->after('business_entity'); // toko, gudang, head_office, hub
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['business_entity', 'type']);
        });
    }
};
