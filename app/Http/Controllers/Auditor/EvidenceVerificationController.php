<?php

namespace App\Http\Controllers\Auditor;

use App\Http\Controllers\Controller;
use App\Models\Evidence;
use App\Models\Finding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EvidenceVerificationController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $pendingEvidences = Evidence::where('verification_status', 'PENDING')
            ->whereHas('finding.audit', fn ($q) => $q->where('auditor_id', $user->id))
            ->with(['finding.audit.store', 'uploader'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($e) => [
                'id'          => $e->id,
                'finding_id'  => $e->finding_id,
                'finding'     => \Str::limit($e->finding->finding, 60),
                'audit'       => $e->finding->audit->audit_number,
                'store'       => $e->finding->audit->store->name,
                'description' => $e->description,
                'file_url'    => $e->file_url,
                'uploaded_by' => $e->uploader->name,
                'uploaded_at' => $e->created_at->format('d M Y H:i'),
            ]);

        return Inertia::render('Auditor/Verification/Index', [
            'pending_evidences' => $pendingEvidences,
        ]);
    }

    public function approve(Request $request, Evidence $evidence): RedirectResponse
    {
        $this->authorize('verify', $evidence);

        $evidence->update([
            'verification_status' => 'APPROVED',
            'verified_by'         => $request->user()->id,
            'verified_at'         => now(),
            'rejection_reason'    => null,
        ]);

        // A finding moves to VERIFIED if there are NO PENDING evidences and at least one APPROVED evidence
        $finding = $evidence->finding;
        $hasPending = $finding->evidences()->where('verification_status', 'PENDING')->exists();
        $hasApproved = $finding->evidences()->where('verification_status', 'APPROVED')->exists();

        if (!$hasPending && $hasApproved) {
            $finding->update(['status' => Finding::STATUS_VERIFIED]);
        }

        \App\Services\WhatsAppService::notifyEvidenceVerified($evidence);

        return back()->with('success', 'Evidence disetujui & notifikasi WhatsApp terkirim ke Toko.');
    }

    public function reject(Request $request, Evidence $evidence): RedirectResponse
    {
        $this->authorize('verify', $evidence);

        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $evidence->update([
            'verification_status' => 'REJECTED',
            'verified_by'         => $request->user()->id,
            'verified_at'         => now(),
            'rejection_reason'    => $request->rejection_reason,
        ]);

        $finding = $evidence->finding;
        $hasPending = $finding->evidences()->where('verification_status', 'PENDING')->exists();

        // If there are no other pending evidences, move finding status back to IN_PROGRESS
        if (!$hasPending) {
            $finding->update(['status' => Finding::STATUS_IN_PROGRESS]);
        } else {
            $finding->update(['status' => Finding::STATUS_WAITING_VERIFICATION]);
        }

        \App\Services\WhatsAppService::notifyEvidenceVerified($evidence);

        return back()->with('success', 'Evidence ditolak & notifikasi WhatsApp terkirim ke Toko.');
    }
}
