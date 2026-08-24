<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Models\AuditCategory;
use App\Models\Finding;
use App\Models\Sop;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditController extends Controller
{
    public function index(Request $request): Response
    {
        $audits = Audit::with(['store', 'auditor'])
            ->withCount('findings')
            ->orderBy('audit_date', 'desc')
            ->get()
            ->map(fn ($audit) => [
                'id'             => $audit->id,
                'audit_number'   => $audit->audit_number,
                'store'          => $audit->store->name,
                'store_code'     => $audit->store->code,
                'auditor'        => $audit->auditor->name,
                'audit_date'     => $audit->audit_date->format('d M Y'),
                'status'         => $audit->status,
                'findings_count' => $audit->findings_count,
            ]);

        return Inertia::render('Admin/Audits/Index', [
            'audits' => $audits,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Audits/Create', [
            'stores'   => Store::active()->orderBy('name')->get(['id', 'name', 'code']),
            'auditors' => User::whereHas('roles', fn ($q) => $q->where('name', 'auditor'))
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'suggested_number' => Audit::generateNumber(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'audit_number' => 'required|string|max:50|unique:audits,audit_number',
            'store_id'     => 'required|exists:stores,id',
            'auditor_id'   => 'required|exists:users,id',
            'audit_date'   => 'required|date',
            'status'       => 'required|in:PLANNED,IN_PROGRESS,COMPLETED,CLOSED',
            'notes'        => 'nullable|string',
        ]);

        $audit = Audit::create($validated);

        \App\Services\WhatsAppService::notifyAuditScheduled($audit);

        return redirect()->route('admin.audits.show', $audit)->with('success', 'Audit berhasil dibuat & notifikasi WhatsApp terkirim.');
    }

    public function show(Audit $audit): Response
    {
        $audit->load([
            'store',
            'auditor',
            'findings.category',
            'findings.sop',
            'findings.actionPlan',
            'findings.evidences.uploader',
        ]);

        return Inertia::render('Admin/Audits/Show', [
            'audit' => [
                'id'           => $audit->id,
                'audit_number' => $audit->audit_number,
                'store'        => $audit->store->only(['name', 'code', 'area', 'regional']),
                'auditor'      => $audit->auditor->only(['name', 'email']),
                'audit_date'   => $audit->audit_date->format('d M Y'),
                'status'       => $audit->status,
                'notes'        => $audit->notes,
                'findings'     => $audit->findings->map(fn ($f) => [
                    'id'             => $f->id,
                    'category'       => $f->category->name,
                    'sop'            => $f->sop?->only(['code', 'title']),
                    'finding'        => $f->finding,
                    'loss_amount'    => $f->loss_amount,
                    'severity'       => $f->severity,
                    'status'         => $f->status,
                    'recommendation' => $f->recommendation,
                    'action_plan'    => $f->actionPlan,
                ]),
            ],
            'categories' => AuditCategory::orderBy('name')->get(['id', 'name']),
            'sops'       => Sop::active()->orderBy('code')->get(['id', 'code', 'title']),
        ]);
    }

    public function edit(Audit $audit): Response
    {
        return Inertia::render('Admin/Audits/Edit', [
            'audit' => [
                'id'           => $audit->id,
                'audit_number' => $audit->audit_number,
                'store_id'     => $audit->store_id,
                'auditor_id'   => $audit->auditor_id,
                'audit_date'   => $audit->audit_date->format('Y-m-d'),
                'status'       => $audit->status,
                'notes'        => $audit->notes,
            ],
            'stores'   => Store::active()->orderBy('name')->get(['id', 'name', 'code']),
            'auditors' => User::role('auditor')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Audit $audit): RedirectResponse
    {
        $validated = $request->validate([
            'audit_number' => 'required|string|max:50|unique:audits,audit_number,' . $audit->id,
            'store_id'     => 'required|exists:stores,id',
            'auditor_id'   => 'required|exists:users,id',
            'audit_date'   => 'required|date',
            'status'       => 'required|in:PLANNED,IN_PROGRESS,COMPLETED,CLOSED',
            'notes'        => 'nullable|string',
        ]);

        $audit->update($validated);

        return redirect()->route('admin.audits.show', $audit)->with('success', 'Audit berhasil diperbarui.');
    }

    public function destroy(Audit $audit): RedirectResponse
    {
        $audit->delete();

        return redirect()->route('admin.audits.index')->with('success', 'Audit berhasil dihapus.');
    }

    public function storeFinding(Request $request, Audit $audit): RedirectResponse
    {
        $validated = $request->validate([
            'category_id'     => 'required|exists:audit_categories,id',
            'sop_id'          => 'nullable|exists:sops,id',
            'finding'         => 'required|string',
            'loss_amount'     => 'nullable|numeric|min:0',
            'auditor_opinion' => 'nullable|string',
            'recommendation'  => 'required|string',
            'severity'        => 'required|in:CRITICAL,MAJOR,MINOR,OBSERVATION',
            'status'          => 'required|in:OPEN,IN_PROGRESS,WAITING_VERIFICATION,VERIFIED,CLOSED',
        ]);

        $finding = $audit->findings()->create($validated);
        $finding->actionPlan()->create(['status' => 'OPEN']);
        $finding->followUp()->create([]);

        \App\Services\WhatsAppService::notifyFindingCreated($finding);

        return back()->with('success', 'Finding berhasil ditambahkan & notifikasi WhatsApp terkirim.');
    }
}
