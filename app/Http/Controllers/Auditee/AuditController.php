<?php

namespace App\Http\Controllers\Auditee;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $storeIds = $user->stores()->pluck('stores.id');

        $audits = Audit::whereIn('store_id', $storeIds)
            ->with(['store', 'auditor'])
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
                'notes'          => $audit->notes,
            ]);

        return Inertia::render('Auditee/Audits/Index', [
            'audits' => $audits,
        ]);
    }

    public function show(Request $request, Audit $audit): Response
    {
        $this->authorize('view', $audit);

        $audit->load([
            'store',
            'auditor',
            'findings.category',
            'findings.sop',
            'findings.actionPlan',
            'findings.evidences.uploader',
        ]);

        return Inertia::render('Auditee/Audits/Show', [
            'audit' => [
                'id'           => $audit->id,
                'audit_number' => $audit->audit_number,
                'store'        => $audit->store->only(['name', 'code', 'area', 'regional']),
                'auditor'      => $audit->auditor->only(['id', 'name', 'email']),
                'audit_date'   => $audit->audit_date->format('d M Y'),
                'status'       => $audit->status,
                'notes'        => $audit->notes,
                'findings'     => $audit->findings->map(fn ($f) => [
                    'id'            => $f->id,
                    'category'      => $f->category->name,
                    'sop'           => $f->sop?->only(['code', 'title']),
                    'finding'       => $f->finding,
                    'loss_amount'   => $f->loss_amount,
                    'severity'      => $f->severity,
                    'status'        => $f->status,
                    'opinion'       => $f->auditor_opinion,
                    'recommendation' => $f->recommendation,
                    'action_plan'   => $f->actionPlan,
                    'evidences_count' => $f->evidences->count(),
                ]),
            ],
        ]);
    }
}
