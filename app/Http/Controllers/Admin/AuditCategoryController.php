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
    public function index(Request $request): Response
    {
        $status = $request->query('status', 'all');

        $query = AuditCategory::withTrashed()->withCount('findings')->orderBy('name');

        if ($status === 'active') {
            $query->whereNull('deleted_at')->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->whereNull('deleted_at')->where('is_active', false);
        } elseif ($status === 'trashed') {
            $query->onlyTrashed();
        }

        $categories = $query->get()->map(function ($cat) {
            return [
                'id'             => $cat->id,
                'name'           => $cat->name,
                'description'    => $cat->description,
                'is_active'      => (bool) $cat->is_active,
                'findings_count' => $cat->findings_count,
                'is_deleted'     => (bool) $cat->trashed(),
                'deleted_at'     => $cat->deleted_at?->format('d M Y H:i'),
            ];
        });

        $stats = [
            'all'      => AuditCategory::withTrashed()->count(),
            'active'   => AuditCategory::where('is_active', true)->count(),
            'inactive' => AuditCategory::where('is_active', false)->count(),
            'trashed'  => AuditCategory::onlyTrashed()->count(),
        ];

        return Inertia::render('Admin/AuditCategories/Index', [
            'categories'    => $categories,
            'currentStatus' => $status,
            'stats'         => $stats,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:audit_categories,name',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        AuditCategory::create([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active'   => $validated['is_active'] ?? true,
        ]);

        return back()->with('success', 'Kategori audit berhasil ditambahkan.');
    }

    public function update(Request $request, AuditCategory $auditCategory): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:audit_categories,name,' . $auditCategory->id,
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        $auditCategory->update([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active'   => $validated['is_active'] ?? true,
        ]);

        return back()->with('success', 'Kategori audit berhasil diperbarui.');
    }

    public function toggleActive(AuditCategory $auditCategory): RedirectResponse
    {
        $auditCategory->update([
            'is_active' => !$auditCategory->is_active,
        ]);

        $statusText = $auditCategory->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Status kategori '{$auditCategory->name}' berhasil {$statusText}.");
    }

    public function destroy(AuditCategory $auditCategory): RedirectResponse
    {
        $name = $auditCategory->name;
        $auditCategory->delete(); // Soft delete

        return back()->with('success', "Kategori '{$name}' berhasil dihapus dan disimpan ke riwayat arsip.");
    }

    public function restore($id): RedirectResponse
    {
        $category = AuditCategory::onlyTrashed()->findOrFail($id);
        $category->restore();

        return back()->with('success', "Kategori '{$category->name}' berhasil dipulihkan dari arsip riwayat.");
    }
}
