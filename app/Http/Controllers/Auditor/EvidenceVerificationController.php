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
            ->whereHas('finding', fn ($q) => $q->where('status', '!=', Finding::STATUS_CLOSED))
            ->whereHas('finding.audit', fn ($q) => $q->forAuditor($user->id))
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

        if ($error = $this->verificationError($evidence)) {
            return back()->with('error', $error);
        }

        $evidence->update([
            'verification_status' => 'APPROVED',
            'verified_by'         => $request->user()->id,
            'verified_at'         => now(),
            'rejection_reason'    => null,
        ]);

        $finding = $evidence->finding;
        $finding->update(['status' => Finding::STATUS_VERIFIED]);
        $finding->actionPlan?->update(['status' => 'COMPLETED']);

        \App\Services\WhatsAppService::notifyEvidenceVerified($evidence);

        return back()->with('success', 'Bukti disetujui.');
    }

    public function reject(Request $request, Evidence $evidence): RedirectResponse
    {
        $this->authorize('verify', $evidence);

        if ($error = $this->verificationError($evidence)) {
            return back()->with('error', $error);
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $evidence->update([
            'verification_status' => 'REJECTED',
            'verified_by'         => $request->user()->id,
            'verified_at'         => now(),
            'rejection_reason'    => $request->rejection_reason,
        ]);

        // Kembali ke IN_PROGRESS; tetap WAITING_VERIFICATION bila masih ada bukti lain yang menunggu.
        $finding = $evidence->finding;
        $hasPending = $finding->evidences()->where('verification_status', 'PENDING')->exists();
        $finding->update(['status' => $hasPending ? Finding::STATUS_WAITING_VERIFICATION : Finding::STATUS_IN_PROGRESS]);
        $finding->actionPlan?->update(['status' => 'IN_PROGRESS']);

        \App\Services\WhatsAppService::notifyEvidenceVerified($evidence);

        return back()->with('success', 'Bukti ditolak.');
    }

    /**
     * Hanya bukti PENDING pada temuan yang belum CLOSED yang bisa diverifikasi.
     */
    private function verificationError(Evidence $evidence): ?string
    {
        if (! $evidence->isPending()) {
            return 'Bukti ini sudah diverifikasi.';
        }

        if ($evidence->finding?->isClosed()) {
            return 'Temuan sudah ditutup.';
        }

        return null;
    }
}
