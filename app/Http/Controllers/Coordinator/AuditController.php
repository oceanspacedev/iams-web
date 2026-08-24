<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\Audit;
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
            ->map(fn ($a) => [
                'id'             => $a->id,
                'audit_number'   => $a->audit_number,
                'store'          => $a->store->name,
                'store_code'     => $a->store->code,
                'auditor'        => $a->auditor->name,
                'audit_date'     => $a->audit_date->format('d M Y'),
                'status'         => $a->status,
                'findings_count' => $a->findings_count,
            ]);

        return Inertia::render('Coordinator/Audits/Index', [
            'audits' => $audits,
        ]);
    }

    public function show(Audit $audit): Response
    {
        $audit->load([
            'store',
            'auditor',
            'findings.category',
            'findings.sop',
            'findings.severityReviewer',
            'findings.actionPlan',
        ]);

        return Inertia::render('Coordinator/Audits/Show', [
            'audit' => [
                'id'           => $audit->id,
                'audit_number' => $audit->audit_number,
                'store'        => $audit->store->only(['name', 'code', 'area', 'regional']),
                'auditor'      => $audit->auditor->only(['name', 'email', 'phone']),
                'audit_date'   => $audit->audit_date->format('d M Y'),
                'status'       => $audit->status,
                'notes'        => $audit->notes,
                'findings'     => $audit->findings->map(fn ($f) => [
                    'id'                   => $f->id,
                    'category'             => $f->category->name,
                    'sop'                  => $f->sop?->only(['code', 'title']),
                    'finding'              => $f->finding,
                    'loss_amount'          => $f->loss_amount,
                    'severity'             => $f->severity,
                    'severity_status'      => $f->severity_status,
                    'is_severity_locked'   => $f->is_severity_locked,
                    'severity_reviewed_by' => $f->severityReviewer?->name,
                    'status'               => $f->status,
                    'recommendation'       => $f->recommendation,
                    'action_plan'          => $f->actionPlan,
                ]),
            ],
        ]);
    }
}
