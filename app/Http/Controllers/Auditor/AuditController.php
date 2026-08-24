<?php

namespace App\Http\Controllers\Auditor;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditController extends Controller
{
    public function index(Request $request): Response
    {
        $audits = Audit::forAuditor($request->user()->id)
            ->with(['store'])
            ->withCount('findings')
            ->orderBy('audit_date', 'desc')
            ->get()
            ->map(fn ($audit) => [
                'id'             => $audit->id,
                'audit_number'   => $audit->audit_number,
                'store'          => $audit->store->name,
                'store_area'     => $audit->store->area,
                'audit_date'     => $audit->audit_date->format('d M Y'),
                'status'         => $audit->status,
                'findings_count' => $audit->findings_count,
                'notes'          => $audit->notes,
            ]);

        return Inertia::render('Auditor/Audits/Index', [
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

        return Inertia::render('Auditor/Audits/Show', [
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
                    'evidences'     => $f->evidences->map(fn ($e) => [
                        'id'                  => $e->id,
                        'description'         => $e->description,
                        'verification_status' => $e->verification_status,
                        'uploaded_by'         => $e->uploader->name,
                        'uploaded_at'         => $e->created_at->format('d M Y H:i'),
                        'file_url'            => $e->file_url,
                    ]),
                ]),
            ],
        ]);
    }
}
