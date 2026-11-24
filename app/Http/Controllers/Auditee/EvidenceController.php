<?php

namespace App\Http\Controllers\Auditee;

use App\Http\Controllers\Controller;
use App\Models\Evidence;
use App\Models\Finding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EvidenceController extends Controller
{
    public function store(Request $request, Finding $finding): RedirectResponse
    {
        $this->authorize('view', $finding);

        if (! $finding->canUploadEvidence()) {
            return back()->with('error', 'Bukti tidak bisa diunggah untuk temuan dengan status ini.');
        }

        $request->validate([
            'file'        => 'required|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx|max:10240',
            'description' => 'nullable|string|max:500',
        ]);

        $disk = config('filesystems.default');
        $filePath = $request->file('file')->store('evidences', $disk);

        $evidence = $finding->evidences()->create([
            'uploaded_by'         => $request->user()->id,
            'file'                => $filePath,
            'description'         => $request->description,
            'verification_status' => 'PENDING',
        ]);

        // Automatically update finding status to WAITING_VERIFICATION
        $finding->update(['status' => Finding::STATUS_WAITING_VERIFICATION]);

        \App\Services\WhatsAppService::notifyEvidenceUploaded($evidence);

        return back()->with('success', 'Bukti diunggah.');
    }

    public function destroy(Request $request, Evidence $evidence): RedirectResponse
    {
        $finding = $evidence->finding;

        if ($finding?->isClosed()) {
            return back()->with('error', 'Temuan sudah ditutup.');
        }

        $this->authorize('delete', $evidence);

        $disk = config('filesystems.default');
        if (Storage::disk($disk)->exists($evidence->file)) {
            Storage::disk($disk)->delete($evidence->file);
        } elseif (Storage::disk('public')->exists($evidence->file)) {
            Storage::disk('public')->delete($evidence->file);
        }

        $evidence->delete();

        $finding->recalculateStatusFromEvidence();

        return back()->with('success', 'Bukti dihapus.');
    }
}
