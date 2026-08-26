<?php

namespace App\Services;

use App\Models\AuditNotification;
use Illuminate\Support\Facades\Log;

class AuditNotificationService
{
    /**
     * Dispatch an individual AuditNotification item.
     */
    public static function dispatch(AuditNotification $notification): bool
    {
        $notification->loadMissing(['audit.store.auditees', 'audit.auditor', 'rule']);
        $audit = $notification->audit;
        $rule  = $notification->rule;

        if (!$audit || !$rule || !$rule->is_active) {
            $notification->update(['status' => 'INACTIVE']);
            return false;
        }

        // Prevent resending already sent notification
        if ($notification->status === 'SENT') {
            return true;
        }

        try {
            $recipients = [];
            $recipientType = $rule->recipient_type ?: 'all';

            // Collect Auditees
            if (in_array($recipientType, ['all', 'auditee']) && $audit->store && $audit->store->auditees) {
                foreach ($audit->store->auditees as $auditee) {
                    if (!empty($auditee->phone)) {
                        $recipients[] = [
                            'name'  => $auditee->name,
                            'phone' => $auditee->phone,
                            'role'  => 'Auditee Toko',
                        ];
                    }
                }
            }

            // Collect Auditor
            if (in_array($recipientType, ['all', 'auditor']) && $audit->auditor && !empty($audit->auditor->phone)) {
                $recipients[] = [
                    'name'  => $audit->auditor->name,
                    'phone' => $audit->auditor->phone,
                    'role'  => 'Auditor',
                ];
            }

            if (empty($recipients)) {
                $notification->update([
                    'status'        => 'FAILED',
                    'error_message' => 'Tidak ada nomor WhatsApp penerima (Auditee atau Auditor) yang terdaftar.',
                ]);
                return false;
            }

            $auditDateStr = $audit->audit_date->format('d M Y');
            $auditTimeStr = $audit->audit_time ?: '09:00';
            $locationStr  = $audit->location ?: ($audit->store ? "{$audit->store->name} ({$audit->store->code})" : 'Toko Ritel');
            $auditorName  = $audit->auditor ? $audit->auditor->name : 'Auditor Lapangan';
            $titleStr     = $audit->title ?: 'Pemeriksaan Audit Toko';
            $ruleName     = $rule->name;

            $sentSuccessCount = 0;
            $recipientNames = [];

            foreach ($recipients as $target) {
                $recipientNames[] = "{$target['name']} ({$target['phone']})";

                // Compose message
                $msg = "🔔 *PENGINGAT JADWAL AUDIT ({$ruleName})*\n\n"
                    . "Halo *{$target['name']}*,\n"
                    . "Ini adalah pengingat pelaksanaan audit *{$titleStr}*:\n\n"
                    . "• *No. Audit:* {$audit->audit_number}\n"
                    . "• *Tanggal Audit:* {$auditDateStr}\n"
                    . "• *Waktu:* {$auditTimeStr} WIB\n"
                    . "• *Lokasi:* {$locationStr}\n"
                    . "• *Auditor Bertugas:* {$auditorName}\n"
                    . ($audit->notes ? "• *Catatan:* {$audit->notes}\n" : "")
                    . "\nMohon mempersiapkan seluruh kebutuhan, personil, dan dokumen audit.\n"
                    . "_AuditFlow Enterprise System_";

                $sent = WhatsAppService::send($target['phone'], $msg);
                if ($sent) {
                    $sentSuccessCount++;
                }
            }

            if ($sentSuccessCount > 0) {
                $notification->update([
                    'status'        => 'SENT',
                    'sent_at'       => now(),
                    'recipient'     => implode(', ', $recipientNames),
                    'error_message' => null,
                ]);
                return true;
            }

            $notification->update([
                'status'        => 'FAILED',
                'error_message' => 'Gagal mengirim pesan WhatsApp via Gateway WagHub.',
            ]);
            return false;

        } catch (\Throwable $e) {
            Log::error("Error dispatching audit notification ID {$notification->id}: " . $e->getMessage());

            $notification->update([
                'status'        => 'FAILED',
                'error_message' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
