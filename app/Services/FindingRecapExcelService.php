<?php

namespace App\Services;

use App\Models\Finding;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Options\PageOrientation;
use OpenSpout\Writer\XLSX\Options\PageSetup;
use OpenSpout\Writer\XLSX\Options\PaperSize;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Export .xlsx dengan format rekap operasional:
 * - findings(): sheet "REKAP TEMUAN AUDIT RETAIL" (ringkasan + daftar temuan per toko & kategori).
 * - periodRecap(): sheet resume status OPEN / ON PROGRES / CLOSED per bulan per tipe toko,
 *   ditambah satu sheet rekap temuan untuk tiap bulan yang punya temuan.
 */
class FindingRecapExcelService
{
    public const MONTHS = [
        1 => 'JANUARI', 2 => 'FEBRUARI', 3 => 'MARET', 4 => 'APRIL', 5 => 'MEI', 6 => 'JUNI',
        7 => 'JULI', 8 => 'AGUSTUS', 9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DESEMBER',
    ];

    // Status di file mengikuti istilah rekap operasional
    private const STATUS_OPEN = 'OPEN';
    private const STATUS_PROGRESS = 'ON PROGRES';
    private const STATUS_CLOSED = 'CLOSED';

    private const TYPE_LABELS = [
        'toko'        => 'TOKO',
        'gudang'      => 'GUDANG',
        'head_office' => 'HO',
        'hub'         => 'HUB',
    ];

    // Warna sesuai template
    private const NAVY = '1F4E78';
    private const YELLOW = 'FFFF00';
    private const CYAN = '00B0F0';
    private const TAN = 'C4BD97';

    // Style yang membungkus teks; tinggi barisnya dihitung dari panjang teks
    private const WRAP_STYLES = ['text', 'textCenter', 'tableHead', 'resumeHead', 'category', 'title'];

    private Options $options;

    /** @var array<string, Style> */
    private array $styles = [];

    /** @var array<int, float|int> lebar kolom sheet aktif (0-based) */
    private array $colWidths = [];

    // ---------------------------------------------------------------------
    // Export publik
    // ---------------------------------------------------------------------

    /**
     * Rekap temuan untuk satu toko / satu bulan (atau semua).
     *
     * @param  string|null  $period  format Y-m, null = semua periode
     */
    public function findings(?int $storeId = null, ?string $period = null): BinaryFileResponse
    {
        [$start, $end] = $this->periodRange($period);
        $store = $storeId ? Store::find($storeId) : null;

        $findings = $this->findingsFor($storeId, $start, $end);

        $nameParts = ['Rekap_Temuan_Audit'];
        if ($store) {
            $nameParts[] = $store->code ?: $store->name;
        }
        $nameParts[] = $start ? $this->monthLabel($start, true) : 'Semua_Periode';
        $filename = $this->safeFilename(implode('_', $nameParts)) . '.xlsx';

        return $this->write($filename, function (Writer $writer) use ($findings, $start, $end, $store) {
            $sheet = $writer->getCurrentSheet();
            $sheet->setName($start ? $this->monthLabel($start) : 'REKAP TEMUAN');
            $this->writeFindingSheet($writer, $findings, $start, $end, $store);
        });
    }

    /**
     * Resume status per bulan dalam satu tahun + sheet rekap temuan per bulan.
     */
    public function periodRecap(int $year, ?int $storeId = null): BinaryFileResponse
    {
        $store = $storeId ? Store::find($storeId) : null;
        $start = Carbon::create($year, 1, 1)->startOfDay();
        $end = $start->copy()->endOfYear();

        $findings = $this->findingsFor($storeId, $start, $end);
        $byMonth = $findings->groupBy(fn (Finding $f) => (int) $f->audit->audit_date->format('n'));

        $types = $this->storeTypes($store);

        $filename = $this->safeFilename('Rekap_Status_Temuan_' . $year . ($store ? '_' . ($store->code ?: $store->name) : '')) . '.xlsx';

        return $this->write($filename, function (Writer $writer) use ($year, $byMonth, $types, $store) {
            $writer->getCurrentSheet()->setName('RESUME ' . $year);
            $this->writeResumeSheet($writer, $year, $byMonth, $types, $store);

            foreach (range(1, 12) as $month) {
                if (!$byMonth->has($month)) {
                    continue;
                }
                $monthStart = Carbon::create($year, $month, 1)->startOfDay();
                $sheet = $writer->addNewSheetAndMakeItCurrent();
                $sheet->setName($this->monthLabel($monthStart));
                $this->writeFindingSheet($writer, $byMonth->get($month), $monthStart, $monthStart->copy()->endOfMonth(), $store);
            }
        });
    }

    /**
     * Tahun yang punya audit, untuk pilihan di UI. Tahun berjalan selalu ada.
     *
     * @return array<int>
     */
    public static function availableYears(): array
    {
        $years = \App\Models\Audit::query()
            ->whereNotNull('audit_date')
            ->pluck('audit_date')
            ->map(fn ($d) => (int) Carbon::parse($d)->format('Y'))
            ->push((int) now()->format('Y'))
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        return $years;
    }

    /**
     * Bulan (Y-m) yang punya audit, untuk filter di halaman Temuan.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function availablePeriods(): array
    {
        return \App\Models\Audit::query()
            ->whereNotNull('audit_date')
            ->pluck('audit_date')
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m'))
            ->unique()
            ->sortDesc()
            ->values()
            ->map(function (string $ym) {
                $date = Carbon::createFromFormat('Y-m-d', $ym . '-01');

                return [
                    'value' => $ym,
                    'label' => ucfirst(strtolower(self::MONTHS[(int) $date->format('n')])) . ' ' . $date->format('Y'),
                ];
            })
            ->all();
    }

    // ---------------------------------------------------------------------
    // Sheet: rekap temuan
    // ---------------------------------------------------------------------

    private function writeFindingSheet(Writer $writer, Collection $findings, ?Carbon $start, ?Carbon $end, ?Store $store): void
    {
        $sheet = $writer->getCurrentSheet();
        $idx = $sheet->getIndex();

        // A NO, B NAMA TOKO, C TANGGAL, D TEMUAN, E NOMINAL, F SOP, G OPINI, H REKOMENDASI,
        // I ACTION PLAN, J PIC, K DEADLINE, L STATUS, M TIM, N KATEGORI, O TANGGAL SELESAI, P STATUS PENYELESAIAN
        $this->applyWidths($sheet, [6, 22, 13, 60, 22, 28, 42, 42, 36, 18, 13, 14, 22, 13, 14, 20]);

        // Baris header tabel = judul + baris ringkasan + spasi + 1. Header dibekukan saat scroll.
        $summaryLines = max(5, count(Finding::severityOptions()) + 4);
        $headerRow = 1 + $summaryLines + 1 + 1;
        $sheet->setSheetView((new SheetView())->setFreezeRow($headerRow + 1)->setZoomScale(90));

        $rowNo = 0;

        // Judul
        $title = 'REKAP TEMUAN AUDIT RETAIL';
        if ($store) {
            $title .= ' - ' . strtoupper($store->name);
        }
        $title .= "\n" . ($start
            ? 'PERIODE ' . $this->monthLabel($start) . ' (' . $start->format('d-m-Y') . ' S/D ' . $end->format('d-m-Y') . ')'
            : 'SEMUA PERIODE');
        $writer->addRow($this->row([1 => [$title, 'title']], 48));
        $rowNo++;
        $this->options->mergeCells(1, $rowNo, 5, $rowNo, $idx);

        // Ringkasan: tabel FINDING (B-C) dan KATEGORI (E-F)
        $statusCounts = [
            self::STATUS_OPEN     => 0,
            self::STATUS_PROGRESS => 0,
            self::STATUS_CLOSED   => 0,
        ];
        foreach ($findings as $f) {
            $statusCounts[$this->statusLabel($f->status)]++;
        }
        $total = $findings->count();
        $severities = Finding::severityOptions();
        $severityCounts = [];
        foreach ($severities as $key => $opt) {
            $severityCounts[strtoupper($opt['label'])] = $findings->filter(fn ($f) => $this->normalizeSeverity($f->severity) === $key)->count();
        }

        $left = [
            ['FINDING', 'JUMLAH', 'head'],
            [self::STATUS_OPEN, $statusCounts[self::STATUS_OPEN], 'cell'],
            [self::STATUS_PROGRESS, $statusCounts[self::STATUS_PROGRESS], 'cell'],
            [self::STATUS_CLOSED, $statusCounts[self::STATUS_CLOSED], 'cell'],
            ['TOTAL TEMUAN', $total, 'cellBold'],
        ];
        $right = [['KATEGORI', 'JUMLAH', 'head']];
        foreach ($severityCounts as $label => $count) {
            $right[] = [$label, $count, 'cell'];
        }
        $right[] = ['PERSENTASE CLOSED', $total ? $statusCounts[self::STATUS_CLOSED] / $total : 0, 'pct'];
        $right[] = ['PERSENTASE OPEN', $total ? ($statusCounts[self::STATUS_OPEN] + $statusCounts[self::STATUS_PROGRESS]) / $total : 0, 'pct'];
        $right[] = ['PERSENTASE TOTAL', $total ? 1 : 0, 'pctBold'];

        $lines = max(count($left), count($right));
        for ($i = 0; $i < $lines; $i++) {
            $cells = [];
            if (isset($left[$i])) {
                [$label, $value, $kind] = $left[$i];
                $cells[1] = [$label, $kind === 'head' ? 'summaryHead' : ($kind === 'cellBold' ? 'summaryBold' : 'summary')];
                $cells[2] = [$value, $kind === 'head' ? 'summaryHead' : ($kind === 'cellBold' ? 'summaryBold' : 'summary')];
            }
            if (isset($right[$i])) {
                [$label, $value, $kind] = $right[$i];
                $labelStyle = match ($kind) {
                    'head'    => 'summaryHead',
                    'pctBold' => 'summaryBold',
                    default   => 'summary',
                };
                $valueStyle = match ($kind) {
                    'head'    => 'summaryHead',
                    'pct'     => 'summaryPct',
                    'pctBold' => 'summaryPctBold',
                    default   => 'summary',
                };
                $cells[4] = [$label, $labelStyle];
                $cells[5] = [$value, $valueStyle];
            }
            $writer->addRow($this->row($cells));
            $rowNo++;
        }

        // Spasi
        $writer->addRow($this->row([]));
        $rowNo++;

        // Header tabel
        $headers = ['NO', 'NAMA TOKO', 'TANGGAL', 'TEMUAN AUDIT', 'NOMINAL KERUGIAN', 'SOP/SE', 'OPINI AUDITOR',
            'REKOMENDASI PERBAIKAN', 'ACTION PLAN', 'PIC', 'DEADLINE', 'STATUS', 'TIM', 'KATEGORI',
            'TANGGAL SELESAI', 'STATUS PENYELESAIAN'];
        $headerCells = [];
        foreach ($headers as $i => $h) {
            $headerCells[$i] = [$h, 'tableHead'];
        }
        $writer->addRow($this->row($headerCells, 36));
        $rowNo++;

        if ($findings->isEmpty()) {
            $writer->addRow($this->row([3 => ['Tidak ada temuan pada periode ini', 'text']]));

            return;
        }

        $no = 0;
        foreach ($findings->groupBy('audit_id') as $auditFindings) {
            $audit = $auditFindings->first()->audit;
            $no++;
            $groupStart = $rowNo + 1;
            $first = true;

            $byCategory = $auditFindings->groupBy(fn ($f) => $f->category?->name ?? 'Lainnya');
            foreach ($byCategory as $categoryName => $items) {
                // Baris kategori
                $cells = $this->groupCells($first ? $no : null, $first ? $audit : null);
                $cells[3] = [$categoryName, 'category'];
                for ($c = 4; $c <= 15; $c++) {
                    $cells[$c] = ['', 'categoryFill'];
                }
                $writer->addRow($this->row($cells));
                $rowNo++;
                $first = false;

                foreach ($items as $f) {
                    $cells = $this->groupCells(null, null);
                    $status = $this->statusLabel($f->status);

                    $cells[3] = [$f->finding, 'text'];
                    if ((float) $f->loss_amount > 0) {
                        $cells[4] = [(float) $f->loss_amount, 'money'];
                    } elseif ((float) $f->surplus_amount > 0) {
                        $cells[4] = ['NBL Rp ' . number_format((float) $f->surplus_amount, 0, ',', '.'), 'textCenter'];
                    } else {
                        $cells[4] = ['', 'text'];
                    }
                    $cells[5] = [$f->sop ? trim($f->sop->code . ' ' . $f->sop->title) : '', 'textCenter'];
                    $cells[6] = [$f->auditor_opinion ?? '', 'text'];
                    $cells[7] = [$f->recommendation ?? '', 'text'];
                    $cells[8] = [$f->actionPlan?->action_plan ?? '', 'text'];
                    $cells[9] = [$f->actionPlan?->pic ?? '', 'textCenter'];
                    $cells[10] = [$f->actionPlan?->deadline ? Carbon::parse($f->actionPlan->deadline)->toDateTimeImmutable() : '', 'date'];
                    $cells[11] = [$status, match ($status) {
                        self::STATUS_CLOSED   => 'statusClosed',
                        self::STATUS_PROGRESS => 'statusProgress',
                        default               => 'statusOpen',
                    }];
                    $cells[12] = [$this->teamNames($audit), 'textCenter'];
                    $cells[13] = [strtoupper($severities[$this->normalizeSeverity($f->severity)]['label'] ?? (string) $f->severity), 'textCenter'];
                    $closedDate = $f->closed_at ?? ($status === self::STATUS_CLOSED ? $f->updated_at : null);
                    $cells[14] = [$closedDate ? Carbon::parse($closedDate)->toDateTimeImmutable() : '', 'date'];
                    $cells[15] = [$this->completionLabel($f, $status), 'textCenter'];

                    $writer->addRow($this->row($cells));
                    $rowNo++;
                }
            }

            // NO, NAMA TOKO, TANGGAL digabung sepanjang grup toko
            if ($rowNo > $groupStart) {
                foreach ([0, 1, 2] as $col) {
                    $this->options->mergeCells($col, $groupStart, $col, $rowNo, $idx);
                }
            }

            // Baris kosong antar toko
            $writer->addRow($this->row([]));
            $rowNo++;
        }
    }

    /**
     * Sel NO / NAMA TOKO / TANGGAL. Nilai hanya di baris pertama grup (lalu di-merge).
     */
    private function groupCells(?int $no, $audit): array
    {
        return [
            0 => [$no ?? '', 'groupCell'],
            1 => [$audit ? ($audit->store?->name ?? '-') : '', 'groupCell'],
            2 => [$audit?->audit_date ? Carbon::parse($audit->audit_date)->toDateTimeImmutable() : '', 'groupDate'],
        ];
    }

    // ---------------------------------------------------------------------
    // Sheet: resume per periode
    // ---------------------------------------------------------------------

    private function writeResumeSheet(Writer $writer, int $year, Collection $byMonth, array $types, ?Store $store): void
    {
        $sheet = $writer->getCurrentSheet();
        $idx = $sheet->getIndex();

        $blockWidth = 1 + count($types) * 2 + 2; // label + (jumlah, %) per tipe + total
        $gap = 1;
        $rightStart = $blockWidth + $gap;

        // Lebar kolom
        $widths = [];
        foreach ([0, $rightStart] as $base) {
            $widths[$base] = 15;
            for ($c = 1; $c < $blockWidth; $c++) {
                $widths[$base + $c] = 8;
            }
        }
        $widths[$blockWidth] = 3;
        $this->applyWidths($sheet, $widths);

        $rowNo = 0;

        // Judul
        $title = 'REKAPITULASI FINDING DENGAN STATUS OPEN, ON PROGRES, CLOSED ' . $year;
        if ($store) {
            $title .= ' - ' . strtoupper($store->name);
        }
        $writer->addRow($this->row([0 => [$title, 'resumeTitle']], 24));
        $rowNo++;
        $this->options->mergeCells(0, $rowNo, $rightStart + $blockWidth - 1, $rowNo, $idx);

        $writer->addRow($this->row([]));
        $rowNo++;

        // 6 baris blok: Januari-Juni di kiri, Juli-Desember di kanan
        for ($i = 1; $i <= 6; $i++) {
            $blocks = [
                0           => [$i, $byMonth->get($i, collect())],
                $rightStart => [$i + 6, $byMonth->get($i + 6, collect())],
            ];

            // Baris judul periode
            $cells = [];
            foreach ($blocks as $base => [$month]) {
                $cells[$base] = ['PERIODE ' . self::MONTHS[$month] . ' ' . $year, 'periodHead'];
                for ($c = 1; $c < $blockWidth; $c++) {
                    $cells[$base + $c] = ['', 'periodHead'];
                }
            }
            $writer->addRow($this->row($cells));
            $rowNo++;
            foreach (array_keys($blocks) as $base) {
                $this->options->mergeCells($base, $rowNo, $base + $blockWidth - 1, $rowNo, $idx);
            }

            // Baris header tipe
            $cells = [];
            foreach (array_keys($blocks) as $base) {
                $cells[$base] = ['', 'resumeHead'];
                $c = $base + 1;
                foreach ($types as $label) {
                    $cells[$c] = [$label, 'resumeHead'];
                    $cells[$c + 1] = ['', 'resumeHead'];
                    $c += 2;
                }
                $cells[$c] = ['TOTAL', 'resumeHead'];
                $cells[$c + 1] = ['', 'resumeHead'];
            }
            $writer->addRow($this->row($cells, 30));
            $rowNo++;
            foreach (array_keys($blocks) as $base) {
                for ($t = 0; $t <= count($types); $t++) {
                    $col = $base + 1 + $t * 2;
                    $this->options->mergeCells($col, $rowNo, $col + 1, $rowNo, $idx);
                }
            }

            // Hitung per tipe
            $counts = [];
            foreach ($blocks as $base => [$month, $monthFindings]) {
                $counts[$base] = [];
                foreach (array_keys($types) as $type) {
                    $counts[$base][$type] = [self::STATUS_PROGRESS => 0, self::STATUS_OPEN => 0, self::STATUS_CLOSED => 0];
                }
                foreach ($monthFindings as $f) {
                    $type = $this->typeKey($f->audit->store?->type, $types);
                    $counts[$base][$type][$this->statusLabel($f->status)]++;
                }
            }

            // Baris status
            foreach ([self::STATUS_PROGRESS, self::STATUS_OPEN, self::STATUS_CLOSED] as $status) {
                $cells = [];
                foreach (array_keys($blocks) as $base) {
                    $cells[$base] = [$status, 'resumeLabel'];
                    $c = $base + 1;
                    $rowTotal = 0;
                    $grandTotal = 0;
                    foreach (array_keys($types) as $type) {
                        $typeTotal = array_sum($counts[$base][$type]);
                        $value = $counts[$base][$type][$status];
                        $cells[$c] = [$value, 'resumeNum'];
                        $cells[$c + 1] = [$typeTotal ? $value / $typeTotal : 0, 'resumePct'];
                        $rowTotal += $value;
                        $grandTotal += $typeTotal;
                        $c += 2;
                    }
                    $cells[$c] = [$rowTotal, 'resumeTotal'];
                    $cells[$c + 1] = [$grandTotal ? $rowTotal / $grandTotal : 0, 'resumeTotalPct'];
                }
                $writer->addRow($this->row($cells));
                $rowNo++;
            }

            // Baris jumlah
            $cells = [];
            foreach (array_keys($blocks) as $base) {
                $cells[$base] = ['', 'resumeSumLabel'];
                $c = $base + 1;
                $grandTotal = 0;
                foreach (array_keys($types) as $type) {
                    $typeTotal = array_sum($counts[$base][$type]);
                    $cells[$c] = [$typeTotal, 'resumeSum'];
                    $cells[$c + 1] = ['', 'resumeSum'];
                    $grandTotal += $typeTotal;
                    $c += 2;
                }
                $cells[$c] = [$grandTotal, 'resumeTotal'];
                $cells[$c + 1] = ['', 'resumeTotal'];
            }
            $writer->addRow($this->row($cells));
            $rowNo++;

            $writer->addRow($this->row([]));
            $rowNo++;
        }
    }

    // ---------------------------------------------------------------------
    // Data helpers
    // ---------------------------------------------------------------------

    private function findingsFor(?int $storeId, ?Carbon $start, ?Carbon $end): Collection
    {
        return Finding::query()
            ->with(['audit.store', 'audit.auditor', 'audit.auditors', 'category', 'sop', 'actionPlan'])
            ->whereHas('audit', function ($q) use ($storeId, $start, $end) {
                if ($storeId) {
                    $q->where('store_id', $storeId);
                }
                if ($start && $end) {
                    $q->whereBetween('audit_date', [$start->toDateString(), $end->toDateString()]);
                }
            })
            ->get()
            ->filter(fn (Finding $f) => $f->audit && $f->audit->audit_date)
            ->sortBy(fn (Finding $f) => sprintf(
                '%s|%010d|%s|%010d',
                Carbon::parse($f->audit->audit_date)->format('Y-m-d'),
                $f->audit_id,
                $f->category?->name ?? 'zzz',
                $f->id
            ))
            ->values();
    }

    private function periodRange(?string $period): array
    {
        if (!$period || !preg_match('/^\d{4}-\d{2}$/', $period)) {
            return [null, null];
        }
        $start = Carbon::createFromFormat('Y-m-d', $period . '-01')->startOfDay();

        return [$start, $start->copy()->endOfMonth()];
    }

    /**
     * Tipe toko yang dipakai sebagai kolom resume (urutan tetap).
     *
     * @return array<string, string> key tipe => label kolom
     */
    private function storeTypes(?Store $store): array
    {
        // Kolom tetap seperti template (TOKO, GUDANG, HO); tipe lain ditambahkan kalau ada datanya
        $types = ['toko' => 'TOKO', 'gudang' => 'GUDANG', 'head_office' => 'HO'];
        $present = $store
            ? collect([$store->type ? strtolower($store->type) : 'toko'])
            : Store::query()->pluck('type')->map(fn ($t) => $t ? strtolower($t) : 'toko')->unique();

        foreach ($present as $key) {
            if (!isset($types[$key])) {
                $types[$key] = self::TYPE_LABELS[$key] ?? strtoupper(str_replace('_', ' ', $key));
            }
        }

        return $types;
    }

    private function typeKey(?string $type, array $types): string
    {
        $type = $type ? strtolower($type) : 'toko';

        return array_key_exists($type, $types) ? $type : array_key_first($types);
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            Finding::STATUS_CLOSED => self::STATUS_CLOSED,
            Finding::STATUS_OPEN, null => self::STATUS_OPEN,
            default => self::STATUS_PROGRESS,
        };
    }

    private function normalizeSeverity(?string $severity): string
    {
        return match ($severity) {
            'CRITICAL'    => Finding::SEVERITY_MAJOR,
            'OBSERVATION' => Finding::SEVERITY_MINOR,
            null          => Finding::SEVERITY_MINOR,
            default       => $severity,
        };
    }

    private function completionLabel(Finding $f, string $status): string
    {
        if ($status === self::STATUS_CLOSED) {
            return 'SELESAI';
        }
        if ($f->severity === Finding::SEVERITY_CONTINUOUS) {
            return 'MONITORING';
        }
        $deadline = $f->actionPlan?->deadline;
        if (!$deadline) {
            return '-';
        }
        $deadline = Carbon::parse($deadline)->startOfDay();
        $today = now()->startOfDay();
        $days = (int) $today->diffInDays($deadline, false);

        if ($days < 0) {
            return abs($days) . ' hari terlambat';
        }

        return $days === 0 ? 'Hari ini' : $days . ' hari lagi';
    }

    private function teamNames($audit): string
    {
        $names = collect([$audit->auditor?->name])
            ->merge($audit->auditors?->pluck('name') ?? [])
            ->filter()
            ->unique()
            ->values();

        return $names->implode(', ');
    }

    private function monthLabel(Carbon $date, bool $forFilename = false): string
    {
        $label = self::MONTHS[(int) $date->format('n')] . ' ' . $date->format('Y');

        return $forFilename ? str_replace(' ', '_', ucwords(strtolower($label))) : $label;
    }

    private function safeFilename(string $name): string
    {
        return preg_replace('/[^A-Za-z0-9_\-]+/', '_', $name);
    }

    // ---------------------------------------------------------------------
    // Writer helpers
    // ---------------------------------------------------------------------

    private function write(string $filename, callable $build): BinaryFileResponse
    {
        $this->options = new Options();
        // Cetak: landscape A4, semua kolom muat satu halaman lebar
        $this->options->setPageSetup(new PageSetup(PageOrientation::LANDSCAPE, PaperSize::A4, 0, 1));
        $this->styles = [];
        $this->colWidths = [];

        $path = tempnam(sys_get_temp_dir(), 'iams_xlsx_');
        $writer = new Writer($this->options);
        $writer->openToFile($path);
        $build($writer);
        $writer->close();

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * @param  array<int, array{0: mixed, 1: string}>  $cells  index kolom (0-based) => [nilai, nama style]
     */
    private function row(array $cells, ?float $height = null): Row
    {
        $max = $cells ? max(array_keys($cells)) : 0;
        $list = [];
        for ($i = 0; $i <= $max; $i++) {
            if (isset($cells[$i])) {
                [$value, $style] = $cells[$i];
                $list[] = Cell::fromValue($value === '' ? null : $value, $this->style($style));
            } else {
                $list[] = Cell::fromValue(null);
            }
        }
        $row = new Row($list);
        $row->setHeight($height ?? $this->fitHeight($cells));

        return $row;
    }

    /**
     * Tinggi baris yang cukup untuk semua teks yang di-wrap.
     * Excel/Google Sheets tidak menyesuaikan tinggi baris sendiri saat file dibuka,
     * jadi tanpa ini teks panjang terpotong dan tersembunyi.
     *
     * @param  array<int, array{0: mixed, 1: string}>  $cells
     */
    private function fitHeight(array $cells): float
    {
        $lineHeight = 13.0; // Arial 10pt
        $lines = 1;

        foreach ($cells as $col => [$value, $style]) {
            if (!is_string($value) || $value === '' || !in_array($style, self::WRAP_STYLES, true)) {
                continue;
            }
            $width = $this->colWidths[$col] ?? 10;
            // Lebar kolom Excel ~ jumlah karakter "0"; teks huruf campuran Arial 10 sedikit lebih rapat.
            // Huruf tebal lebih lebar, jadi kapasitasnya dikurangi.
            $factor = in_array($style, ['tableHead', 'resumeHead', 'category'], true) ? 0.95 : 1.05;
            $perLine = max(1, (int) floor(($width - 1.5) * $factor));

            $cellLines = 0;
            foreach (preg_split('/\r\n|\r|\n/', $value) as $paragraph) {
                $cellLines += $this->wrappedLines(trim($paragraph), $perLine);
            }
            $lines = max($lines, $cellLines);
        }

        return min(409.0, max(15.0, $lines * $lineHeight + 4));
    }

    /**
     * Jumlah baris satu paragraf bila dipotong per kata, seperti yang dilakukan Excel.
     */
    private function wrappedLines(string $text, int $perLine): int
    {
        if ($text === '') {
            return 1;
        }
        $lines = 1;
        $current = 0;
        foreach (preg_split('/\s+/', $text) as $word) {
            $len = mb_strlen($word);
            if ($current === 0) {
                $current = $len;
            } elseif ($current + 1 + $len <= $perLine) {
                $current += 1 + $len;
            } else {
                $lines++;
                $current = $len;
            }
            // Kata yang lebih panjang dari lebar kolom dipotong ke baris berikutnya
            while ($current > $perLine) {
                $lines++;
                $current -= $perLine;
            }
        }

        return $lines;
    }

    /**
     * Set lebar kolom sheet aktif dan simpan untuk perhitungan tinggi baris.
     *
     * @param  array<int, float|int>  $widths  index kolom (0-based) => lebar
     */
    private function applyWidths($sheet, array $widths): void
    {
        ksort($widths);
        foreach ($widths as $col => $width) {
            $sheet->setColumnWidth((float) $width, $col + 1);
        }
        $this->colWidths = $widths;
    }

    private function style(string $name): Style
    {
        if (isset($this->styles[$name])) {
            return $this->styles[$name];
        }

        $s = (new Style())->setFontName('Arial')->setFontSize(10)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER);

        $border = new Border(
            new BorderPart(Border::TOP, '000000', Border::WIDTH_THIN),
            new BorderPart(Border::BOTTOM, '000000', Border::WIDTH_THIN),
            new BorderPart(Border::LEFT, '000000', Border::WIDTH_THIN),
            new BorderPart(Border::RIGHT, '000000', Border::WIDTH_THIN),
        );

        switch ($name) {
            case 'title':
                $s->setFontSize(18)->setFontBold()->setShouldWrapText()->setCellVerticalAlignment(CellVerticalAlignment::BOTTOM);
                break;
            case 'resumeTitle':
                $s->setFontSize(13)->setFontBold();
                break;
            case 'summaryHead':
                $s->setFontBold()->setFontColor('FFFFFF')->setBackgroundColor(self::NAVY)
                    ->setCellAlignment(CellAlignment::CENTER)->setBorder($border);
                break;
            case 'summary':
                $s->setCellAlignment(CellAlignment::CENTER)->setBorder($border);
                break;
            case 'summaryBold':
                $s->setFontBold()->setCellAlignment(CellAlignment::CENTER)->setBorder($border);
                break;
            case 'summaryPct':
                $s->setCellAlignment(CellAlignment::CENTER)->setFormat('0.0%')->setBorder($border);
                break;
            case 'summaryPctBold':
                $s->setFontBold()->setCellAlignment(CellAlignment::CENTER)->setFormat('0.0%')->setBorder($border);
                break;
            case 'tableHead':
                $s->setFontBold()->setBackgroundColor(self::YELLOW)->setCellAlignment(CellAlignment::CENTER)
                    ->setShouldWrapText()->setBorder($border);
                break;
            case 'category':
            case 'categoryFill':
                $s->setFontBold()->setBackgroundColor(self::TAN)->setBorder($border);
                break;
            case 'groupCell':
                $s->setFontBold()->setCellAlignment(CellAlignment::CENTER)->setShouldWrapText()->setBorder($border);
                break;
            case 'groupDate':
                $s->setFontBold()->setCellAlignment(CellAlignment::CENTER)->setFormat('dd/mm/yyyy')->setBorder($border);
                break;
            case 'text':
                $s->setShouldWrapText()->setCellVerticalAlignment(CellVerticalAlignment::TOP)->setBorder($border);
                break;
            case 'textCenter':
                $s->setShouldWrapText()->setCellAlignment(CellAlignment::CENTER)->setBorder($border);
                break;
            case 'money':
                $s->setCellAlignment(CellAlignment::CENTER)->setFormat('"Rp" #,##0')->setBorder($border);
                break;
            case 'date':
                $s->setCellAlignment(CellAlignment::CENTER)->setFormat('dd/mm/yyyy')->setBorder($border);
                break;
            case 'statusClosed':
                $s->setFontBold()->setFontColor('00B050')->setCellAlignment(CellAlignment::CENTER)->setBorder($border);
                break;
            case 'statusProgress':
                $s->setFontBold()->setFontColor('C65911')->setCellAlignment(CellAlignment::CENTER)->setBorder($border);
                break;
            case 'statusOpen':
                $s->setFontBold()->setFontColor('FF0000')->setCellAlignment(CellAlignment::CENTER)->setBorder($border);
                break;
            case 'periodHead':
                $s->setFontBold()->setBackgroundColor(self::CYAN)->setCellAlignment(CellAlignment::CENTER)->setBorder($border);
                break;
            case 'resumeHead':
                $s->setFontBold()->setBackgroundColor(self::YELLOW)->setCellAlignment(CellAlignment::CENTER)
                    ->setShouldWrapText()->setBorder($border);
                break;
            case 'resumeLabel':
                $s->setFontBold()->setBackgroundColor(self::YELLOW)->setBorder($border);
                break;
            case 'resumeNum':
            case 'resumeSum':
                $s->setFontBold()->setCellAlignment(CellAlignment::CENTER)->setBorder($border);
                break;
            case 'resumePct':
                $s->setFontBold()->setCellAlignment(CellAlignment::CENTER)->setFormat('0%')->setBorder($border);
                break;
            case 'resumeSumLabel':
                $s->setBackgroundColor(self::YELLOW)->setBorder($border);
                break;
            case 'resumeTotal':
                $s->setFontBold()->setBackgroundColor(self::YELLOW)->setCellAlignment(CellAlignment::CENTER)->setBorder($border);
                break;
            case 'resumeTotalPct':
                $s->setFontBold()->setBackgroundColor(self::YELLOW)->setCellAlignment(CellAlignment::CENTER)
                    ->setFormat('0.00%')->setBorder($border);
                break;
        }

        return $this->styles[$name] = $s;
    }
}
