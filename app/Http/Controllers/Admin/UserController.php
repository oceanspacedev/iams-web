<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::with(['roles', 'stores'])
            ->orderBy('name')
            ->get()
            ->map(fn ($u) => [
                'id'        => $u->id,
                'name'      => $u->name,
                'email'     => $u->email,
                'phone'     => $u->phone ?: '—',
                'role'      => $u->roles->first()?->name ?? '-',
                'is_active' => $u->is_active,
                'stores'    => $u->stores->pluck('name')->join(', ') ?: '—',
            ]);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Users/Create', [
            'roles'  => Role::where('name', '!=', 'auditee')->pluck('name'),
            'stores' => Store::active()->orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email|max:255',
            'phone'     => 'nullable|string|max:20',
            'password'  => 'required|string|min:6',
            'role'      => 'required|in:admin,chief,asmen,coordinator,auditor',
            'is_active' => 'boolean',
            'stores'    => 'nullable|array',
            'stores.*'  => 'exists:stores,id',
        ]);

        $user = User::create([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'phone'     => $validated['phone'] ?? null,
            'password'  => Hash::make($validated['password']),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $user->assignRole($validated['role']);

        if (!empty($validated['stores'])) {
            $user->stores()->sync($validated['stores']);
        }

        return redirect()->route('admin.users.index')->with('success', 'Data saved! User berhasil dibuat.');
    }

    public function edit(User $user): Response
    {
        $user->load(['roles', 'stores']);

        return Inertia::render('Admin/Users/Edit', [
            'user' => [
                'id'        => $user->id,
                'name'      => $user->name,
                'email'     => $user->email,
                'phone'     => $user->phone ?? '',
                'role'      => $user->roles->first()?->name ?? 'auditor',
                'is_active' => (bool)$user->is_active,
                'stores'    => $user->stores->pluck('id')->toArray(),
            ],
            'roles'  => Role::where('name', '!=', 'auditee')->pluck('name'),
            'stores' => Store::active()->orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone'     => 'nullable|string|max:20',
            'password'  => 'nullable|string|min:6',
            'role'      => 'required|in:admin,chief,asmen,coordinator,auditor',
            'is_active' => 'boolean',
            'stores'    => 'nullable|array',
            'stores.*'  => 'exists:stores,id',
        ]);

        $user->update([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'phone'     => $validated['phone'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            ...(!empty($validated['password']) ? ['password' => Hash::make($validated['password'])] : []),
        ]);

        $user->syncRoles([$validated['role']]);

        if (isset($validated['stores'])) {
            $user->stores()->sync($validated['stores']);
        }

        return redirect()->route('admin.users.index')->with('success', 'Data saved! User berhasil diperbarui.');
    }

    public function toggleActive(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
        }

        $user->update(['is_active' => !$user->is_active]);

        return back()->with('success', 'Status user berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User berhasil dihapus.');
    }
}
