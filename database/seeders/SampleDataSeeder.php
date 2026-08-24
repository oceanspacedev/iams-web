<?php

namespace Database\Seeders;

use App\Models\Audit;
use App\Models\AuditCategory;
use App\Models\Finding;
use App\Models\ActionPlan;
use App\Models\Sop;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        // Audit Categories
        $categories = [
            ['name' => 'Penjualan Langsung / COD',  'description' => 'Audit terhadap penjualan tunai dan cash on delivery'],
            ['name' => 'Transfer Order',             'description' => 'Audit proses transfer order antar toko/gudang'],
            ['name' => 'Buku Customer',              'description' => 'Audit pengelolaan data dan buku pelanggan'],
            ['name' => 'Stock Opname',               'description' => 'Audit stock opname dan kesesuaian fisik dengan sistem'],
            ['name' => 'Visibility',                 'description' => 'Audit display produk dan visibility toko'],
            ['name' => 'IC BIC',                     'description' => 'Audit internal control dan business internal control'],
            ['name' => 'Kas & Bank',                 'description' => 'Audit pengelolaan kas dan rekening bank toko'],
            ['name' => 'Administrasi',               'description' => 'Audit kelengkapan dan ketertiban administrasi'],
        ];

        foreach ($categories as $cat) {
            AuditCategory::firstOrCreate(['name' => $cat['name']], $cat);
        }

        // SOP / SE
        $sops = [
            ['code' => 'SOP-001', 'title' => 'SOP Penjualan Langsung',          'description' => 'Prosedur standar penjualan langsung kepada pelanggan'],
            ['code' => 'SOP-002', 'title' => 'SOP Transfer Order',              'description' => 'Prosedur transfer order antar unit'],
            ['code' => 'SOP-003', 'title' => 'SOP Stock Opname',                'description' => 'Prosedur pelaksanaan stock opname bulanan'],
            ['code' => 'SOP-004', 'title' => 'SOP Pengelolaan Kas',             'description' => 'Prosedur pengelolaan kas toko harian'],
            ['code' => 'SE-001',  'title' => 'SE Kebijakan Display Produk',     'description' => 'Surat edaran tentang standar display dan visibility produk'],
            ['code' => 'SE-002',  'title' => 'SE Prosedur Retur Barang',        'description' => 'Surat edaran prosedur pengembalian barang'],
            ['code' => 'SE-003',  'title' => 'SE Penanganan Keluhan Pelanggan', 'description' => 'Surat edaran standar penanganan keluhan pelanggan'],
        ];

        foreach ($sops as $sop) {
            Sop::firstOrCreate(['code' => $sop['code']], $sop);
        }

        // Stores
        $stores = [
            ['code' => 'STR-001', 'name' => 'Toko Jakarta Pusat',  'area' => 'Jakarta',   'regional' => 'Regional 1', 'status' => 'active'],
            ['code' => 'STR-002', 'name' => 'Toko Jakarta Selatan', 'area' => 'Jakarta',   'regional' => 'Regional 1', 'status' => 'active'],
            ['code' => 'STR-003', 'name' => 'Toko Bandung Utara',   'area' => 'Bandung',   'regional' => 'Regional 2', 'status' => 'active'],
            ['code' => 'STR-004', 'name' => 'Toko Surabaya Timur',  'area' => 'Surabaya',  'regional' => 'Regional 3', 'status' => 'active'],
            ['code' => 'STR-005', 'name' => 'Toko Medan Barat',     'area' => 'Medan',     'regional' => 'Regional 4', 'status' => 'inactive'],
        ];

        foreach ($stores as $store) {
            Store::firstOrCreate(['code' => $store['code']], $store);
        }

        // Sample Audits
        $auditor = User::whereHas('roles', fn($q) => $q->where('name', 'auditor'))->first();
        $storeA  = Store::where('code', 'STR-001')->first();
        $storeB  = Store::where('code', 'STR-002')->first();

        if ($auditor && $storeA) {
            $audit1 = Audit::firstOrCreate(
                ['audit_number' => 'AUD/2026/08/0001'],
                [
                    'store_id'   => $storeA->id,
                    'auditor_id' => $auditor->id,
                    'audit_date' => '2026-08-15',
                    'status'     => 'IN_PROGRESS',
                    'notes'      => 'Audit rutin bulanan toko Jakarta Pusat',
                ]
            );

            // Sample Finding
            $category = AuditCategory::where('name', 'Stock Opname')->first();
            $sop      = Sop::where('code', 'SOP-003')->first();

            if ($category && $sop) {
                $finding = Finding::firstOrCreate(
                    ['audit_id' => $audit1->id, 'finding' => 'Terdapat selisih stok antara fisik dan sistem sebesar 15 unit produk SKU-A123'],
                    [
                        'category_id'     => $category->id,
                        'sop_id'          => $sop->id,
                        'loss_amount'     => 4500000,
                        'auditor_opinion' => 'Selisih stok signifikan mengindikasikan kurangnya kontrol dalam proses penerimaan barang',
                        'recommendation'  => 'Lakukan rekonsiliasi stok mingguan dan perketat SOP penerimaan barang',
                        'severity'        => 'MAJOR',
                        'status'          => 'IN_PROGRESS',
                    ]
                );

                ActionPlan::firstOrCreate(
                    ['finding_id' => $finding->id],
                    [
                        'action_plan' => 'Melakukan stock opname ulang dan rekonsiliasi dengan sistem',
                        'response'    => 'Tim toko akan melakukan pengecekan ulang seluruh SKU dalam minggu ini',
                        'pic'         => 'Budi Santoso',
                        'deadline'    => '2026-08-31',
                        'status'      => 'IN_PROGRESS',
                    ]
                );
            }
        }

        if ($auditor && $storeB) {
            Audit::firstOrCreate(
                ['audit_number' => 'AUD/2026/08/0002'],
                [
                    'store_id'   => $storeB->id,
                    'auditor_id' => $auditor->id,
                    'audit_date' => '2026-09-01',
                    'status'     => 'PLANNED',
                    'notes'      => 'Audit rutin toko Jakarta Selatan',
                ]
            );
        }

        $this->command->info('Sample data seeded successfully.');
    }
}
