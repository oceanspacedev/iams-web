<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'availableRoles' => [
                [
                    'value' => 'chief',
                    'label' => 'Chief Auditor (Head of Audit)',
                ],
                [
                    'value' => 'asmen',
                    'label' => 'Asisten Manager (Asmen)',
                ],
                [
                    'value' => 'coordinator',
                    'label' => 'Koordinator Audit',
                ],
                [
                    'value' => 'auditor',
                    'label' => 'Auditor Lapangan',
                ],
            ],
        ]);
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'phone'          => 'required|string|max:25|unique:users,phone',
            'email'          => 'required|string|lowercase|email|max:255|unique:users,email',
            'requested_role' => 'required|in:chief,asmen,coordinator,auditor',
            'password'       => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'phone.required'     => 'Nomor WhatsApp wajib diisi untuk verifikasi dan notifikasi login.',
            'phone.unique'       => 'Nomor WhatsApp ini sudah terdaftar di sistem.',
            'email.unique'       => 'Alamat email ini sudah terdaftar.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $cleanPhone = preg_replace('/[^0-9]/', '', $validated['phone']);

        $user = User::create([
            'name'            => $validated['name'],
            'email'           => $validated['email'],
            'phone'           => $cleanPhone,
            'password'        => Hash::make($validated['password']),
            'is_active'       => false,
            'approval_status' => 'pending',
            'requested_role'  => $validated['requested_role'],
        ]);

        return redirect()->route('register.pending')
            ->with('registered_name', $user->name)
            ->with('registered_phone', $user->phone)
            ->with('registered_role', $user->requested_role);
    }

    /**
     * Display the pending approval confirmation screen.
     */
    public function pending(Request $request): Response
    {
        return Inertia::render('Auth/RegisterPending', [
            'name'  => session('registered_name'),
            'phone' => session('registered_phone'),
            'role'  => session('registered_role'),
        ]);
    }
}
