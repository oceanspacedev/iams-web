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
        $query = Audit::forAuditor($request->user()->id)
            ->with(['store', 'category'])
            ->withCount('findings')
            ->orderBy('audit_date', 'desc');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->query('category_id'));
        }

        $audits = $query->get()
            ->map(fn ($audit) => [
                'id'             => $audit->id,
                'audit_number'   => $audit->audit_number,
                'title'          => $audit->title,
                'category'       => $audit->category?->name ?? '—',
                'category_id'    => $audit->category_id,
                'store'          => $audit->store->name,
                'store_area'     => $audit->store->area,
                'audit_date'     => $audit->audit_date->format('d M Y'),
                'status'         => $audit->status,
                'findings_count' => $audit->findings_count,
                'notes'          => $audit->notes,
            ]);

        return Inertia::render('Auditor/Audits/Index', [
            'audits'     => $audits,
            'categories' => \App\Models\AuditCategory::active()->orderBy('name')->get(['id', 'name']),
            'filters'    => [
                'category_id' => $request->query('category_id', ''),
            ],
        ]);
    }

    public function show(Request $request, Audit $audit): Response
    {
        $this->authorize('view', $audit);

        $audit->load([
            'category',
            'store',
            'auditor',
            'auditors',
            'documents.uploader',
            'findings.category',
            'findings.sop',
            'findings.actionPlan',
            'findings.evidences.uploader',
        ]);

        return Inertia::render('Auditor/Audits/Show', [
            'audit' => [
                'id'           => $audit->id,
                'audit_number' => $audit->audit_number,
                'title'        => $audit->title,
                'category'     => $audit->category?->name,
                'category_id'  => $audit->category_id,
                'store'        => $audit->store->only(['name', 'code', 'business_entity', 'type', 'area', 'regional']),
                'auditor'      => $audit->auditor->only(['id', 'name', 'email']),
                'auditors'     => $audit->auditors->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email]),
                'audit_date'   => $audit->audit_date->format('d M Y'),
                'status'       => $audit->status,
                'notes'        => $audit->notes,
                'documents'    => $audit->documents->map(fn ($d) => [
                    'id'            => $d->id,
                    'document_type' => $d->document_type,
                    'title'         => $d->title,
                    'file_name'     => $d->file_name,
                    'file_size'     => $d->file_size,
                    'file_url'      => $d->file_url,
                    'notes'         => $d->notes,
                    'uploaded_by'   => $d->uploader->name,
                    'created_at'    => $d->created_at->format('d M Y H:i'),
                ]),
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
