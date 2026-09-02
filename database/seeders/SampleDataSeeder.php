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
            ['name' => 'Audit Operational Retail',  'description' => 'Pemeriksaan operasional toko retail, kasir, display produk, dan kepatuhan SOP cabang ritel'],
            ['name' => 'Audit Finance',             'description' => 'Pemeriksaan keuangan, cash flow, kas & bank, rekonsiliasi, dan administrasi finansial'],
            ['name' => 'Audit Distribusi & Online', 'description' => 'Pemeriksaan gudang distribusi logistik, stok fisik, order transfer, dan penjualan online / marketplace'],
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

        // Stores (Termasuk data toko CSA: Toko Megu, Toko Tuparev, Toko Jatiwangi)
        $stores = [
            ['code' => 'TKO-MEGU', 'name' => 'Toko Megu',      'business_entity' => 'CSA Retail', 'type' => 'toko', 'area' => 'Cirebon',    'regional' => 'Jawa Barat', 'status' => 'active'],
            ['code' => 'TKO-TPRV', 'name' => 'Toko Tuparev',   'business_entity' => 'CSA Retail', 'type' => 'toko', 'area' => 'Cirebon',    'regional' => 'Jawa Barat', 'status' => 'active'],
            ['code' => 'TKO-JTWG', 'name' => 'Toko Jatiwangi', 'business_entity' => 'CSA Retail', 'type' => 'toko', 'area' => 'Majalengka', 'regional' => 'Jawa Barat', 'status' => 'active'],
            ['code' => 'STR-001',  'name' => 'Toko Jakarta Pusat',  'business_entity' => 'PT CSA Retail', 'type' => 'toko', 'area' => 'Jakarta',   'regional' => 'Regional 1', 'status' => 'active'],
            ['code' => 'STR-002',  'name' => 'Toko Jakarta Selatan', 'business_entity' => 'PT CSA Retail', 'type' => 'toko', 'area' => 'Jakarta',   'regional' => 'Regional 1', 'status' => 'active'],
            ['code' => 'STR-003',  'name' => 'Toko Bandung Utara',   'business_entity' => 'PT CSA Retail', 'type' => 'toko', 'area' => 'Bandung',   'regional' => 'Regional 2', 'status' => 'active'],
        ];

        foreach ($stores as $store) {
            Store::firstOrCreate(['code' => $store['code']], $store);
        }

        // Sample Audits
        $auditor = User::whereHas('roles', fn($q) => $q->where('name', 'auditor'))->first();
        $storeA  = Store::where('code', 'STR-001')->first();
        $storeB  = Store::where('code', 'STR-002')->first();

        if ($auditor && $storeA) {
            $category = AuditCategory::where('name', 'Audit Operational Retail')->first();

            $audit1 = Audit::firstOrCreate(
                ['audit_number' => 'AUD/2026/08/0001'],
                [
                    'category_id' => $category?->id,
                    'store_id'    => $storeA->id,
                    'auditor_id'  => $auditor->id,
                    'audit_date'  => '2026-08-15',
                    'status'      => 'IN_PROGRESS',
                    'notes'       => 'Audit rutin bulanan toko Jakarta Pusat',
                ]
            );

            // Sample Finding
            $sop = Sop::where('code', 'SOP-003')->first();

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
