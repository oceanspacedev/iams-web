<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StoreController extends Controller
{
    public function index(): Response
    {
        $stores = Store::withCount(['audits', 'auditees'])
            ->with('auditees')
            ->orderBy('code')
            ->get()
            ->map(fn ($s) => [
                'id'             => $s->id,
                'code'           => $s->code,
                'name'           => $s->name,
                'area'           => $s->area,
                'regional'       => $s->regional,
                'status'         => $s->status,
                'audits_count'   => $s->audits_count,
                'auditees_count' => $s->auditees_count,
                'auditees'       => $s->auditees->pluck('name')->join(', ') ?: '—',
            ]);

        return Inertia::render('Admin/Stores/Index', [
            'stores' => $stores,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Stores/Create', [
            'auditees' => User::whereHas('roles', fn ($q) => $q->where('name', 'auditee'))
                ->where('is_active', true)
                ->get(['id', 'name', 'email']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code'       => 'required|string|max:20|unique:stores,code',
            'name'       => 'required|string|max:255',
            'area'       => 'nullable|string|max:100',
            'regional'   => 'nullable|string|max:100',
            'status'     => 'required|in:active,inactive',
            'auditees'   => 'nullable|array',
            'auditees.*' => 'exists:users,id',
        ]);

        $store = Store::create([
            'code'     => $validated['code'],
            'name'     => $validated['name'],
            'area'     => $validated['area'] ?? null,
            'regional' => $validated['regional'] ?? null,
            'status'   => $validated['status'],
        ]);

        if (!empty($validated['auditees'])) {
            $store->auditees()->sync($validated['auditees']);
        }

        return redirect()->route('admin.stores.index')->with('success', 'Toko berhasil ditambahkan.');
    }

    public function edit(Store $store): Response
    {
        $store->load('auditees');

        return Inertia::render('Admin/Stores/Edit', [
            'store' => [
                'id'       => $store->id,
                'code'     => $store->code,
                'name'     => $store->name,
                'area'     => $store->area,
                'regional' => $store->regional,
                'status'   => $store->status,
                'auditees' => $store->auditees->pluck('id')->toArray(),
            ],
            'auditees' => User::whereHas('roles', fn ($q) => $q->where('name', 'auditee'))
                ->where('is_active', true)
                ->get(['id', 'name', 'email']),
        ]);
    }

    public function update(Request $request, Store $store): RedirectResponse
    {
        $validated = $request->validate([
            'code'       => 'required|string|max:20|unique:stores,code,' . $store->id,
            'name'       => 'required|string|max:255',
            'area'       => 'nullable|string|max:100',
            'regional'   => 'nullable|string|max:100',
            'status'     => 'required|in:active,inactive',
            'auditees'   => 'nullable|array',
            'auditees.*' => 'exists:users,id',
        ]);

        $store->update([
            'code'     => $validated['code'],
            'name'     => $validated['name'],
            'area'     => $validated['area'] ?? null,
            'regional' => $validated['regional'] ?? null,
            'status'   => $validated['status'],
        ]);

        if (isset($validated['auditees'])) {
            $store->auditees()->sync($validated['auditees']);
        }

        return redirect()->route('admin.stores.index')->with('success', 'Toko berhasil diperbarui.');
    }

    public function destroy(Store $store): RedirectResponse
    {
        if ($store->audits()->exists()) {
            return back()->with('error', 'Toko tidak dapat dihapus karena memiliki riwayat audit.');
        }

        $store->delete();

        return redirect()->route('admin.stores.index')->with('success', 'Toko berhasil dihapus.');
    }
}
