<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            SampleDataSeeder::class,
            UserSeeder::class,
            // Dipanggil lagi: audit & temuan contoh butuh user auditor dari UserSeeder,
            // sedangkan UserSeeder butuh toko dari SampleDataSeeder. Aman karena pakai firstOrCreate.
            SampleDataSeeder::class,
            CsaStoresSeeder::class,
        ]);
    }
}
