<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Models\AuditDocument;
use App\Models\Evidence;
use App\Models\QualityFinding;
use App\Models\Store;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

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
            'roles'         => $this->assignableRoles(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Users/Create', [
            'roles'  => $this->assignableRoles(),
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
            'role'      => ['required', Rule::in($this->assignableRoles())],
            'is_active' => 'boolean',
            'stores'    => 'nullable|array|required_if:role,auditee',
            'stores.*'  => 'exists:stores,id',
        ], [
            'stores.required_if' => 'Pilih minimal satu toko untuk auditee.',
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

        // store_user holds auditee assignments only
        $user->stores()->sync($validated['role'] === 'auditee' ? ($validated['stores'] ?? []) : []);

        return redirect()->route('admin.users.index')->with('success', 'User berhasil dibuat.');
    }

    public function edit(User $user): Response|RedirectResponse
    {
        if ($this->isProtectedAdmin($user)) {
            return redirect()->route('admin.users.index')->with('error', 'Chief tidak dapat mengubah akun admin.');
        }

        $user->load(['roles', 'stores']);
        $assignedStoreIds = $user->stores->pluck('id')->toArray();

        return Inertia::render('Admin/Users/Edit', [
            'user' => [
                'id'              => $user->id,
                'name'            => $user->name,
                'email'           => $user->email,
                'phone'           => $user->phone ?? '',
                'role'            => $user->roles->first()?->name ?? 'auditor',
                'is_active'       => (bool)$user->is_active,
                'approval_status' => $user->approval_status ?? 'approved',
                'stores'          => $assignedStoreIds,
            ],
            'roles'  => $this->assignableRoles(),
            'stores' => Store::where(function ($q) use ($assignedStoreIds) {
                    $q->where('status', 'active')->orWhereIn('id', $assignedStoreIds);
                })->orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        if ($this->isProtectedAdmin($user)) {
            return back()->with('error', 'Chief tidak dapat mengubah akun admin.');
        }

        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone'     => 'nullable|string|max:20',
            'password'  => 'nullable|string|min:6',
            'role'      => ['required', Rule::in($this->assignableRoles())],
            'is_active' => 'boolean',
            'stores'    => 'nullable|array|required_if:role,auditee',
            'stores.*'  => 'exists:stores,id',
        ], [
            'stores.required_if' => 'Pilih minimal satu toko untuk auditee.',
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

        // store_user holds auditee assignments only
        $user->stores()->sync($validated['role'] === 'auditee' ? ($validated['stores'] ?? []) : []);

        return redirect()->route('admin.users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function approve(Request $request, User $user): RedirectResponse
    {
        if ($this->isProtectedAdmin($user)) {
            return back()->with('error', 'Chief tidak dapat mengubah akun admin.');
        }

        $request->merge(['role' => $request->input('role') ?: ($user->requested_role ?: 'auditor')]);

        $validated = $request->validate([
            'role' => ['required', Rule::in($this->assignableRoles())],
        ], [
            'role.in' => 'Jabatan tidak valid.',
        ]);

        $role = $validated['role'];

        $user->update([
            'approval_status' => 'approved',
            'is_active'       => true,
            'rejection_reason'=> null,
        ]);

        $user->syncRoles([$role]);

        // Send WhatsApp notification
        WhatsAppService::notifyAccountApproved($user, ucfirst($role));

        return back()->with('success', "Akun {$user->name} disetujui sebagai " . ucfirst($role) . '.');
    }

    public function reject(Request $request, User $user): RedirectResponse
    {
        if ($this->isProtectedAdmin($user)) {
            return back()->with('error', 'Chief tidak dapat mengubah akun admin.');
        }

        $reason = $request->input('reason');

        $user->update([
            'approval_status'  => 'rejected',
            'is_active'        => false,
            'rejection_reason' => $reason,
        ]);

        // Send WhatsApp notification
        WhatsAppService::notifyAccountRejected($user, $reason);

        return back()->with('success', "Permohonan akun {$user->name} ditolak.");
    }

    public function toggleActive(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
        }

        if ($this->isProtectedAdmin($user)) {
            return back()->with('error', 'Chief tidak dapat mengubah akun admin.');
        }

        $user->update(['is_active' => !$user->is_active]);

        return back()->with('success', 'Status user berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        if ($this->isProtectedAdmin($user)) {
            return back()->with('error', 'Chief tidak dapat menghapus akun admin.');
        }

        $isReferenced = Audit::withTrashed()->where('auditor_id', $user->id)->exists()
            || DB::table('audit_auditor')->where('user_id', $user->id)->exists()
            || Evidence::where('uploaded_by', $user->id)->orWhere('verified_by', $user->id)->exists()
            || AuditDocument::where('uploaded_by', $user->id)->exists()
            || QualityFinding::withTrashed()->where('reported_by', $user->id)->exists();

        if ($isReferenced) {
            return back()->with('error', 'User tidak dapat dihapus karena memiliki riwayat audit. Nonaktifkan akun ini sebagai gantinya.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User berhasil dihapus.');
    }

    /**
     * Roles the current user may assign. Chief cannot create or assign admin.
     */
    private function assignableRoles(): array
    {
        $roles = ['admin', 'chief', 'asmen', 'coordinator', 'auditor', 'auditee'];

        if (!auth()->user()?->hasRole('admin')) {
            $roles = array_values(array_diff($roles, ['admin']));
        }

        return $roles;
    }

    /**
     * True when a non-admin (chief) tries to manage an admin account.
     */
    private function isProtectedAdmin(User $user): bool
    {
        return $user->hasRole('admin') && !auth()->user()?->hasRole('admin');
    }
}
