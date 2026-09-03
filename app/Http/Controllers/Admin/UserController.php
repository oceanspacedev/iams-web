<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\User;
use App\Services\WhatsAppService;
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
        $status = $request->query('status', 'all');

        $query = User::with(['roles', 'stores'])->orderByDesc('created_at');

        if ($status === 'pending') {
            $query->where('approval_status', 'pending');
        } elseif ($status === 'active') {
            $query->where('approval_status', 'approved')->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where(function ($q) {
                $q->where('approval_status', 'rejected')
                  ->orWhere(function ($sub) {
                      $sub->where('approval_status', 'approved')->where('is_active', false);
                  });
            });
        }

        $users = $query->get()->map(fn ($u) => [
            'id'              => $u->id,
            'name'            => $u->name,
            'email'           => $u->email,
            'phone'           => $u->phone ?: '—',
            'role'            => $u->roles->first()?->name ?? '-',
            'is_active'       => (bool) $u->is_active,
            'approval_status' => $u->approval_status ?? 'approved',
            'requested_role'  => $u->requested_role,
            'rejection_reason'=> $u->rejection_reason,
            'stores'          => $u->stores->pluck('name')->join(', ') ?: '—',
            'created_at'      => $u->created_at?->format('d M Y H:i'),
        ]);

        $stats = [
            'all'      => User::count(),
            'active'   => User::where('approval_status', 'approved')->where('is_active', true)->count(),
            'pending'  => User::where('approval_status', 'pending')->count(),
            'inactive' => User::where('approval_status', 'rejected')
                            ->orWhere(function ($sub) {
                                $sub->where('approval_status', 'approved')->where('is_active', false);
                            })->count(),
        ];

        return Inertia::render('Admin/Users/Index', [
            'users'         => $users,
            'currentStatus' => $status,
            'stats'         => $stats,
            'roles'         => Role::pluck('name'),
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
            'name'            => $validated['name'],
            'email'           => $validated['email'],
            'phone'           => $validated['phone'] ?? null,
            'password'        => Hash::make($validated['password']),
            'is_active'       => $validated['is_active'] ?? true,
            'approval_status' => 'approved',
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
                'id'              => $user->id,
                'name'            => $user->name,
                'email'           => $user->email,
                'phone'           => $user->phone ?? '',
                'role'            => $user->roles->first()?->name ?? 'auditor',
                'is_active'       => (bool)$user->is_active,
                'approval_status' => $user->approval_status ?? 'approved',
                'stores'          => $user->stores->pluck('id')->toArray(),
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
            'name'            => $validated['name'],
            'email'           => $validated['email'],
            'phone'           => $validated['phone'] ?? null,
            'is_active'       => $validated['is_active'] ?? true,
            'approval_status' => 'approved',
            ...(!empty($validated['password']) ? ['password' => Hash::make($validated['password'])] : []),
        ]);

        $user->syncRoles([$validated['role']]);

        if (isset($validated['stores'])) {
            $user->stores()->sync($validated['stores']);
        }

        return redirect()->route('admin.users.index')->with('success', 'Data saved! User berhasil diperbarui.');
    }

    public function approve(Request $request, User $user): RedirectResponse
    {
        $role = $request->input('role', $user->requested_role ?: 'auditor');

        $user->update([
            'approval_status' => 'approved',
            'is_active'       => true,
            'rejection_reason'=> null,
        ]);

        $user->syncRoles([$role]);

        // Send WhatsApp notification
        WhatsAppService::notifyAccountApproved($user, ucfirst($role));

        return back()->with('success', "Akun '{$user->name}' berhasil DISETUJUI dengan jabatan " . ucfirst($role) . " dan notifikasi WhatsApp telah dikirimkan.");
    }

    public function reject(Request $request, User $user): RedirectResponse
    {
        $reason = $request->input('reason');

        $user->update([
            'approval_status'  => 'rejected',
            'is_active'        => false,
            'rejection_reason' => $reason,
        ]);

        // Send WhatsApp notification
        WhatsAppService::notifyAccountRejected($user, $reason);

        return back()->with('success', "Permohonan akun '{$user->name}' telah DITOLAK dan notifikasi WhatsApp telah dikirimkan.");
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
