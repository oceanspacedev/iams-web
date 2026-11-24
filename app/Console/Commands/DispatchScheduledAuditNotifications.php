<?php

namespace App\Console\Commands;

use App\Models\AuditNotification;
use App\Services\AuditNotificationService;
use Illuminate\Console\Command;

class DispatchScheduledAuditNotifications extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'audit:dispatch-scheduled-notifications';

    /**
     * The console command description.
     */
    protected $description = 'Kirim otomatis notifikasi jadwal audit (H-7, H-3, H-1, Hari H) yang sudah tiba waktunya';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dueNotifications = AuditNotification::with(['audit.store.auditees', 'audit.auditor', 'rule'])
            ->where('status', 'PENDING')
            ->where('scheduled_at', '<=', now())
            ->get();

        if ($dueNotifications->isEmpty()) {
            return Command::SUCCESS;
        }

        $this->info("Ditemukan {$dueNotifications->count()} jadwal notifikasi audit yang siap dikirim...");

        $successCount = 0;
        $failCount    = 0;

        $skippedCount = 0;

        foreach ($dueNotifications as $notification) {
            $audit = $notification->audit;

            // Audit deleted, or no longer PLANNED: the reminder is obsolete
            if (!$audit || $audit->status !== 'PLANNED') {
                $notification->update(['status' => 'INACTIVE']);
                $skippedCount++;
                continue;
            }

            $ruleName    = $notification->rule?->name ?? '-';
            $auditNumber = $audit->audit_number;

            $ok = AuditNotificationService::dispatch($notification);
            if ($ok) {
                $successCount++;
                $this->line("  ✓ Sent [{$ruleName}] for Audit: {$auditNumber}");
            } else {
                $failCount++;
                $this->error("  ✗ Failed [{$ruleName}] for Audit: {$auditNumber}");
            }
        }

        $this->info("Selesai: {$successCount} terkirim, {$failCount} gagal, {$skippedCount} dinonaktifkan.");

        return Command::SUCCESS;
    }
}
