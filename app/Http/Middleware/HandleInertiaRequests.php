<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames(),
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
                'message' => fn () => $request->session()->get('message'),
            ],
            'notifications' => function () use ($user) {
                if (!$user || !$user->hasAnyRole(['admin', 'chief'])) {
                    return ['count' => 0, 'items' => []];
                }

                $pendingUsers = \App\Models\User::where('approval_status', 'pending')
                    ->orderByDesc('created_at')
                    ->take(5)
                    ->get(['id', 'name', 'phone', 'requested_role', 'created_at']);

                return [
                    'count' => \App\Models\User::where('approval_status', 'pending')->count(),
                    'items' => $pendingUsers->map(function ($u) {
                        $roleName = match ($u->requested_role) {
                            'chief'       => 'Chief Auditor',
                            'asmen'       => 'Asisten Manager',
                            'coordinator' => 'Koordinator Audit',
                            default       => 'Auditor Lapangan',
                        };

                        return [
                            'id'      => $u->id,
                            'title'   => 'Pendaftaran Akun Baru',
                            'message' => "{$u->name} mengajukan jabatan {$roleName}",
                            'time'    => $u->created_at ? $u->created_at->diffForHumans() : 'Baru saja',
                            'url'     => route('admin.users.index', ['status' => 'pending']),
                        ];
                    }),
                ];
            },
        ];
    }
}
