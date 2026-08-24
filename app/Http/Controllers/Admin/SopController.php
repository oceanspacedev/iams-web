<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SopController extends Controller
{
    public function index(): Response
    {
        $sops = Sop::withCount('findings')
            ->orderBy('code')
            ->get();

        return Inertia::render('Admin/Sops/Index', [
            'sops' => $sops,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code'        => 'required|string|max:50|unique:sops,code',
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'document'    => 'nullable|file|mimes:pdf,docx,xlsx|max:10240',
            'is_active'   => 'boolean',
        ]);

        $docPath = null;
        if ($request->hasFile('document')) {
            $docPath = $request->file('document')->store('sop-documents', 'public');
        }

        Sop::create([
            'code'        => $validated['code'],
            'title'       => $validated['title'],
            'description' => $validated['description'] ?? null,
            'document'    => $docPath,
            'is_active'   => $validated['is_active'] ?? true,
        ]);

        return back()->with('success', 'SOP / SE berhasil ditambahkan.');
    }

    public function update(Request $request, Sop $sop): RedirectResponse
    {
        $validated = $request->validate([
            'code'        => 'required|string|max:50|unique:sops,code,' . $sop->id,
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'document'    => 'nullable|file|mimes:pdf,docx,xlsx|max:10240',
            'is_active'   => 'boolean',
        ]);

        $docPath = $sop->document;
        if ($request->hasFile('document')) {
            if ($docPath && Storage::disk('public')->exists($docPath)) {
                Storage::disk('public')->delete($docPath);
            }
            $docPath = $request->file('document')->store('sop-documents', 'public');
        }

        $sop->update([
            'code'        => $validated['code'],
            'title'       => $validated['title'],
            'description' => $validated['description'] ?? null,
            'document'    => $docPath,
            'is_active'   => $validated['is_active'] ?? true,
        ]);

        return back()->with('success', 'SOP / SE berhasil diperbarui.');
    }

    public function destroy(Sop $sop): RedirectResponse
    {
        if ($sop->findings()->exists()) {
            return back()->with('error', 'SOP/SE tidak dapat dihapus karena ditautkan pada temuan audit.');
        }

        if ($sop->document && Storage::disk('public')->exists($sop->document)) {
            Storage::disk('public')->delete($sop->document);
        }

        $sop->delete();

        return back()->with('success', 'SOP / SE berhasil dihapus.');
    }
}
