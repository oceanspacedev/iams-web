<?php

namespace App\Http\Controllers\Auditee;

use App\Http\Controllers\Controller;
use App\Models\Finding;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FindingController extends Controller
{
    public function show(Request $request, Finding $finding): Response
    {
        $this->authorize('view', $finding);

        $finding->load([
            'audit.store',
            'category',
            'sop',
            'actionPlan',
            'followUp',
            'evidences.uploader',
            'evidences.verifier',
        ]);

        return Inertia::render('Auditee/Findings/Show', [
            'finding' => [
                'id'              => $finding->id,
                'audit'           => $finding->audit->only(['id', 'audit_number', 'status', 'audit_date']),
                'store'           => $finding->audit->store->only(['name', 'code']),
                'category'        => $finding->category->name,
                'sop'             => $finding->sop?->only(['code', 'title']),
                'finding'         => $finding->finding,
                'loss_amount'     => $finding->loss_amount,
                'severity'        => $finding->severity,
                'status'          => $finding->status,
                'auditor_opinion' => $finding->auditor_opinion,
                'recommendation'  => $finding->recommendation,
                'action_plan'     => $finding->actionPlan,
                'follow_up'       => $finding->followUp,
                'evidences'       => $finding->evidences->map(fn ($e) => [
                    'id'                  => $e->id,
                    'description'         => $e->description,
                    'verification_status' => $e->verification_status,
                    'rejection_reason'    => $e->rejection_reason,
                    'uploaded_by'         => $e->uploader->name,
                    'verified_by'         => $e->verifier?->name,
                    'uploaded_at'         => $e->created_at->format('d M Y H:i'),
                    'file_url'            => $e->file_url,
                    'can_delete'          => $e->uploaded_by === $request->user()->id && in_array($e->verification_status, ['PENDING', 'REJECTED']),
                ]),
                'can_upload_evidence' => $finding->canUploadEvidence(),
                'can_edit_action_plan' => in_array($finding->status, [Finding::STATUS_OPEN, Finding::STATUS_IN_PROGRESS]),
            ],
        ]);
    }
}
