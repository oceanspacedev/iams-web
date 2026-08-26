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

        foreach ($dueNotifications as $notification) {
            $ok = AuditNotificationService::dispatch($notification);
            if ($ok) {
                $successCount++;
                $this->line("  ✓ Sent [{$notification->rule->name}] for Audit: {$notification->audit->audit_number}");
            } else {
                $failCount++;
                $this->error("  ✗ Failed [{$notification->rule->name}] for Audit: {$notification->audit->audit_number}");
            }
        }

        $this->info("Selesai: {$successCount} terkirim, {$failCount} gagal.");

        return Command::SUCCESS;
    }
}
