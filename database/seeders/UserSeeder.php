<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@auditflow.com'],
            [
                'name'      => 'Administrator',
                'password'  => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $admin->assignRole('admin');

        // Auditor
        $auditor = User::firstOrCreate(
            ['email' => 'auditor@auditflow.com'],
            [
                'name'      => 'Ahmad Rasyid',
                'password'  => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $auditor->assignRole('auditor');

        $auditor2 = User::firstOrCreate(
            ['email' => 'auditor2@auditflow.com'],
            [
                'name'      => 'Dewi Pratiwi',
                'password'  => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $auditor2->assignRole('auditor');

        // Coordinator (Koordinator Audit)
        $coordinator = User::firstOrCreate(
            ['email' => 'kordinator@auditflow.com'],
            [
                'name'      => 'Hendra Wijaya (Koordinator)',
                'password'  => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $coordinator->assignRole('coordinator');

        // Auditee
        $auditee = User::firstOrCreate(
            ['email' => 'auditee@auditflow.com'],
            [
                'name'      => 'Budi Santoso',
                'password'  => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $auditee->assignRole('auditee');

        $auditee2 = User::firstOrCreate(
            ['email' => 'toko.selatan@auditflow.com'],
            [
                'name'      => 'Siti Rahayu',
                'password'  => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $auditee2->assignRole('auditee');

        // Assign auditees to stores
        $storeA = Store::where('code', 'STR-001')->first();
        $storeB = Store::where('code', 'STR-002')->first();

        if ($storeA) {
            $storeA->auditees()->syncWithoutDetaching([$auditee->id]);
        }
        if ($storeB) {
            $storeB->auditees()->syncWithoutDetaching([$auditee2->id]);
        }

        $this->command->info('Users seeded successfully.');
    }
}
