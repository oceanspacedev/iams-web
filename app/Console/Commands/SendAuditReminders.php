<?php

namespace App\Console\Commands;

use App\Models\ActionPlan;
use App\Models\Audit;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class SendAuditReminders extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'audit:send-reminders {--type=all : Jenis pengingat: all, deadline, overdue, unfilled, audit}';

    /**
     * The console command description.
     */
    protected $description = 'Kirim pengingat WhatsApp otomatis untuk deadline Action Plan dan jadwal audit H-1';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->option('type') ?: 'all';
        $today = now()->startOfDay();
        $totalSent = 0;

        $this->info("=== Memulai Pengiriman Pengingat WhatsApp (Mode: {$type}) ===");
        $this->info("Waktu: " . now()->format('Y-m-d H:i:s'));

        // 1. Pengingat H-3 s/d Hari H Deadline Action Plan
        if (in_array($type, ['all', 'deadline'])) {
            $this->line("\n[1/4] Mengecek Action Plan mendekati deadline (H-3 s/d Hari H)...");

            $upcomingPlans = ActionPlan::with(['finding.audit.store.auditees', 'finding.category'])
                ->whereIn('status', ['OPEN', 'IN_PROGRESS'])
                ->whereNotNull('deadline')
                ->whereBetween('deadline', [$today, $today->copy()->addDays(3)])
                ->get();

            $this->info("Ditemukan: {$upcomingPlans->count()} Action Plan mendekati deadline.");

            foreach ($upcomingPlans as $plan) {
                $sent = WhatsAppService::notifyActionPlanReminder($plan, 'UPCOMING');
                $totalSent += $sent;
                $this->line("  ✓ Sent to {$sent} recipient(s) for Audit: {$plan->finding->audit->audit_number}");
            }
        }

        // 2. Pengingat Action Plan OVERDUE (Terlambat)
        if (in_array($type, ['all', 'overdue'])) {
            $this->line("\n[2/4] Mengecek Action Plan yang OVERDUE (Melewati Deadline)...");

            $overduePlans = ActionPlan::with(['finding.audit.store.auditees', 'finding.category'])
                ->whereIn('status', ['OPEN', 'IN_PROGRESS', 'OVERDUE'])
                ->whereNotNull('deadline')
                ->where('deadline', '<', $today)
                ->get();

            $this->info("Ditemukan: {$overduePlans->count()} Action Plan OVERDUE.");

            foreach ($overduePlans as $plan) {
                // Update status to OVERDUE if still OPEN or IN_PROGRESS
                if ($plan->status !== 'OVERDUE') {
                    $plan->update(['status' => 'OVERDUE']);
                }

                $sent = WhatsAppService::notifyActionPlanReminder($plan, 'OVERDUE');
                $totalSent += $sent;
                $this->line("  🚨 Overdue reminder sent to {$sent} recipient(s) for Store: {$plan->finding->audit->store->name}");
            }
        }

        // 3. Pengingat Temuan yang belum diisi Action Plan (> 24 jam)
        if (in_array($type, ['all', 'unfilled'])) {
            $this->line("\n[3/4] Mengecek temuan yang belum diisi Action Plan oleh toko...");

            $unfilledPlans = ActionPlan::with(['finding.audit.store.auditees', 'finding.category'])
                ->where('status', 'OPEN')
                ->whereNull('action_plan')
                ->whereHas('finding', fn ($q) => $q->where('created_at', '<=', now()->subDay()))
                ->get();

            $this->info("Ditemukan: {$unfilledPlans->count()} Action Plan belum diisi.");

            foreach ($unfilledPlans as $plan) {
                $sent = WhatsAppService::notifyActionPlanReminder($plan, 'UNFILLED');
                $totalSent += $sent;
                $this->line("  ⏰ Unfilled reminder sent to {$sent} recipient(s) for Store: {$plan->finding->audit->store->name}");
            }
        }

        // 4. Pengingat Audit H-1 (Jadwal besok hari)
        if (in_array($type, ['all', 'audit'])) {
            $this->line("\n[4/4] Mengecek jadwal Audit yang akan berlangsung besok (H-1)...");

            $tomorrow = $today->copy()->addDay()->toDateString();
            $upcomingAudits = Audit::with(['store.auditees', 'auditor'])
                ->where('status', 'PLANNED')
                ->whereDate('audit_date', $tomorrow)
                ->get();

            $this->info("Ditemukan: {$upcomingAudits->count()} Audit terjadwal besok ({$tomorrow}).");

            foreach ($upcomingAudits as $audit) {
                $sent = WhatsAppService::notifyAuditH1Reminder($audit);
                $totalSent += $sent;
                $this->line("  🔔 H-1 reminder sent to {$sent} recipient(s) for Audit: {$audit->audit_number}");
            }
        }

        $this->info("\n=== Selesai. Total pesan pengingat terkirim: {$totalSent} pesan ===");

        return Command::SUCCESS;
    }
}
