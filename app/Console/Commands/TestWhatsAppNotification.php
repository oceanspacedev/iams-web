<?php

namespace App\Console\Commands;

use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class TestWhatsAppNotification extends Command
{
    protected $signature = 'whatsapp:test {phone : Nomor WhatsApp tujuan (contoh: 081234567890)}';
    protected $description = 'Kirim pesan tes WhatsApp via WagHub Gateway';

    public function handle(): int
    {
        $phone = $this->argument('phone');

        $this->info("Mengirim pesan tes WhatsApp ke: {$phone}...");

        $name = 'Pengguna Sistem';
        $text = "🔔 *Sistem Audit (IAMS) Notification Test*\n\n"
            . "Halo *{$name}*,\n"
            . "Ini adalah pesan uji coba integrasi WhatsApp Gateway WagHub untuk sistem audit.\n\n"
            . "• *Waktu Kirim:* " . now()->format('d M Y H:i:s') . "\n"
            . "• *Status:* Berhasil Terhubung ✅\n\n"
            . "_Sistem Audit (IAMS)_";

        $success = WhatsAppService::send($phone, $text, 'notification');

        if ($success) {
            $this->info("✅ Berhasil! Pesan WhatsApp telah berhasil dikirim ke {$phone}.");
            return self::SUCCESS;
        }

        $this->error("❌ Gagal mengirim pesan. Silakan periksa log di storage/logs/laravel.log.");
        return self::FAILURE;
    }
}
