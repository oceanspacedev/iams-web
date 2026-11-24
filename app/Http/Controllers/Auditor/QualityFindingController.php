<?php

namespace App\Http\Controllers\Auditor;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Models\Finding;
use App\Models\QualityFinding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class QualityFindingController extends Controller
{
    /**
     * This controller serves both the auditor portal (auditor.*) and the coordinator
     * portal (coordinator.*, also used by asmen/chief/admin). Only the auditor portal
     * is scoped to the auditor's own audits (lead or team member).
     */
    private function isAuditorPortal(Request $request): bool
    {
        return $request->routeIs('auditor.*');
    }

    private function routePrefix(Request $request): string
    {
        return $this->isAuditorPortal($request) ? 'auditor.' : 'coordinator.';
    }

    private function canAccess(Request $request, QualityFinding $qualityFinding): bool
    {
        if (!$this->isAuditorPortal($request)) {
            return true;
        }

        return Audit::whereKey($qualityFinding->audit_id)
            ->forAuditor($request->user()->id)
            ->exists();
    }

    public function index(Request $request): Response
    {
        $user = $request->user();

        // Hide reports whose finding or audit was deleted
        $baseQuery = QualityFinding::query()
            ->whereHas('finding')
            ->whereHas('audit');

        if ($this->isAuditorPortal($request)) {
            $baseQuery->whereHas('audit', function ($q) use ($user) {
                $q->forAuditor($user->id);
            });
        }

        $query = (clone $baseQuery)
            ->with(['finding.category', 'finding.sop', 'audit.store', 'reporter'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('category')) {
            $cat = $request->query('category');
            $query->where(function ($q) use ($cat) {
                $q->where('quality_category', 'like', '%"' . $cat . '"%')
                  ->orWhere('quality_category', 'like', '%' . $cat . '%');
            });
        }

        $qualityFindings = $query->get()->map(fn ($qf) => [
            'id'                      => $qf->id,
            'quality_category'        => $qf->quality_categories[0] ?? $qf->quality_category,
            'quality_categories'      => $qf->quality_categories,
            'quality_categories_info' => $qf->quality_categories_info,
            'title'                   => $qf->title,
            'impact_amount'           => (float) $qf->impact_amount,
            'root_cause'              => $qf->root_cause,
            'systemic_issue'          => $qf->systemic_issue,
            'recommendation'          => $qf->recommendation,
            'auditor_notes'           => $qf->auditor_notes,
            'status'                  => $qf->status,
            'reported_by'             => $qf->reporter?->name ?? '-',
            'created_at'              => $qf->created_at ? $qf->created_at->copy()->timezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB' : '-',
            'finding'                 => [
                'id'          => $qf->finding?->id,
                'category'    => $qf->finding?->category?->name ?? '-',
                'finding'     => $qf->finding?->finding ?? '-',
                'severity'    => $qf->finding?->severity ?? 'MINOR',
                'status'      => $qf->finding?->status ?? 'OPEN',
                'loss_amount' => (float) ($qf->finding?->loss_amount ?? 0),
            ],
            'audit'            => [
                'id'           => $qf->audit?->id,
                'audit_number' => $qf->audit?->audit_number ?? '-',
                'store_name'   => $qf->audit?->store?->name ?? ($qf->audit?->location ?: '-'),
                'store_code'   => $qf->audit?->store?->code ?? '-',
                'audit_date'   => $qf->audit?->audit_date?->format('d M Y') ?? '-',
            ],
        ]);

        $countCategory = fn (string $key) => (clone $baseQuery)
            ->where(fn ($q) => $q->where('quality_category', 'like', '%"' . $key . '"%')->orWhere('quality_category', 'like', '%' . $key . '%'))
            ->count();

        $stats = [
            'total'          => (clone $baseQuery)->count(),
            'impact_50m'     => $countCategory('impact_50m'),
            'fraud_risk'     => $countCategory('fraud_risk'),
            'system_control' => $countCategory('system_control'),
            'org_structure'  => $countCategory('org_structure'),
            'total_impact'   => (float) (clone $baseQuery)->sum('impact_amount'),
        ];

        return Inertia::render('Auditor/FindingQualities/Index', [
            'qualityFindings' => $qualityFindings,
            'stats'           => $stats,
            'categories'      => QualityFinding::categories(),
            'selectedCategory'=> $request->query('category', ''),
            'routePrefix'     => $this->routePrefix($request),
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();

        // Get findings from audits assigned to this auditor (or all if admin/coordinator)
        $findingsQuery = Finding::with(['audit.store', 'category'])
            ->whereHas('audit')
            ->whereNotIn('status', [Finding::STATUS_CLOSED])
            ->whereDoesntHave('qualityReport')
            ->orderBy('id', 'desc');

        if ($this->isAuditorPortal($request)) {
            $findingsQuery->whereHas('audit', function ($q) use ($user) {
                $q->forAuditor($user->id);
            });
        }

        $preselectedFindingId = $request->query('finding_id');
        $findingsList = $findingsQuery->get();

        // Temuan yang dipilih lewat link (?finding_id=) selalu ikut di daftar,
        // asalkan belum punya laporan dan (di portal auditor) termasuk audit miliknya.
        if ($preselectedFindingId && ! $findingsList->contains('id', (int) $preselectedFindingId)) {
            $preselectedQuery = Finding::with(['audit.store', 'category'])
                ->whereKey($preselectedFindingId)
                ->whereDoesntHave('qualityReport');

            if ($this->isAuditorPortal($request)) {
                $preselectedQuery->whereHas('audit', fn ($q) => $q->forAuditor($user->id));
            }

            if ($preselected = $preselectedQuery->first()) {
                $findingsList->prepend($preselected);
            }
        }

        $findings = $findingsList->map(function ($f) {
            $auditNum = $f->audit?->audit_number ?? '-';
            $storeName = $f->audit?->store?->name ?? ($f->audit?->location ?: '-');
            $catName = $f->category?->name ?? '-';
            $findingText = $f->finding ?? '-';

            return [
                'id'            => $f->id,
                'audit_id'      => $f->audit_id,
                'audit_number'  => $auditNum,
                'store_name'    => $storeName,
                'store_code'    => $f->audit?->store?->code ?? '-',
                'category'      => $catName,
                'finding'       => $findingText,
                'severity'      => $f->severity ?? 'MINOR',
                'loss_amount'   => (float) ($f->loss_amount ?? 0),
                'display_label' => "[{$auditNum}] {$storeName} - #{$f->id} {$catName} ({$findingText})",
            ];
        });

        return Inertia::render('Auditor/FindingQualities/Create', [
            'findings'         => $findings,
            'categories'       => QualityFinding::categories(),
            'preselectedAudit' => $request->query('audit_id'),
            'preselectedFinding' => $preselectedFindingId,
            'routePrefix'      => $this->routePrefix($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Support both quality_categories (array) and legacy quality_category (string or array)
        $categoriesInput = $request->input('quality_categories', $request->input('quality_category'));
        if (is_string($categoriesInput)) {
            $categoriesInput = [$categoriesInput];
        }
        $request->merge(['quality_categories' => $categoriesInput]);

        $validated = $request->validate([
            'finding_id'           => [
                'required',
                Rule::exists('findings', 'id')->whereNull('deleted_at'),
                Rule::unique('quality_findings', 'finding_id')->whereNull('deleted_at'),
            ],
            'quality_categories'   => 'required|array|min:1',
            'quality_categories.*' => 'in:impact_50m,fraud_risk,system_control,org_structure',
            'title'                => 'required|string|max:255',
            'impact_amount'        => 'nullable|numeric|min:0',
            'root_cause'           => 'required|string',
            'systemic_issue'       => 'nullable|string',
            'recommendation'       => 'required|string',
            'auditor_notes'        => 'nullable|string',
        ], [
            'quality_categories.required' => 'Pilih minimal satu kategori target Finding Quality.',
            'quality_categories.min'      => 'Pilih minimal satu kategori target Finding Quality.',
            'finding_id.unique'           => 'Temuan ini sudah memiliki laporan finding quality.',
        ]);

        $finding = Finding::findOrFail($validated['finding_id']);

        $auditQuery = Audit::whereKey($finding->audit_id);
        if ($this->isAuditorPortal($request)) {
            $auditQuery->forAuditor($request->user()->id);
        }

        if (!$auditQuery->exists()) {
            throw ValidationException::withMessages([
                'finding_id' => 'Temuan ini bukan dari audit yang Anda tangani.',
            ]);
        }

        $qualityFinding = QualityFinding::create([
            'finding_id'       => $validated['finding_id'],
            'audit_id'         => $finding->audit_id,
            'quality_category' => $validated['quality_categories'],
            'title'            => $validated['title'],
            'impact_amount'    => $validated['impact_amount'] ?? null,
            'root_cause'       => $validated['root_cause'],
            'systemic_issue'   => $validated['systemic_issue'] ?? null,
            'recommendation'   => $validated['recommendation'],
            'auditor_notes'    => $validated['auditor_notes'] ?? null,
            'reported_by'      => $request->user()->id,
            'status'           => 'REPORTED',
        ]);

        return redirect()->route($this->routePrefix($request) . 'finding-qualities.show', $qualityFinding)
            ->with('success', 'Laporan finding quality berhasil dibuat.');
    }

    public function show(Request $request, QualityFinding $findingQuality): Response
    {
        abort_unless($this->canAccess($request, $findingQuality), 403);

        $findingQuality->load([
            'finding.category',
            'finding.sop',
            'finding.actionPlan',
            'audit.store',
            'audit.auditor',
            'reporter',
        ]);

        $finding = $findingQuality->finding;
        $audit   = $findingQuality->audit;

        return Inertia::render('Auditor/FindingQualities/Show', [
            'qualityFinding' => [
                'id'                      => $findingQuality->id,
                'quality_category'        => $findingQuality->quality_categories[0] ?? '',
                'quality_categories'      => $findingQuality->quality_categories,
                'quality_categories_info' => $findingQuality->quality_categories_info,
                'title'                   => $findingQuality->title,
                'impact_amount'           => (float) $findingQuality->impact_amount,
                'root_cause'              => $findingQuality->root_cause,
                'systemic_issue'          => $findingQuality->systemic_issue,
                'recommendation'          => $findingQuality->recommendation,
                'auditor_notes'           => $findingQuality->auditor_notes,
                'status'                  => $findingQuality->status,
                'reported_by'             => $findingQuality->reporter?->name ?? '-',
                'created_at'              => $findingQuality->created_at ? $findingQuality->created_at->copy()->timezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB' : '-',
                'categories_info'         => QualityFinding::categories()[$findingQuality->quality_categories[0] ?? ''] ?? [],
                'finding'          => [
                    'id'               => $finding?->id,
                    'category'         => $finding?->category?->name ?? '-',
                    'sop'              => $finding?->sop ? "{$finding->sop->code} - {$finding->sop->title}" : '-',
                    'finding'          => $finding?->finding ?? '-',
                    'severity'         => $finding?->severity ?? 'MINOR',
                    'status'           => $finding?->status ?? 'OPEN',
                    'loss_amount'      => (float) ($finding?->loss_amount ?? 0),
                    'auditor_opinion'  => $finding?->auditor_opinion,
                    'recommendation'   => $finding?->recommendation,
                ],
                'audit'            => [
                    'id'           => $audit?->id,
                    'audit_number' => $audit?->audit_number ?? '-',
                    'store_name'   => $audit?->store?->name ?? ($audit?->location ?: '-'),
                    'store_code'   => $audit?->store?->code ?? '-',
                    'store_area'   => $audit?->store?->area,
                    'audit_date'   => $audit?->audit_date?->format('d M Y') ?? '-',
                    'lead_auditor' => $audit?->auditor?->name ?? '-',
                ],
            ],
            'categories' => QualityFinding::categories(),
            'routePrefix' => $this->routePrefix($request),
        ]);
    }

    public function destroy(Request $request, QualityFinding $findingQuality): RedirectResponse
    {
        abort_unless($this->canAccess($request, $findingQuality), 403);

        $findingQuality->delete();

        return redirect()->route($this->routePrefix($request) . 'finding-qualities.index')
            ->with('success', 'Laporan finding quality berhasil dihapus.');
    }
}
