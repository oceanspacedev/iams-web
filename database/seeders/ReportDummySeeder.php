<?php

namespace Database\Seeders;

use App\Models\ActionPlan;
use App\Models\Audit;
use App\Models\AuditCategory;
use App\Models\Finding;
use App\Models\Sop;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Data dummy untuk mencoba laporan dan export Excel (Jan s/d bulan berjalan).
 *
 *   php artisan db:seed --class=ReportDummySeeder
 *
 * Semua data ditandai: nomor audit "DMY/..." dan kode toko "DMY-...".
 * Menjalankan ulang seeder menghapus data dummy lama dulu, jadi aman diulang.
 * Hanya hapus tanpa membuat ulang: REPORT_DUMMY_CLEAN_ONLY=1 php artisan db:seed --class=ReportDummySeeder
 */
class ReportDummySeeder extends Seeder
{
    private const AUDIT_PREFIX = 'DMY/';
    private const STORE_PREFIX = 'DMY-';

    /**
     * Contoh temuan per kelompok: [kelompok, kategori audit, kode SOP, temuan, opini, rekomendasi, action plan, PIC, ada kerugian].
     */
    private const SAMPLES = [
        ['Stock Opname', 'Audit Operational Retail', 'SOP-003',
            'Terdapat nilai barang hilang (NBH) aksesoris hasil stock opname.',
            "(-) Kurangnya monitoring stok dari pihak toko\n(-) Stock opname mandiri tidak berjalan rutin",
            '(-) Lakukan monitoring stok secara konsisten sesuai SOP yang berlaku',
            'Siap, akan kami perbaiki dan evaluasi', 'RSH, FL', true],
        ['Stock Opname', 'Audit Operational Retail', 'SOP-003',
            'Terdapat nilai barang lebih (NBL) aksesoris yang belum diinput ke sistem.',
            '(-) Penerimaan barang belum langsung diinput oleh admin toko',
            '(-) Input penerimaan barang di hari yang sama dan lakukan SO aksesoris 2 hari sekali',
            'Sudah kami input dan akan dimonitor', 'Admin toko', false],
        ['Transfer Order', 'Audit Distribusi & Online', 'SOP-002',
            'Terdapat 2 unit HP yang sudah pindah fisik ke toko lain namun belum diproses TO di sistem.',
            '(-) Tim toko yang meminta barang belum melakukan pengajuan TO',
            '(-) Segera koordinasi dengan admin gudang untuk proses TO sesuai SOP',
            'Sudah dilakukan proses TO di sistem', 'RSH, Kasir, FL', false],
        ['Transfer Order', 'Audit Distribusi & Online', 'SOP-002',
            'Terdapat unit retur yang dikirim ke gudang retur lebih dari 30 hari namun status di sistem masih di toko.',
            '(-) Belum ada konfirmasi penerimaan dari gudang retur',
            '(-) Koordinasi dengan PIC retur dan pastikan status unit diperbarui',
            'Akan kami follow up ke tim retur', 'RSH, Tim retur', false],
        ['Buku Customer & COD', 'Audit Operational Retail', 'SE-003',
            'Pencatatan buku customer dan buku COD tidak konsisten, beberapa transaksi tidak tercatat.',
            '(-) Pencatatan pada buku customer tidak konsisten',
            '(-) Perbaiki dan evaluasi pencatatan buku customer sesuai SOP',
            'Siap, akan kami evaluasi', 'RSH, FL', false],
        ['Kas Toko', 'Audit Finance', 'SOP-004',
            'Terdapat selisih kas kecil antara saldo fisik dan catatan kas harian.',
            '(-) Rekonsiliasi kas harian tidak dilakukan setiap tutup toko',
            '(-) Lakukan rekonsiliasi kas setiap hari dan laporkan selisih ke finance',
            'Selisih sudah diganti dan rekonsiliasi dijalankan harian', 'Kasir, RSH', true],
        ['Kas Toko', 'Audit Finance', 'SOP-004',
            'Setoran penjualan tunai ke bank terlambat lebih dari 1 hari kerja.',
            '(-) Belum ada jadwal setoran yang jelas di toko',
            '(-) Setorkan penjualan tunai maksimal H+1 sesuai ketentuan',
            'Jadwal setoran sudah kami buat', 'Kasir', false],
        ['Visibility', 'Audit Operational Retail', 'SE-001',
            'Display produk dan banner promo tidak sesuai planogram, area toko kurang bersih.',
            '(-) Kurang awareness tim toko terhadap kebersihan dan display',
            '(-) Tim toko wajib menjaga display dan kebersihan sesuai standar',
            'Sudah kami rapikan', 'RSH, Promotor', false],
        ['IC BIC Toko', 'Audit Operational Retail', 'SOP-001',
            'Hasil pemeriksaan IC BIC menunjukkan beberapa SOP penjualan tidak dijalankan.',
            '(-) Masih terdapat SOP yang harus diperbaiki oleh RSH dan tim toko',
            '(-) Lakukan evaluasi dan perbaiki kegiatan di lapangan sesuai SOP',
            'Baik, akan kami evaluasi dan perbaiki', 'RSH, FL, Tim toko', false],
        ['Aset', 'Audit Distribusi & Online', 'SE-002',
            'Terdapat aset rusak yang belum diproses penghapusan dan masih tercatat aktif di sistem.',
            '(-) Tidak ada catatan kondisi aset dari toko maupun GA',
            '(-) Koordinasi dengan GA dan IT agar data aset diperbarui',
            'Akan kami koordinasikan dengan tim GA', 'Tim toko, GA', false],
    ];

    private const DUMMY_STORES = [
        ['code' => 'DMY-GDG1', 'name' => 'Gudang Pusat Cirebon', 'type' => 'gudang', 'area' => 'Cirebon'],
        ['code' => 'DMY-GDG2', 'name' => 'Gudang Bandung', 'type' => 'gudang', 'area' => 'Bandung'],
        ['code' => 'DMY-HO1', 'name' => 'Head Office Cirebon', 'type' => 'head_office', 'area' => 'Cirebon'],
    ];

    public function run(): void
    {
        mt_srand(2026);

        DB::transaction(function () {
            $this->clean();

            if (env('REPORT_DUMMY_CLEAN_ONLY')) {
                $this->command?->info('Data dummy laporan dihapus.');

                return;
            }

            $this->seed();
        });
    }

    private function clean(): void
    {
        $auditIds = Audit::withTrashed()->where('audit_number', 'like', self::AUDIT_PREFIX . '%')->pluck('id');
        $findingIds = Finding::withTrashed()->whereIn('audit_id', $auditIds)->pluck('id');

        ActionPlan::whereIn('finding_id', $findingIds)->delete();
        Finding::withTrashed()->whereIn('id', $findingIds)->forceDelete();
        DB::table('audit_auditor')->whereIn('audit_id', $auditIds)->delete();
        Audit::withTrashed()->whereIn('id', $auditIds)->forceDelete();

        $storeIds = Store::withTrashed()->where('code', 'like', self::STORE_PREFIX . '%')->pluck('id');
        DB::table('store_user')->whereIn('store_id', $storeIds)->delete();
        Store::withTrashed()->whereIn('id', $storeIds)->forceDelete();
    }

    private function seed(): void
    {
        $auditors = User::role('auditor')->pluck('id')->all();
        $coordinatorId = User::role('coordinator')->value('id');
        $categories = AuditCategory::withTrashed()->pluck('id', 'name');
        $sops = Sop::pluck('id', 'code');

        if (!$auditors || $categories->isEmpty()) {
            $this->command?->warn('Butuh user auditor dan kategori audit. Jalankan php artisan db:seed dulu.');

            return;
        }

        $dummyStores = collect(self::DUMMY_STORES)->map(fn ($s) => Store::create($s + [
            'business_entity' => 'CV CS',
            'status'          => 'active',
        ]));
        $toko = Store::where('type', 'toko')->where('code', 'not like', self::STORE_PREFIX . '%')
            ->orderBy('id')->take(12)->get();
        $stores = $toko->concat($dummyStores)->values();

        $today = now()->startOfDay();
        $lastMonth = (int) $today->format('n');
        $year = (int) $today->format('Y');
        $auditCount = 0;
        $findingCount = 0;

        for ($month = 1; $month <= $lastMonth; $month++) {
            $monthAgo = $lastMonth - $month; // 0 = bulan berjalan
            $auditsThisMonth = mt_rand(5, 7);
            $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;

            foreach ($this->pick($stores, $auditsThisMonth) as $i => $store) {
                $day = min($daysInMonth, 2 + $i * 3 + mt_rand(0, 2));
                $auditDate = Carbon::create($year, $month, $day);
                if ($auditDate->greaterThan($today)) {
                    $auditDate = $today->copy()->subDays(mt_rand(0, 3));
                }

                $leadId = $auditors[array_rand($auditors)];
                $audit = Audit::create([
                    'audit_number' => sprintf('%s%d/%02d/%04d', self::AUDIT_PREFIX, $year, $month, $i + 1),
                    'title'        => 'Audit rutin ' . $store->name,
                    'store_id'     => $store->id,
                    'category_id'  => $categories['Audit Operational Retail'] ?? $categories->first(),
                    'location'     => $store->name,
                    'auditor_id'   => $leadId,
                    'audit_date'   => $auditDate->toDateString(),
                    'audit_time'   => '09:00',
                    'status'       => 'IN_PROGRESS',
                    'notes'        => 'Data dummy untuk uji laporan',
                ]);
                $team = array_values(array_diff($auditors, [$leadId]));
                if ($team) {
                    $audit->auditors()->attach($team[0]);
                }
                $auditCount++;

                $allClosed = true;
                foreach ($this->pick(collect(self::SAMPLES), mt_rand(3, 6)) as $sample) {
                    [, $categoryName, $sopCode, $text, $opinion, $recommendation, $response, $pic, $hasLoss] = $sample;

                    $severity = $this->weighted(['MINOR' => 60, 'MEDIUM' => 20, 'MAJOR' => 15, 'CONTINUOUS' => 5]);
                    $status = $this->statusFor($monthAgo);
                    $allClosed = $allClosed && $status === Finding::STATUS_CLOSED;

                    $days = match ($severity) {
                        'MINOR'  => 7,
                        'MEDIUM' => 14,
                        'MAJOR'  => 30,
                        default  => 30,
                    };
                    $deadline = $auditDate->copy()->addDays($days);
                    $closedAt = null;
                    if ($status === Finding::STATUS_CLOSED) {
                        // Sebagian besar tepat waktu, sebagian kecil terlambat
                        $closedAt = $auditDate->copy()->addDays(mt_rand(1, $days + (mt_rand(1, 10) <= 2 ? 10 : 0)));
                        if ($closedAt->greaterThan($today)) {
                            $closedAt = $today->copy();
                        }
                    }

                    $loss = $hasLoss ? round(mt_rand(25000, 2500000) + mt_rand(0, 99) / 100, 2) : 0;
                    $surplus = (!$hasLoss && str_contains($text, 'NBL')) ? mt_rand(10000, 350000) : 0;

                    $finding = Finding::create([
                        'audit_id'             => $audit->id,
                        'category_id'          => $categories[$categoryName] ?? $categories->first(),
                        'sop_id'               => $sops[$sopCode] ?? null,
                        'finding'              => $text,
                        'loss_amount'          => $loss,
                        'surplus_amount'       => $surplus,
                        'auditor_opinion'      => $opinion,
                        'recommendation'       => $recommendation,
                        'severity'             => $severity,
                        'severity_status'      => 'APPROVED',
                        'severity_reviewed_by' => $coordinatorId,
                        'severity_reviewed_at' => $auditDate->copy()->addDay(),
                        'is_severity_locked'   => true,
                        'status'               => $status,
                    ]);
                    $finding->forceFill(['created_at' => $auditDate, 'updated_at' => $closedAt ?? $auditDate])->saveQuietly();
                    $findingCount++;

                    if ($status !== Finding::STATUS_OPEN) {
                        ActionPlan::create([
                            'finding_id'  => $finding->id,
                            'action_plan' => $response,
                            'response'    => $response,
                            'pic'         => $pic,
                            'deadline'    => $deadline->toDateString(),
                            'status'      => $status === Finding::STATUS_CLOSED ? 'COMPLETED' : 'IN_PROGRESS',
                        ]);
                    }
                }

                $audit->update(['status' => $allClosed ? 'COMPLETED' : 'IN_PROGRESS']);
            }
        }

        $this->command?->info("Data dummy laporan: {$auditCount} audit, {$findingCount} temuan, " . $dummyStores->count() . ' toko dummy (gudang/HO).');
    }

    /**
     * Bulan lama hampir semua CLOSED; bulan terbaru lebih banyak yang masih berjalan.
     */
    private function statusFor(int $monthAgo): string
    {
        $weights = match (true) {
            $monthAgo >= 3 => ['CLOSED' => 96, 'IN_PROGRESS' => 2, 'OPEN' => 2],
            $monthAgo === 2 => ['CLOSED' => 85, 'IN_PROGRESS' => 6, 'WAITING_VERIFICATION' => 3, 'OPEN' => 6],
            $monthAgo === 1 => ['CLOSED' => 65, 'IN_PROGRESS' => 12, 'WAITING_VERIFICATION' => 8, 'VERIFIED' => 5, 'OPEN' => 10],
            default         => ['CLOSED' => 30, 'IN_PROGRESS' => 25, 'WAITING_VERIFICATION' => 10, 'VERIFIED' => 5, 'OPEN' => 30],
        };

        return $this->weighted($weights);
    }

    private function weighted(array $weights): string
    {
        $roll = mt_rand(1, array_sum($weights));
        foreach ($weights as $value => $weight) {
            $roll -= $weight;
            if ($roll <= 0) {
                return $value;
            }
        }

        return array_key_first($weights);
    }

    /**
     * Ambil n item acak (tanpa duplikat) dengan urutan stabil dari mt_rand.
     */
    private function pick($items, int $n): array
    {
        $list = $items->values()->all();
        for ($i = count($list) - 1; $i > 0; $i--) {
            $j = mt_rand(0, $i);
            [$list[$i], $list[$j]] = [$list[$j], $list[$i]];
        }

        return array_slice($list, 0, min($n, count($list)));
    }
}
