<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
        $findings = Finding::with(['audit.store', 'audit.auditor', 'category', 'actionPlan', 'evidences'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($f) => [
                'id'           => $f->id,
                'audit_id'     => $f->audit_id,
                'audit_number' => $f->audit?->audit_number ?? '—',
                'store'        => $f->audit?->store?->name ?? '—',
                'store_id'     => $f->audit?->store_id,
                'period'       => $f->audit?->audit_date?->format('Y-m'),
                'auditor'      => $f->audit?->auditor?->name ?? '—',
                'category'     => $f->category?->name ?? '—',
                'finding'      => $f->finding,
                'loss_amount'  => $f->loss_amount,
                'surplus_amount' => $f->surplus_amount,
                'severity'     => $f->severity,
                'status'       => $f->status,
                'action_plan'  => $f->actionPlan?->action_plan,
                'pic'          => $f->actionPlan?->pic,
                'deadline'     => $f->actionPlan?->deadline?->format('d M Y'),
                'created_at'   => $f->created_at->format('d M Y'),
            ]);

        return Inertia::render('Admin/Findings/Index', [
            'findings'   => $findings,
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

    public function show(Request $request, Finding $finding): Response
    {
        $finding->load([
            'audit.store',
            'audit.documents',
            'audit.auditor',
            'category',
            'sop',
            'actionPlan',
            'followUp',
            'evidences.uploader',
            'evidences.verifier',
        ]);

        $docsCount = $finding->audit?->documents?->count() ?? 0;
        $hasDocs = $docsCount > 0 || $finding->evidences->isNotEmpty();
        $hasActionPlan = !empty($finding->actionPlan?->action_plan);

        return Inertia::render('Admin/Findings/Show', [
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
        if (! $finding->isCloseable()) {
            return back()->with('error', 'Temuan hanya bisa ditutup setelah bukti diverifikasi.');
        }

        $this->authorize('close', $finding);

        // CLOSED + action plan COMPLETED, audit ikut COMPLETED bila semua temuan sudah ditutup
        $finding->markClosed();

        WhatsAppService::notifyFindingClosed($finding);

        return redirect()->route('admin.findings.show', $finding)
            ->with('success', 'Temuan ditutup.');
    }

    public function updateRecommendation(Request $request, Finding $finding): RedirectResponse
    {
        if ($finding->isClosed()) {
            return back()->with('error', 'Temuan sudah ditutup.');
        }

        $validated = $request->validate([
            'recommendation' => 'required|string|max:2000',
        ]);

        $finding->update([
            'recommendation' => $validated['recommendation'],
        ]);

        return back()->with('success', 'Rekomendasi disimpan.');
    }

    public function reviewSeverity(Request $request, Finding $finding): RedirectResponse
    {
        if ($finding->isClosed()) {
            return back()->with('error', 'Temuan sudah ditutup.');
        }

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

        \App\Services\WhatsAppService::notifySeverityReviewed($finding, $oldSeverity);

        return back()->with('success', 'Severity disimpan.');
    }

    public function destroy(Finding $finding): RedirectResponse
    {
        $finding->delete();

        return redirect()->route('admin.findings.index')->with('success', 'Temuan dihapus.');
    }
}
