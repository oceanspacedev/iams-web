<?php

namespace App\Services;

use App\Models\ActionPlan;
use App\Models\Audit;
use App\Models\Evidence;
use App\Models\Finding;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Send WhatsApp text message via WagHub API gateway.
     */
    public static function send(string $phone, string $text, string $purpose = 'notification'): bool
    {
        $token = config('services.waghub.token');
        $url   = config('services.waghub.url');

        if (empty($token) || empty($phone)) {
            Log::warning("WhatsApp notification skipped: Missing token or phone number.", [
                'phone' => $phone,
            ]);
            return false;
        }

        // Clean phone number format
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

        try {
            $endpoint = rtrim($url, '/') . '/api/v1/messages';

            $response = Http::withHeaders([
                'Accept'          => 'application/json',
                'Authorization'   => 'Bearer ' . $token,
                'Idempotency-Key' => uniqid('audit-', true),
                'Content-Type'    => 'application/json',
            ])->timeout(10)->post($endpoint, [
                'recipient' => [
                    'type'  => 'phone',
                    'value' => $cleanPhone,
                ],
                'message' => [
                    'type' => 'text',
                    'text' => $text,
                ],
                'purpose'          => $purpose,
                'mode'             => 'sync',
                'route_key'        => 'default',
                'client_reference' => 'auditflow-' . time(),
            ]);

            if ($response->successful()) {
                Log::info("WhatsApp message sent successfully to {$cleanPhone}");
                return true;
            }

            Log::error("WhatsApp gateway returned error", [
                'status' => $response->status(),
                'body'   => $response->body(),
                'phone'  => $cleanPhone,
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error("Failed to send WhatsApp message: " . $e->getMessage(), [
                'phone' => $cleanPhone,
            ]);
            return false;
        }
    }

    /**
     * Notify Auditor & Store Auditees when an Audit is scheduled.
     */
    public static function notifyAuditScheduled(Audit $audit): void
    {
        $audit->loadMissing(['store.auditees', 'auditor']);

        // 1. Send to assigned Auditor
        if ($audit->auditor && !empty($audit->auditor->phone)) {
            $msg = "📋 *PENUGASAN AUDIT BARU*\n\n"
                . "Halo *{$audit->auditor->name}*,\n"
                . "Anda telah ditugaskan untuk melakukan audit ritel:\n\n"
                . "• *No. Audit:* {$audit->audit_number}\n"
                . "• *Toko:* {$audit->store->name} ({$audit->store->code})\n"
                . "• *Tanggal:* {$audit->audit_date->format('d M Y')}\n"
                . "• *Catatan:* " . ($audit->notes ?: '-') . "\n\n"
                . "Silakan login ke portal Auditor untuk melihat detail penugasan.\n"
                . "_Sistem Audit (IAMS)_";

            self::send($audit->auditor->phone, $msg);
        }

        // 2. Send to Store Auditees
        if ($audit->store && $audit->store->auditees) {
            foreach ($audit->store->auditees as $auditee) {
                if (!empty($auditee->phone)) {
                    $msg = "📢 *JADWAL AUDIT TOKO ANDA*\n\n"
                        . "Halo *{$auditee->name}* ({$audit->store->name}),\n"
                        . "Toko Anda telah dijadwalkan untuk audit pemeriksaan:\n\n"
                        . "• *No. Audit:* {$audit->audit_number}\n"
                        . "• *Auditor Bertugas:* {$audit->auditor->name}\n"
                        . "• *Tanggal Pelaksanaan:* {$audit->audit_date->format('d M Y')}\n\n"
                        . "Harap persiapkan seluruh dokumen dan operasional toko.\n"
                        . "_Sistem Audit (IAMS)_";

                    self::send($auditee->phone, $msg);
                }
            }
        }
    }

    /**
     * Notify Store Auditees when a new Finding is recorded.
     */
    public static function notifyFindingCreated(Finding $finding): void
    {
        $finding->loadMissing(['audit.store.auditees', 'category']);

        $store = $finding->audit->store ?? null;
        if (!$store || !$store->auditees) return;

        foreach ($store->auditees as $auditee) {
            if (!empty($auditee->phone)) {
                $loss = $finding->loss_amount > 0 ? "Rp " . number_format($finding->loss_amount, 0, ',', '.') : "-";

                $msg = "⚠️ *TEMUAN AUDIT BARU (FINDING)*\n\n"
                    . "Halo *{$auditee->name}* ({$store->name}),\n"
                    . "Terdapat temuan audit baru yang memerlukan tindak lanjut:\n\n"
                    . "• *No. Audit:* {$finding->audit->audit_number}\n"
                    . "• *Kategori:* {$finding->category->name}\n"
                    . "• *Severity:* [{$finding->severity}]\n"
                    . "• *Estimasi Kerugian:* {$loss}\n"
                    . "• *Temuan:* {$finding->finding}\n"
                    . "• *Rekomendasi:* {$finding->recommendation}\n\n"
                    . "Mohon segera mengisi *Action Plan & Target Deadline* di portal Toko.\n"
                    . "_Sistem Audit (IAMS)_";

                self::send($auditee->phone, $msg);
            }
        }
    }

    /**
     * Notify Auditor when Store uploads improvement evidence.
     */
    public static function notifyEvidenceUploaded(Evidence $evidence): void
    {
        $evidence->loadMissing(['finding.audit.auditor', 'finding.audit.store', 'uploader']);

        $auditor = $evidence->finding->audit->auditor ?? null;
        $store   = $evidence->finding->audit->store ?? null;

        if ($auditor && !empty($auditor->phone)) {
            $msg = "📤 *BUKTI PERBAIKAN DIUNGGAH*\n\n"
                . "Halo *{$auditor->name}*,\n"
                . "Toko *{$store->name}* telah mengunggah bukti perbaikan:\n\n"
                . "• *No. Audit:* {$evidence->finding->audit->audit_number}\n"
                . "• *Temuan:* " . \Illuminate\Support\Str::limit($evidence->finding->finding, 70) . "\n"
                . "• *Keterangan Bukti:* {$evidence->description}\n"
                . "• *Diunggah Oleh:* " . ($evidence->uploader->name ?? 'Toko') . "\n\n"
                . "Silakan buka menu *Verification Queue* di portal Auditor untuk memverifikasi.\n"
                . "_Sistem Audit (IAMS)_";

            self::send($auditor->phone, $msg);
        }
    }

    /**
     * Notify Store when Evidence is Approved or Rejected.
     */
    public static function notifyEvidenceVerified(Evidence $evidence): void
    {
        $evidence->loadMissing(['finding.audit.store.auditees', 'verifier']);

        $store = $evidence->finding->audit->store ?? null;
        if (!$store || !$store->auditees) return;

        $isApproved = $evidence->verification_status === 'APPROVED';
        $icon = $isApproved ? "✅" : "❌";
        $statusText = $isApproved ? "DISETUJUI (APPROVED)" : "DITOLAK (REJECTED)";

        foreach ($store->auditees as $auditee) {
            if (!empty($auditee->phone)) {
                $msg = "{$icon} *STATUS VERIFIKASI BUKTI PERBAIKAN*\n\n"
                    . "Halo *{$auditee->name}* ({$store->name}),\n"
                    . "Bukti perbaikan temuan audit Anda telah diverifikasi:\n\n"
                    . "• *Status:* *{$statusText}*\n"
                    . "• *Temuan:* " . \Illuminate\Support\Str::limit($evidence->finding->finding, 70) . "\n"
                    . ($isApproved ? "" : "• *Alasan Penolakan:* " . ($evidence->rejection_reason ?: 'Harap perbaiki dan unggah ulang bukti yang valid.') . "\n")
                    . "\n_Sistem Audit (IAMS)_";

                self::send($auditee->phone, $msg);
            }
        }
    }

    /**
     * Notify Store when a Finding is Closed.
     */
    public static function notifyFindingClosed(Finding $finding): void
    {
        $finding->loadMissing(['audit.store.auditees', 'category']);

        $store = $finding->audit->store ?? null;
        if (!$store || !$store->auditees) return;

        foreach ($store->auditees as $auditee) {
            if (!empty($auditee->phone)) {
                $msg = "🎉 *TEMUAN AUDIT RESMI DITUTUP (CLOSED)*\n\n"
                    . "Halo *{$auditee->name}* ({$store->name}),\n"
                    . "Temuan audit berikut telah dinyatakan selesai & resmi ditutup oleh Auditor:\n\n"
                    . "• *No. Audit:* {$finding->audit->audit_number}\n"
                    . "• *Kategori:* {$finding->category->name}\n"
                    . "• *Temuan:* {$finding->finding}\n\n"
                    . "Terima kasih atas kerja sama dan tindak lanjut perbaikan toko Anda.\n"
                    . "_Sistem Audit (IAMS)_";

                self::send($auditee->phone, $msg);
            }
        }
    }

    /**
     * Notify Auditor when Coordinator reviews / adjusts Severity.
     */
    public static function notifySeverityReviewed(Finding $finding, string $oldSeverity): void
    {
        $finding->loadMissing(['audit.auditor', 'audit.store', 'severityReviewer']);

        $auditor  = $finding->audit->auditor ?? null;
        $store    = $finding->audit->store ?? null;
        $reviewer = $finding->severityReviewer->name ?? 'Koordinator Audit';

        if ($auditor && !empty($auditor->phone)) {
            $isAdjusted = $finding->severity !== $oldSeverity;
            $statusText = $isAdjusted ? "DISESUAIKAN (ADJUSTED)" : "DISETUJUI (APPROVED)";

            $msg = "⚖️ *REVIEW SEVERITY OLEH KOORDINATOR*\n\n"
                . "Halo *{$auditor->name}*,\n"
                . "Severity untuk temuan audit Anda telah direview oleh Koordinator (*{$reviewer}*):\n\n"
                . "• *No. Audit:* {$finding->audit->audit_number}\n"
                . "• *Toko:* {$store->name}\n"
                . "• *Temuan:* " . \Illuminate\Support\Str::limit($finding->finding, 70) . "\n"
                . "• *Severity Awal:* [{$oldSeverity}]\n"
                . "• *Severity Final:* *[{$finding->severity}]* ({$statusText})\n"
                . ($finding->severity_notes ? "• *Catatan Koordinator:* {$finding->severity_notes}\n" : "")
                . "• *Status Kunci:* 🔒 *Terkunci (Auditor tidak dapat mengubah lagi)*\n\n"
                . "_Sistem Audit (IAMS)_";

            self::send($auditor->phone, $msg);
        }
    }

    /**
     * Send Reminder to Store Auditees about Action Plan Deadlines / Follow-ups.
     */
    public static function notifyActionPlanReminder(ActionPlan $actionPlan, string $type = 'UPCOMING'): int
    {
        $actionPlan->loadMissing(['finding.audit.store.auditees', 'finding.category']);

        $store = $actionPlan->finding->audit->store ?? null;
        if (!$store || !$store->auditees) return 0;

        $sentCount = 0;
        $deadlineStr = $actionPlan->deadline ? $actionPlan->deadline->format('d M Y') : 'Belum ditentukan';

        foreach ($store->auditees as $auditee) {
            if (empty($auditee->phone)) continue;

            if ($type === 'OVERDUE') {
                $msg = "🚨 *PERINGATAN: ACTION PLAN MELEWATI DEADLINE (OVERDUE)*\n\n"
                    . "Halo *{$auditee->name}* ({$store->name}),\n"
                    . "Action Plan untuk temuan audit berikut *TELAH MELEWATI BATAS WAKTU*:\n\n"
                    . "• *No. Audit:* {$actionPlan->finding->audit->audit_number}\n"
                    . "• *Kategori:* {$actionPlan->finding->category->name}\n"
                    . "• *Temuan:* " . \Illuminate\Support\Str::limit($actionPlan->finding->finding, 70) . "\n"
                    . "• *Batas Deadline:* *{$deadlineStr} (TERLAMBAT)*\n"
                    . "• *PIC Bertugas:* " . ($actionPlan->pic ?: '-') . "\n\n"
                    . "Mohon segera selesaikan tindakan perbaikan dan unggah bukti di portal Toko.\n"
                    . "_Sistem Audit (IAMS)_";
            } elseif ($type === 'UNFILLED') {
                $msg = "⏰ *PENGINGAT: PENGISIAN ACTION PLAN TOKO*\n\n"
                    . "Halo *{$auditee->name}* ({$store->name}),\n"
                    . "Terdapat temuan audit yang *BELUM DIBUATKAN ACTION PLAN*:\n\n"
                    . "• *No. Audit:* {$actionPlan->finding->audit->audit_number}\n"
                    . "• *Kategori:* {$actionPlan->finding->category->name}\n"
                    . "• *Temuan:* " . \Illuminate\Support\Str::limit($actionPlan->finding->finding, 70) . "\n\n"
                    . "Harap segera login ke portal Toko untuk menentukan rencana perbaikan, PIC, dan target deadline.\n"
                    . "_Sistem Audit (IAMS)_";
            } else {
                // UPCOMING
                $msg = "⏰ *PENGINGAT DEADLINE ACTION PLAN*\n\n"
                    . "Halo *{$auditee->name}* ({$store->name}),\n"
                    . "Mengingatkan batas waktu pelaksanaan Action Plan perbaikan audit:\n\n"
                    . "• *No. Audit:* {$actionPlan->finding->audit->audit_number}\n"
                    . "• *Kategori:* {$actionPlan->finding->category->name}\n"
                    . "• *Rencana:* " . ($actionPlan->action_plan ? \Illuminate\Support\Str::limit($actionPlan->action_plan, 70) : '-') . "\n"
                    . "• *Target Deadline:* *{$deadlineStr}*\n"
                    . "• *PIC:* " . ($actionPlan->pic ?: '-') . "\n\n"
                    . "Pastikan perbaikan sudah selesai dan bukti telah diunggah sebelum deadline berakhir.\n"
                    . "_Sistem Audit (IAMS)_";
            }

            if (self::send($auditee->phone, $msg)) {
                $sentCount++;
            }
        }

        return $sentCount;
    }

    /**
     * Send Reminder to Auditor & Store (H-1 before Audit date).
     */
    public static function notifyAuditH1Reminder(Audit $audit): int
    {
        $audit->loadMissing(['store.auditees', 'auditor']);
        $sentCount = 0;

        // 1. To Auditor
        if ($audit->auditor && !empty($audit->auditor->phone)) {
            $msg = "🔔 *PENGINGAT AUDIT BESOK HARI (H-1)*\n\n"
                . "Halo *{$audit->auditor->name}*,\n"
                . "Mengingatkan jadwal audit lapangan yang akan dilaksanakan *BESOK*:\n\n"
                . "• *No. Audit:* {$audit->audit_number}\n"
                . "• *Toko:* {$audit->store->name} ({$audit->store->code})\n"
                . "• *Tanggal:* {$audit->audit_date->format('d M Y')}\n\n"
                . "Harap siapkan perlengkapan audit dan berkas checklist.\n"
                . "_Sistem Audit (IAMS)_";

            if (self::send($audit->auditor->phone, $msg)) {
                $sentCount++;
            }
        }

        // 2. To Store Auditees
        if ($audit->store && $audit->store->auditees) {
            foreach ($audit->store->auditees as $auditee) {
                if (!empty($auditee->phone)) {
                    $msg = "🔔 *PENGINGAT AUDIT TOKO BESOK HARI (H-1)*\n\n"
                        . "Halo *{$auditee->name}* ({$audit->store->name}),\n"
                        . "Pemeriksaan audit toko Anda akan dilaksanakan *BESOK*:\n\n"
                        . "• *No. Audit:* {$audit->audit_number}\n"
                        . "• *Auditor:* {$audit->auditor->name}\n"
                        . "• *Tanggal:* {$audit->audit_date->format('d M Y')}\n\n"
                        . "Harap pastikan kesiapan personil toko dan dokumen terkait.\n"
                        . "_Sistem Audit (IAMS)_";

                    if (self::send($auditee->phone, $msg)) {
                        $sentCount++;
                    }
                }
            }
        }

        return $sentCount;
    }
}
