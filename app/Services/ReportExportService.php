<?php

namespace App\Services;

use App\Models\AuditCategory;
use App\Models\Finding;
use App\Models\Store;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportService
{
    /**
     * Export all findings to true Excel (.xls) table format.
     */
    public function exportFindings(): StreamedResponse
    {
        $filename = 'Laporan_Temuan_Audit_' . date('Y-m-d_His') . '.xls';

        $headers = [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () {
            $findings = Finding::with([
                'audit.store',
                'audit.auditor',
                'category',
                'sop',
                'severityReviewer',
                'actionPlan',
            ])->orderByDesc('id')->get();

            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            echo '<head>';
            echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
            echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Laporan Temuan</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
            echo '<style>';
            echo 'th { background-color: #1E3A8A; color: #ffffff; font-weight: bold; text-align: center; border: 1px solid #000000; padding: 8px; font-family: Calibri, sans-serif; font-size: 11pt; }';
            echo 'td { border: 1px solid #d1d5db; padding: 6px; font-family: Calibri, sans-serif; font-size: 10pt; vertical-align: top; }';
            echo '.text { mso-number-format:"\@"; }';
            echo '.number { mso-number-format:"\#\,\#\#0"; text-align: right; }';
            echo '.center { text-align: center; }';
            echo '.title { font-size: 14pt; font-weight: bold; font-family: Calibri, sans-serif; color: #1E3A8A; }';
            echo '</style>';
            echo '</head>';
            echo '<body>';

            echo '<table>';
            echo '<tr><td colspan="23" class="title">LAPORAN REKAPITULASI TEMUAN AUDIT RETAIL</td></tr>';
            echo '<tr><td colspan="23" style="color: #6b7280;">Waktu Ekspor: ' . date('d/m/Y H:i:s') . '</td></tr>';
            echo '<tr><td colspan="23"></td></tr>';
            echo '<tr>';
            echo '<th>No</th>';
            echo '<th>Nomor Audit</th>';
            echo '<th>Tanggal Audit</th>';
            echo '<th>Kode Toko</th>';
            echo '<th>Nama Toko</th>';
            echo '<th>Area / Wilayah</th>';
            echo '<th>Auditor</th>';
            echo '<th>Kategori Audit</th>';
            echo '<th>SOP / SE Acuan</th>';
            echo '<th>Severity</th>';
            echo '<th>Status Review Severity</th>';
            echo '<th>Direview Oleh</th>';
            echo '<th>Catatan Review Koordinator</th>';
            echo '<th>Nominal Kerugian (Rp)</th>';
            echo '<th>Uraian Temuan</th>';
            echo '<th>Opini Auditor</th>';
            echo '<th>Rekomendasi</th>';
            echo '<th>Action Plan Toko</th>';
            echo '<th>Response Toko</th>';
            echo '<th>PIC Toko</th>';
            echo '<th>Target Deadline</th>';
            echo '<th>Status Temuan</th>';
            echo '<th>Tanggal Dibuat</th>';
            echo '</tr>';

            $no = 1;
            foreach ($findings as $f) {
                $statusSeverity = $f->is_severity_locked ? ($f->severity_status ?? 'APPROVED') : 'PENDING_REVIEW';
                $reviewer = $f->severityReviewer->name ?? ($f->is_severity_locked ? 'Koordinator' : '-');
                $auditDate = $f->audit->audit_date ? $f->audit->audit_date->format('d/m/Y') : '-';
                $deadline = $f->actionPlan?->deadline ? $f->actionPlan->deadline->format('d/m/Y') : '-';
                $createdAt = $f->created_at ? $f->created_at->format('d/m/Y H:i') : '-';
                $sop = $f->sop ? ($f->sop->code . ' - ' . $f->sop->title) : '-';

                echo '<tr>';
                echo '<td class="center">' . $no++ . '</td>';
                echo '<td class="text center">' . htmlspecialchars($f->audit->audit_number ?? '-') . '</td>';
                echo '<td class="center">' . $auditDate . '</td>';
                echo '<td class="text center">' . htmlspecialchars($f->audit->store->code ?? '-') . '</td>';
                echo '<td>' . htmlspecialchars($f->audit->store->name ?? '-') . '</td>';
                echo '<td>' . htmlspecialchars($f->audit->store->area ?? '-') . '</td>';
                echo '<td>' . htmlspecialchars($f->audit->auditor->name ?? '-') . '</td>';
                echo '<td>' . htmlspecialchars($f->category->name ?? '-') . '</td>';
                echo '<td>' . htmlspecialchars($sop) . '</td>';
                echo '<td class="center font-bold">' . htmlspecialchars($f->severity) . '</td>';
                echo '<td class="center">' . htmlspecialchars($statusSeverity) . '</td>';
                echo '<td>' . htmlspecialchars($reviewer) . '</td>';
                echo '<td>' . htmlspecialchars($f->severity_notes ?? '-') . '</td>';
                echo '<td class="number">' . number_format((float) $f->loss_amount, 0, ',', '.') . '</td>';
                echo '<td>' . nl2br(htmlspecialchars($f->finding)) . '</td>';
                echo '<td>' . nl2br(htmlspecialchars($f->auditor_opinion ?? '-')) . '</td>';
                echo '<td>' . nl2br(htmlspecialchars($f->recommendation ?? '-')) . '</td>';
                echo '<td>' . nl2br(htmlspecialchars($f->actionPlan->action_plan ?? '-')) . '</td>';
                echo '<td>' . nl2br(htmlspecialchars($f->actionPlan->response ?? '-')) . '</td>';
                echo '<td>' . htmlspecialchars($f->actionPlan->pic ?? '-') . '</td>';
                echo '<td class="center">' . $deadline . '</td>';
                echo '<td class="center font-bold">' . htmlspecialchars($f->status) . '</td>';
                echo '<td class="center">' . $createdAt . '</td>';
                echo '</tr>';
            }

            echo '</table>';
            echo '</body>';
            echo '</html>';
        }, 200, $headers);
    }

    /**
     * Export store loss summary to true Excel (.xls) table format.
     */
    public function exportStores(): StreamedResponse
    {
        $filename = 'Rekapitulasi_Kerugian_Toko_' . date('Y-m-d_His') . '.xls';

        $headers = [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () {
            $stores = Store::with(['audits.findings'])->get();

            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            echo '<head>';
            echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
            echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Rekap Toko</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
            echo '<style>';
            echo 'th { background-color: #1E3A8A; color: #ffffff; font-weight: bold; text-align: center; border: 1px solid #000000; padding: 8px; font-family: Calibri, sans-serif; font-size: 11pt; }';
            echo 'td { border: 1px solid #d1d5db; padding: 6px; font-family: Calibri, sans-serif; font-size: 10pt; vertical-align: top; }';
            echo '.text { mso-number-format:"\@"; }';
            echo '.number { mso-number-format:"\#\,\#\#0"; text-align: right; }';
            echo '.center { text-align: center; }';
            echo '.title { font-size: 14pt; font-weight: bold; font-family: Calibri, sans-serif; color: #1E3A8A; }';
            echo '</style>';
            echo '</head>';
            echo '<body>';

            echo '<table>';
            echo '<tr><td colspan="9" class="title">REKAPITULASI KERUGIAN & HASIL AUDIT PER CABANG TOKO</td></tr>';
            echo '<tr><td colspan="9" style="color: #6b7280;">Waktu Ekspor: ' . date('d/m/Y H:i:s') . '</td></tr>';
            echo '<tr><td colspan="9"></td></tr>';
            echo '<tr>';
            echo '<th>No</th>';
            echo '<th>Kode Toko</th>';
            echo '<th>Nama Toko</th>';
            echo '<th>Wilayah / Area</th>';
            echo '<th>Total Pelaksanaan Audit</th>';
            echo '<th>Total Temuan</th>';
            echo '<th>Temuan Open / In Progress</th>';
            echo '<th>Temuan Closed / Selesai</th>';
            echo '<th>Total Akumulasi Kerugian (Rp)</th>';
            echo '</tr>';

            $no = 1;
            $grandTotalLoss = 0;
            $grandTotalAudits = 0;
            $grandTotalFindings = 0;

            foreach ($stores as $s) {
                $allFindings = $s->audits->flatMap->findings;
                $totalLoss = (float) $allFindings->sum('loss_amount');
                $openCount = $allFindings->whereIn('status', [Finding::STATUS_OPEN, Finding::STATUS_IN_PROGRESS, Finding::STATUS_WAITING_VERIFICATION])->count();
                $closedCount = $allFindings->whereIn('status', [Finding::STATUS_VERIFIED, Finding::STATUS_CLOSED])->count();

                $grandTotalLoss += $totalLoss;
                $grandTotalAudits += $s->audits->count();
                $grandTotalFindings += $allFindings->count();

                echo '<tr>';
                echo '<td class="center">' . $no++ . '</td>';
                echo '<td class="text center font-bold">' . htmlspecialchars($s->code) . '</td>';
                echo '<td>' . htmlspecialchars($s->name) . '</td>';
                echo '<td>' . htmlspecialchars($s->area ?? '-') . '</td>';
                echo '<td class="center">' . $s->audits->count() . '</td>';
                echo '<td class="center font-bold">' . $allFindings->count() . '</td>';
                echo '<td class="center">' . $openCount . '</td>';
                echo '<td class="center">' . $closedCount . '</td>';
                echo '<td class="number font-bold">' . number_format($totalLoss, 0, ',', '.') . '</td>';
                echo '</tr>';
            }

            echo '<tr style="background-color: #f3f4f6; font-weight: bold;">';
            echo '<td colspan="4" class="center">TOTAL NASIONAL</td>';
            echo '<td class="center">' . $grandTotalAudits . '</td>';
            echo '<td class="center">' . $grandTotalFindings . '</td>';
            echo '<td colspan="2"></td>';
            echo '<td class="number" style="color: #991b1b;">' . number_format($grandTotalLoss, 0, ',', '.') . '</td>';
            echo '</tr>';

            echo '</table>';
            echo '</body>';
            echo '</html>';
        }, 200, $headers);
    }

    /**
     * Export executive summary to true Excel (.xls) table format.
     */
    public function exportSummary(): StreamedResponse
    {
        $filename = 'Ringkasan_Eksekutif_Audit_' . date('Y-m-d_His') . '.xls';

        $headers = [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () {
            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            echo '<head>';
            echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
            echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Ringkasan Eksekutif</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
            echo '<style>';
            echo 'th { background-color: #1E3A8A; color: #ffffff; font-weight: bold; text-align: center; border: 1px solid #000000; padding: 6px; font-family: Calibri, sans-serif; }';
            echo 'td { border: 1px solid #d1d5db; padding: 6px; font-family: Calibri, sans-serif; font-size: 10pt; }';
            echo '.number { text-align: right; font-weight: bold; }';
            echo '.center { text-align: center; }';
            echo '.header-section { background-color: #f3f4f6; font-weight: bold; font-size: 11pt; color: #1E3A8A; }';
            echo '</style>';
            echo '</head>';
            echo '<body>';

            echo '<table>';
            echo '<tr><td colspan="3" style="font-size: 14pt; font-weight: bold; color: #1E3A8A;">RINGKASAN EKSEKUTIF AUDIT RETAIL</td></tr>';
            echo '<tr><td colspan="3" style="color: #6b7280;">Waktu Ekspor: ' . date('d/m/Y H:i:s') . '</td></tr>';
            echo '<tr><td colspan="3"></td></tr>';

            // 1. Severity Distribution
            echo '<tr><td colspan="2" class="header-section">1. DISTRIBUSI SEVERITY TEMUAN</td></tr>';
            echo '<tr><th>Tingkat Severity</th><th>Jumlah Temuan</th></tr>';
            foreach (['CRITICAL', 'MAJOR', 'MINOR', 'OBSERVATION'] as $sev) {
                echo '<tr>';
                echo '<td>' . $sev . '</td>';
                echo '<td class="number">' . Finding::where('severity', $sev)->count() . '</td>';
                echo '</tr>';
            }
            echo '<tr><td colspan="2"></td></tr>';

            // 2. Status Distribution
            echo '<tr><td colspan="2" class="header-section">2. STATUS PENYELESAIAN TEMUAN</td></tr>';
            echo '<tr><th>Status Workflow</th><th>Jumlah Temuan</th></tr>';
            foreach ([
                'OPEN'                 => 'Open',
                'IN_PROGRESS'          => 'In Progress',
                'WAITING_VERIFICATION' => 'Waiting Verification',
                'VERIFIED'             => 'Verified',
                'CLOSED'               => 'Closed',
            ] as $key => $label) {
                echo '<tr>';
                echo '<td>' . $label . '</td>';
                echo '<td class="number">' . Finding::where('status', $key)->count() . '</td>';
                echo '</tr>';
            }
            echo '<tr><td colspan="2"></td></tr>';

            // 3. Category Distribution
            echo '<tr><td colspan="2" class="header-section">3. TEMUAN PER KATEGORI AUDIT</td></tr>';
            echo '<tr><th>Kategori Audit</th><th>Jumlah Temuan</th></tr>';
            $categories = AuditCategory::withCount('findings')->get();
            foreach ($categories as $cat) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($cat->name) . '</td>';
                echo '<td class="number">' . $cat->findings_count . '</td>';
                echo '</tr>';
            }
            echo '<tr><td colspan="2"></td></tr>';

            // 4. Total Loss
            echo '<tr><td colspan="2" class="header-section">4. TOTAL KERUGIAN FINANSIAL (LOSS AMOUNT)</td></tr>';
            echo '<tr>';
            echo '<td>Total Kerugian Nasional (Rp)</td>';
            echo '<td class="number" style="color: #991b1b; font-size: 11pt;">Rp ' . number_format((float) Finding::sum('loss_amount'), 0, ',', '.') . '</td>';
            echo '</tr>';

            echo '</table>';
            echo '</body>';
            echo '</html>';
        }, 200, $headers);
    }
}
