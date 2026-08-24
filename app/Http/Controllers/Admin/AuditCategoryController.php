<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditCategoryController extends Controller
{
    public function index(): Response
    {
        $categories = AuditCategory::withCount('findings')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/AuditCategories/Index', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:audit_categories,name',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        AuditCategory::create($validated);

        return back()->with('success', 'Kategori audit berhasil ditambahkan.');
    }

    public function update(Request $request, AuditCategory $auditCategory): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:audit_categories,name,' . $auditCategory->id,
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        $auditCategory->update($validated);

        return back()->with('success', 'Kategori audit berhasil diperbarui.');
    }

    public function destroy(AuditCategory $auditCategory): RedirectResponse
    {
        if ($auditCategory->findings()->exists()) {
            return back()->with('error', 'Kategori tidak dapat dihapus karena digunakan pada temuan audit.');
        }

        $auditCategory->delete();

        return back()->with('success', 'Kategori audit berhasil dihapus.');
    }
}
