<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Models\AuditCategory;
use App\Models\Finding;
use App\Models\Sop;
use App\Models\Store;
use App\Services\FindingRecapExcelService;
use App\Services\WhatsAppService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FindingController extends Controller
{
    public function index(Request $request): Response
    {
        $findings = Finding::with(['audit.store', 'audit.auditor', 'category', 'severityReviewer'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($f) => [
                'id'                   => $f->id,
                'audit_id'             => $f->audit_id,
                'audit_number'         => $f->audit?->audit_number ?? '—',
                'store'                => $f->audit?->store?->name ?? '—',
                'store_id'             => $f->audit?->store_id,
                'period'               => $f->audit?->audit_date?->format('Y-m'),
                'auditor'              => $f->audit?->auditor?->name ?? '—',
                'category'             => $f->category?->name ?? '—',
                'finding'              => $f->finding,
                'loss_amount'          => $f->loss_amount,
                'surplus_amount'       => $f->surplus_amount,
                'severity'             => $f->severity,
                'severity_status'      => $f->severity_status,
                'is_severity_locked'   => $f->is_severity_locked,
                'severity_reviewed_by' => $f->severityReviewer?->name,
                'status'               => $f->status,
                'created_at'           => $f->created_at->format('d M Y'),
            ]);

        $audits = Audit::with(['store:id,code,name', 'category:id,name'])
            ->orderBy('audit_date', 'desc')
            ->orderBy('id', 'desc')
            ->get(['id', 'audit_number', 'store_id', 'category_id', 'audit_date', 'status'])
            ->map(fn ($a) => [
                'id'           => $a->id,
                'audit_number' => $a->audit_number,
                'store'        => $a->store?->name ?? '—',
                'store_code'   => $a->store?->code ?? '',
                'category'     => $a->category?->name ?? '—',
                'audit_date'   => $a->audit_date?->format('d M Y') ?? '—',
            ]);

        return Inertia::render('Coordinator/Findings/Index', [
            'findings'   => $findings,
            'audits'     => $audits,
            'categories' => AuditCategory::active()->get(['id', 'name']),
            'stores'     => Store::orderBy('name')->get(['id', 'code', 'name']),
            'periods'    => FindingRecapExcelService::availablePeriods(),
        ]);
    }

    /**
     * Excel rekap temuan (format rekap operasional), filter toko dan bulan opsional.
     */
    public function export(Request $request, FindingRecapExcelService $excel): BinaryFileResponse
    {
        $validated = $request->validate([
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'period'   => ['nullable', 'date_format:Y-m'],
        ]);

        return $excel->findings(
            isset($validated['store_id']) ? (int) $validated['store_id'] : null,
            $validated['period'] ?? null,
        );
    }

    public function create(Request $request, Audit $audit): Response
    {
        $this->authorize('view', $audit);

        return Inertia::render('Auditor/Findings/Create', [
            'audit'       => $audit->load(['store', 'category'])->only(['id', 'audit_number', 'store', 'category', 'category_id']),
            'categories'  => AuditCategory::active()->orderBy('name')->get(['id', 'name']),
            'sops'        => Sop::active()->orderBy('code')->get(['id', 'code', 'title']),
            'routePrefix' => 'coordinator.',
        ]);
    }

    public function store(Request $request, Audit $audit): RedirectResponse
    {
        $this->authorize('view', $audit);

        $validated = $request->validate([
            'category_id'     => 'required|exists:audit_categories,id',
            'sop_id'          => 'nullable|exists:sops,id',
            'finding'         => 'required|string',
            'loss_amount'     => 'nullable|numeric|min:0',
            'surplus_amount'  => 'nullable|numeric|min:0',
            'auditor_opinion' => 'nullable|string',
            'recommendation'  => 'required|string',
            'severity'        => 'required|in:MINOR,MEDIUM,MAJOR,CRITICAL,OBSERVATION,CONTINUOUS',
        ]);

        $finding = $audit->findings()->create([
            ...$validated,
            'status' => Finding::STATUS_OPEN,
        ]);

        $finding->actionPlan()->create([
            'status' => 'OPEN',
        ]);

        $finding->followUp()->create([]);

        WhatsAppService::notifyFindingCreated($finding);

        return redirect()->route('coordinator.audits.show', $audit)
            ->with('success', 'Data saved! Temuan (Finding) baru berhasil disimpan.');
    }

    public function updateActionPlan(Request $request, Finding $finding): RedirectResponse
    {
        $validated = $request->validate([
            'action_plan' => 'required|string',
            'pic'         => 'nullable|string|max:100',
            'deadline'    => 'nullable|date',
            'notes'       => 'nullable|string',
        ]);

        $finding->actionPlan()->updateOrCreate(
            ['finding_id' => $finding->id],
            [
                'action_plan' => $validated['action_plan'],
                'pic'         => $validated['pic'] ?? null,
                'deadline'    => $validated['deadline'] ?? null,
                'response'    => $validated['notes'] ?? null,
                'status'      => 'IN_PROGRESS',
            ]
        );

        if ($finding->status === Finding::STATUS_OPEN) {
            $finding->update(['status' => Finding::STATUS_IN_PROGRESS]);
        }

        return redirect()->route('coordinator.findings.show', $finding)
            ->with('success', 'Data saved! Komitmen tindak lanjut toko berhasil disimpan.');
    }

    public function destroy(Request $request, Finding $finding): RedirectResponse
    {
        $audit = $finding->audit;
        $finding->delete();

        if ($audit) {
            return redirect()->route('coordinator.audits.show', $audit)
                ->with('success', 'Temuan (Finding) berhasil dihapus.');
        }

        return redirect()->route('coordinator.findings.index')
            ->with('success', 'Temuan (Finding) berhasil dihapus.');
    }

    public function show(Request $request, Finding $finding): Response
    {
        $finding->load([
            'audit.store',
            'audit.documents',
            'audit.auditor',
            'category',
            'sop',
            'severityReviewer',
            'actionPlan',
            'followUp',
            'evidences.uploader',
            'evidences.verifier',
        ]);

        $docsCount = $finding->audit?->documents?->count() ?? 0;
        $hasDocs = $docsCount > 0 || $finding->evidences->isNotEmpty();
        $hasActionPlan = !empty($finding->actionPlan?->action_plan);

        return Inertia::render('Coordinator/Findings/Show', [
            'finding' => [
                'id'                   => $finding->id,
                'audit'                => array_merge(
                    $finding->audit?->only(['id', 'audit_number', 'status', 'audit_date']) ?? [],
                    ['documents_count' => $docsCount, 'has_documents' => $docsCount > 0]
                ),
                'has_documents'        => $hasDocs,
                'documents_count'      => $docsCount,
                'has_action_plan'      => $hasActionPlan,
                'store'                => $finding->audit?->store?->only(['name', 'code', 'area']) ?? [],
                'auditor'              => $finding->audit?->auditor?->only(['name', 'email', 'phone']) ?? [],
                'category'             => $finding->category?->name ?? '—',
                'sop'                  => $finding->sop?->only(['code', 'title']),
                'finding'              => $finding->finding,
                'loss_amount'          => $finding->loss_amount,
                'surplus_amount'       => $finding->surplus_amount,
                'severity'             => $finding->severity,
                'severity_status'      => $finding->severity_status,
                'severity_reviewed_by' => $finding->severityReviewer?->name,
                'severity_reviewed_at' => $finding->severity_reviewed_at?->copy()->timezone('Asia/Jakarta')->format('d M Y H:i') . ($finding->severity_reviewed_at ? ' WIB' : ''),
                'severity_notes'       => $finding->severity_notes,
                'is_severity_locked'   => $finding->is_severity_locked,
                'status'               => $finding->status,
                'auditor_opinion'      => $finding->auditor_opinion,
                'recommendation'       => $finding->recommendation,
                'action_plan'          => $finding->actionPlan,
                'follow_up'            => $finding->followUp,
                'evidences'            => $finding->evidences->map(fn ($e) => [
                    'id'                  => $e->id,
                    'description'         => $e->description,
                    'verification_status' => $e->verification_status,
                    'rejection_reason'    => $e->rejection_reason,
                    'uploaded_by'         => $e->uploader->name,
                    'verified_by'         => $e->verifier?->name,
                    'uploaded_at'         => $e->created_at->copy()->timezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB',
                    'file_url'            => $e->file_url,
                ]),
                'can_close' => $finding->isCloseable() && (bool) $request->user()?->can('close', $finding),
            ],
        ]);
    }

    public function close(Request $request, Finding $finding): RedirectResponse
    {
        $this->authorize('close', $finding);

        $finding->update(['status' => Finding::STATUS_CLOSED]);

        // Update action plan status
        $finding->actionPlan?->update(['status' => 'COMPLETED']);

        WhatsAppService::notifyFindingClosed($finding);

        return redirect()->route('coordinator.findings.show', $finding)
            ->with('success', 'Data tersimpan! Temuan audit resmi ditutup (CLOSED).');
    }

    public function updateRecommendation(Request $request, Finding $finding): RedirectResponse
    {
        $validated = $request->validate([
            'recommendation' => 'required|string|max:2000',
        ]);

        $finding->update([
            'recommendation' => $validated['recommendation'],
        ]);

        return back()->with('success', 'Rekomendasi perbaikan temuan audit berhasil diperbarui.');
    }

    public function reviewSeverity(Request $request, Finding $finding): RedirectResponse
    {
        $validated = $request->validate([
            'severity'       => 'required|in:MINOR,MEDIUM,MAJOR,CRITICAL,OBSERVATION,CONTINUOUS',
            'severity_notes' => 'nullable|string|max:500',
            'recommendation' => 'nullable|string|max:2000',
        ]);

        $oldSeverity = $finding->severity;
        $isAdjusted  = $oldSeverity !== $validated['severity'];

        $updateData = [
            'severity'             => $validated['severity'],
            'severity_status'      => $isAdjusted ? 'ADJUSTED' : 'APPROVED',
            'severity_reviewed_by' => $request->user()->id,
            'severity_reviewed_at' => now(),
            'severity_notes'       => $validated['severity_notes'] ?? null,
            'is_severity_locked'   => true,
        ];

        if (array_key_exists('recommendation', $validated) && $validated['recommendation'] !== null) {
            $updateData['recommendation'] = $validated['recommendation'];
        }

        $finding->update($updateData);

        WhatsAppService::notifySeverityReviewed($finding, $oldSeverity);

        return back()->with('success', 'Severity & Rekomendasi berhasil disimpan. Notifikasi WhatsApp telah dikirim ke Auditor.');
    }
}
