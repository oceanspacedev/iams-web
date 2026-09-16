<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\AuditCategory;
use App\Models\Finding;
use App\Models\Store;
use App\Services\ReportExportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        protected ReportExportService $exportService
    ) {}

    public function index(Request $request): Response
    {
        $categoryId = $request->query('category_id');

        $baseFindingQuery = function () use ($categoryId) {
            $q = Finding::query();
            if ($categoryId && $categoryId !== 'all') {
                $q->where(function ($sub) use ($categoryId) {
                    $sub->where('category_id', $categoryId)
                        ->orWhereHas('audit', fn ($aq) => $aq->where('category_id', $categoryId));
                });
            }
            return $q;
        };

        $bySeverity = [
            'CRITICAL'    => $baseFindingQuery()->where('severity', 'CRITICAL')->count(),
            'MAJOR'       => $baseFindingQuery()->where('severity', 'MAJOR')->count(),
            'MINOR'       => $baseFindingQuery()->where('severity', 'MINOR')->count(),
            'OBSERVATION' => $baseFindingQuery()->where('severity', 'OBSERVATION')->count(),
        ];

        $byStatus = [
            'OPEN'                 => $baseFindingQuery()->where('status', Finding::STATUS_OPEN)->count(),
            'IN_PROGRESS'          => $baseFindingQuery()->where('status', Finding::STATUS_IN_PROGRESS)->count(),
            'WAITING_VERIFICATION' => $baseFindingQuery()->where('status', Finding::STATUS_WAITING_VERIFICATION)->count(),
            'VERIFIED'             => $baseFindingQuery()->where('status', Finding::STATUS_VERIFIED)->count(),
            'CLOSED'               => $baseFindingQuery()->where('status', Finding::STATUS_CLOSED)->count(),
        ];

        $byCategory = AuditCategory::active()
            ->withCount('findings')
            ->get()
            ->map(fn ($cat) => [
                'id'         => $cat->id,
                'name'       => $cat->name,
                'count'      => $cat->findings_count,
                'total_loss' => (float) $cat->findings()->sum('loss_amount'),
            ]);

        $storeLosses = Store::withCount('audits')
            ->get()
            ->map(function ($s) use ($categoryId) {
                $findingQ = Finding::whereHas('audit', fn ($q) => $q->where('store_id', $s->id));
                if ($categoryId && $categoryId !== 'all') {
                    $findingQ->where(function ($sub) use ($categoryId) {
                        $sub->where('category_id', $categoryId)
                            ->orWhereHas('audit', fn ($aq) => $aq->where('category_id', $categoryId));
                    });
                }
                $totalLoss = $findingQ->sum('loss_amount');
                $totalFindings = $findingQ->count();
                return [
                    'store_code'     => $s->code,
                    'store_name'     => $s->name,
                    'area'           => $s->area,
                    'total_audits'   => $s->audits_count,
                    'total_findings' => $totalFindings,
                    'total_loss'     => (float) $totalLoss,
                ];
            })
            ->sortByDesc('total_loss')
            ->values();

        $totalFindings = $baseFindingQuery()->count();
        $closedFindings = $baseFindingQuery()->where('status', Finding::STATUS_CLOSED)->count();
        $closedOnTime = $baseFindingQuery()->closedOnTime()->count();
        $closedOverdue = $baseFindingQuery()->closedOverdue()->count();
        $completionRate = $totalFindings > 0 ? round(($closedFindings / $totalFindings) * 100, 1) : 0;

        $categories = AuditCategory::active()->orderBy('id')->get(['id', 'name']);

        return Inertia::render('Coordinator/Reports/Index', [
            'by_severity'       => $bySeverity,
            'by_status'         => $byStatus,
            'by_category'       => $byCategory,
            'store_losses'      => $storeLosses,
            'total_loss'        => (float) $baseFindingQuery()->sum('loss_amount'),
            'total_findings'    => $totalFindings,
            'completion_rate'   => $completionRate,
            'closed_on_time'    => $closedOnTime,
            'closed_overdue'    => $closedOverdue,
            'categories'        => $categories,
            'selected_category' => $categoryId ? (string) $categoryId : 'all',
        ]);
    }

    public function exportFindings(Request $request): StreamedResponse
    {
        return $this->exportService->exportFindings($request->query('category_id'));
    }

    public function exportStores(Request $request): StreamedResponse
    {
        return $this->exportService->exportStores($request->query('category_id'));
    }

    public function exportSummary(Request $request): StreamedResponse
    {
        return $this->exportService->exportSummary($request->query('category_id'));
    }
}
