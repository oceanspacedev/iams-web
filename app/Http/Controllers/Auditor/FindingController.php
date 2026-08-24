<?php

namespace App\Http\Controllers\Auditor;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Models\AuditCategory;
use App\Models\Finding;
use App\Models\FindingFollowUp;
use App\Models\Sop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FindingController extends Controller
{
    public function create(Request $request, Audit $audit): Response
    {
        $this->authorize('view', $audit);

        return Inertia::render('Auditor/Findings/Create', [
            'audit'      => $audit->load('store')->only(['id', 'audit_number', 'store']),
            'categories' => AuditCategory::active()->orderBy('name')->get(['id', 'name']),
            'sops'       => Sop::active()->orderBy('code')->get(['id', 'code', 'title']),
        ]);
    }

    public function store(Request $request, Audit $audit): RedirectResponse
    {
        $this->authorize('view', $audit);
        $this->authorize('create', Finding::class);

        $validated = $request->validate([
            'category_id'     => 'required|exists:audit_categories,id',
            'sop_id'          => 'nullable|exists:sops,id',
            'finding'         => 'required|string',
            'loss_amount'     => 'nullable|numeric|min:0',
            'auditor_opinion' => 'nullable|string',
            'recommendation'  => 'required|string',
            'severity'        => 'required|in:CRITICAL,MAJOR,MINOR,OBSERVATION',
        ]);

        $finding = $audit->findings()->create([
            ...$validated,
            'status' => Finding::STATUS_OPEN,
        ]);

        // Create empty ActionPlan record
        $finding->actionPlan()->create([
            'status' => 'OPEN',
        ]);

        // Create empty FindingFollowUp record
        $finding->followUp()->create([]);

        \App\Services\WhatsAppService::notifyFindingCreated($finding);

        return redirect()->route('auditor.audits.show', $audit)
            ->with('success', 'Finding berhasil dibuat & notifikasi WhatsApp terkirim ke Toko.');
    }

    public function show(Request $request, Finding $finding): Response
    {
        $this->authorize('view', $finding);

        $finding->load([
            'audit.store',
            'category',
            'sop',
            'severityReviewer',
            'actionPlan',
            'followUp',
            'evidences.uploader',
            'evidences.verifier',
        ]);

        return Inertia::render('Auditor/Findings/Show', [
            'finding' => [
                'id'                   => $finding->id,
                'audit'                => $finding->audit->only(['id', 'audit_number', 'status']),
                'store'                => $finding->audit->store->only(['name', 'code']),
                'category'             => $finding->category->name,
                'sop'                  => $finding->sop?->only(['code', 'title']),
                'finding'              => $finding->finding,
                'loss_amount'          => $finding->loss_amount,
                'severity'             => $finding->severity,
                'severity_status'      => $finding->severity_status,
                'severity_reviewed_by' => $finding->severityReviewer?->name,
                'severity_reviewed_at' => $finding->severity_reviewed_at?->format('d M Y H:i'),
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
                    'uploaded_at'         => $e->created_at->format('d M Y H:i'),
                    'file_url'            => $e->file_url,
                    'can_verify'          => $e->isPending(),
                ]),
                'can_close' => $finding->isCloseable(),
            ],
        ]);
    }

    public function edit(Request $request, Finding $finding): Response
    {
        $this->authorize('update', $finding);

        return Inertia::render('Auditor/Findings/Edit', [
            'finding'    => $finding->load(['audit.store', 'category', 'sop']),
            'categories' => AuditCategory::active()->get(['id', 'name']),
            'sops'       => Sop::active()->get(['id', 'code', 'title']),
        ]);
    }

    public function update(Request $request, Finding $finding): RedirectResponse
    {
        $this->authorize('update', $finding);

        $rules = [
            'category_id'     => 'required|exists:audit_categories,id',
            'sop_id'          => 'nullable|exists:sops,id',
            'finding'         => 'required|string',
            'loss_amount'     => 'nullable|numeric|min:0',
            'auditor_opinion' => 'nullable|string',
            'recommendation'  => 'required|string',
        ];

        // Only validate severity if it has not been locked by Coordinator
        if (!$finding->is_severity_locked) {
            $rules['severity'] = 'required|in:CRITICAL,MAJOR,MINOR,OBSERVATION';
        }

        $validated = $request->validate($rules);

        // Prevent modifying severity if locked
        if ($finding->is_severity_locked) {
            unset($validated['severity']);
        }

        $finding->update($validated);

        return redirect()->route('auditor.findings.show', $finding)
            ->with('success', 'Finding berhasil diperbarui.');
    }

    public function close(Request $request, Finding $finding): RedirectResponse
    {
        $this->authorize('close', $finding);

        $finding->update(['status' => Finding::STATUS_CLOSED]);

        // Update action plan status
        $finding->actionPlan?->update(['status' => 'COMPLETED']);

        \App\Services\WhatsAppService::notifyFindingClosed($finding);

        return redirect()->route('auditor.findings.show', $finding)
            ->with('success', 'Finding berhasil ditutup & notifikasi WhatsApp terkirim ke Toko.');
    }
}
