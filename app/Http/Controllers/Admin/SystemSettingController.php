<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActionPlan;
use App\Models\Audit;
use App\Models\AuditCategory;
use App\Models\AuditDocument;
use App\Models\AuditNotification;
use App\Models\Evidence;
use App\Models\Finding;
use App\Models\FindingFollowUp;
use App\Models\NotificationRule;
use App\Models\QualityFinding;
use App\Models\Sop;
use App\Models\Store;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SystemSettingController extends Controller
{
    /**
     * Display the settings and maintenance page.
     */
    public function index(): Response
    {
        $stats = [
            'total_audits'       => Audit::count(),
            'total_findings'     => Finding::count(),
            'total_action_plans' => ActionPlan::count(),
            'total_documents'    => AuditDocument::count() + Evidence::count(),
            'total_qualities'    => QualityFinding::count(),
            'total_stores'       => Store::count(),
            'total_users'        => User::count(),
            'total_sops'         => Sop::count(),
            'total_categories'   => AuditCategory::count(),
        ];

        return Inertia::render('Admin/Settings/Index', [
            'stats'            => $stats,
            'showDemoAccounts' => SystemSetting::isDemoAccountsEnabled(),
        ]);
    }

    /**
     * Toggle the visibility of demo accounts on the login page.
     */
    public function toggleDemoAccounts(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => 'required|boolean',
        ]);

        SystemSetting::set(
            'show_demo_accounts',
            $validated['enabled'],
            'Menampilkan tombol akun demo cepat di halaman login'
        );

        $statusText = $validated['enabled'] ? 'diaktifkan (tampil di login)' : 'dinonaktifkan (disembunyikan dari login)';

        return back()->with('success', "Tampilan akun demo berhasil {$statusText}.");
    }

    /**
     * Reset transactional audit data only (Audits, Findings, Action Plans, Evidences, Documents).
     * Keeps master data (Users, Stores, SOPs, Categories, Settings) intact.
     */
    public function resetTransactional(Request $request): RedirectResponse
    {
        // Enforce admin check
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Hanya Administrator yang memiliki wewenang untuk mereset data.');
        }

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // Clean transactional records
            Evidence::truncate();
            FindingFollowUp::truncate();
            ActionPlan::truncate();
            Finding::truncate();
            QualityFinding::truncate();
            AuditDocument::truncate();
            AuditNotification::truncate();
            DB::table('audit_auditor')->truncate();
            Audit::truncate();

            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            // Clean physical files stored in public disks
            $this->cleanStorageDirectory('public/audit_documents');
            $this->cleanStorageDirectory('public/evidences');
            $this->cleanStorageDirectory('public/quality_findings');

            return back()->with('success', 'Semua data transaksional audit (Audit, Temuan, Action Plan, Bukti, dan Dokumen) berhasil direset menjadi kosong.');
        } catch (\Throwable $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            return back()->with('error', 'Gagal mereset data transaksional: ' . $e->getMessage());
        }
    }

    /**
     * Factory Reset: Clears all tables and restores the initial seed data.
     */
    public function factoryReset(Request $request): RedirectResponse
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Hanya Administrator yang memiliki wewenang untuk melakukan factory reset.');
        }

        $request->validate([
            'confirmation' => 'required|string|in:RESET',
        ], [
            'confirmation.in' => 'Konfirmasi gagal. Anda harus mengetik kata "RESET" dengan huruf kapital.',
        ]);

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // Truncate all application data
            Evidence::truncate();
            FindingFollowUp::truncate();
            ActionPlan::truncate();
            Finding::truncate();
            QualityFinding::truncate();
            AuditDocument::truncate();
            AuditNotification::truncate();
            DB::table('audit_auditor')->truncate();
            Audit::truncate();

            DB::table('store_user')->truncate();
            Store::truncate();
            Sop::truncate();
            AuditCategory::truncate();
            NotificationRule::truncate();

            User::truncate();
            DB::table('model_has_roles')->truncate();
            DB::table('model_has_permissions')->truncate();

            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            // Clean physical files
            $this->cleanStorageDirectory('public/audit_documents');
            $this->cleanStorageDirectory('public/evidences');
            $this->cleanStorageDirectory('public/quality_findings');

            // Re-seed system with default data
            Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);

            // Re-seed notification rules if any
            if (class_exists(\Database\Seeders\NotificationRuleSeeder::class)) {
                Artisan::call('db:seed', ['--class' => 'Database\Seeders\NotificationRuleSeeder', '--force' => true]);
            }

            // Ensure demo accounts setting is active
            SystemSetting::set('show_demo_accounts', true);

            // Re-authenticate admin user to maintain the session
            $adminUser = User::where('email', 'admin@auditflow.com')->first();
            if ($adminUser) {
                Auth::login($adminUser);
            }

            return redirect()->route('admin.settings.index')->with(
                'success',
                'Factory Reset berhasil! Seluruh data telah dikembalikan ke kondisi awal bawaan (default demo data).'
            );
        } catch (\Throwable $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            return back()->with('error', 'Gagal melakukan factory reset: ' . $e->getMessage());
        }
    }

    /**
     * Helper to safely remove stored files in directory while preserving the folder.
     */
    private function cleanStorageDirectory(string $path): void
    {
        if (Storage::exists($path)) {
            $files = Storage::allFiles($path);
            Storage::delete($files);
        }
    }
}
