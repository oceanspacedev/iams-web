<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * 1. Otomatisasi Notifikasi Jadwal Audit (H-7, H-3, H-1, Hari H)
 * - Berjalan setiap menit mengecek antrean jadwal pengiriman
 * - Mengirim pesan WhatsApp ke penerima dan mengupdate status Menunggu -> Terkirim / Gagal
 */
Schedule::command('audit:dispatch-scheduled-notifications')
    ->everyMinute()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/audit-scheduled-notifications.log'));

/**
 * 2. Daily Reminders (Action Plan Deadlines & Overdue)
 * - Berjalan setiap hari pada pukul 08:00 WIB
 */
Schedule::command('audit:send-reminders')
    ->dailyAt('08:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/audit-reminders.log'));
