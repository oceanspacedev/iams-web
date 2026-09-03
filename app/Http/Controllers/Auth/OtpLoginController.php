<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserOtp;
use App\Services\WhatsAppService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OtpLoginController extends Controller
{
    /**
     * Request a 6-digit login OTP sent to user's WhatsApp.
     */
    public function requestOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'phone' => 'required|string|max:50',
        ], [
            'phone.required' => 'Nomor WhatsApp atau Email wajib diisi.',
        ]);

        $input = trim($request->input('phone'));
        $user = $this->findUserByPhoneOrEmail($input);

        if (!$user) {
            return back()->withErrors([
                'phone' => 'Nomor WhatsApp atau Email belum terdaftar di sistem.',
            ])->withInput();
        }

        if ($user->approval_status === 'pending') {
            return back()->withErrors([
                'phone' => 'Pendaftaran akun Anda masih MENUNGGU PERSETUJUAN dari Administrator.',
            ])->withInput();
        }

        if ($user->approval_status === 'rejected') {
            return back()->withErrors([
                'phone' => 'Permohonan pendaftaran akun Anda ditolak oleh Administrator.',
            ])->withInput();
        }

        if (!$user->is_active) {
            return back()->withErrors([
                'phone' => 'Akun Anda sedang dinonaktifkan. Silakan hubungi Administrator.',
            ])->withInput();
        }

        if (empty($user->phone)) {
            return back()->withErrors([
                'phone' => 'Akun ' . $user->name . ' belum memiliki nomor WhatsApp terdaftar. Silakan hubungi Administrator untuk mendaftarkan nomor Anda.',
            ])->withInput();
        }

        // Cooldown check (prevent requesting more than once every 45 seconds)
        $latestOtp = UserOtp::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subSeconds(45))
            ->first();

        if ($latestOtp) {
            $secondsLeft = 45 - now()->diffInSeconds($latestOtp->created_at);
            return back()->withErrors([
                'phone' => "Mohon tunggu {$secondsLeft} detik sebelum meminta kode OTP kembali.",
            ])->withInput();
        }

        // Rate limiting check (max 5 requests per 15 minutes)
        $recentCount = UserOtp::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subMinutes(15))
            ->count();

        if ($recentCount >= 5) {
            return back()->withErrors([
                'phone' => 'Terlalu banyak permintaan OTP. Silakan coba kembali dalam 15 menit.',
            ])->withInput();
        }

        // Generate 6-digit OTP code
        $otpCode = sprintf('%06d', random_int(100000, 999999));

        // Invalidate old unexpired OTPs
        UserOtp::where('user_id', $user->id)->delete();

        // Save new OTP record
        UserOtp::create([
            'user_id'    => $user->id,
            'phone'      => $user->phone,
            'otp_code'   => $otpCode,
            'expires_at' => now()->addMinutes(5),
            'ip_address' => $request->ip(),
        ]);

        // Send via WhatsApp
        $sent = WhatsAppService::sendLoginOtp($user->phone, $otpCode, $user->name);

        Log::info("Login OTP generated for user #{$user->id} ({$user->name}) phone: {$user->phone}, code: {$otpCode}, sent: " . ($sent ? 'YES' : 'NO'));

        $maskedPhone = $this->maskPhoneNumber($user->phone);

        $flashMessage = "Kode OTP 6-digit telah dikirim ke WhatsApp {$maskedPhone}. Berlaku selama 5 menit.";
        if (app()->environment('local') && !$sent) {
            $flashMessage .= " (Mode Dev: Kode OTP adalah {$otpCode})";
        }

        return back()
            ->with('status', $flashMessage)
            ->with('otp_sent', true)
            ->with('verified_phone', $user->phone)
            ->with('masked_phone', $maskedPhone);
    }

    /**
     * Verify OTP and log the user in.
     */
    public function verifyOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'otp'   => 'required|string|size:6',
        ], [
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.size'     => 'Kode OTP harus terdiri dari 6 digit angka.',
        ]);

        $inputPhone = trim($request->input('phone'));
        $otpInput   = trim($request->input('otp'));

        $user = $this->findUserByPhoneOrEmail($inputPhone);

        if (!$user) {
            return back()->withErrors([
                'otp' => 'Data pengguna tidak ditemukan. Silakan minta kode OTP baru.',
            ]);
        }

        // Retrieve active OTP record
        $otpRecord = UserOtp::where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$otpRecord) {
            return back()->withErrors([
                'otp' => 'Kode OTP telah kedaluwarsa atau belum diminta. Silakan klik "Kirim Ulang".',
            ]);
        }

        // Check attempts limit
        if ($otpRecord->attempts >= 5) {
            $otpRecord->delete();
            return back()->withErrors([
                'otp' => 'Anda telah salah memasukkan OTP sebanyak 5 kali. Kode telah dibatalkan demi keamanan. Silakan minta kode baru.',
            ]);
        }

        // Verify code
        if ($otpRecord->otp_code !== $otpInput) {
            $otpRecord->increment('attempts');
            $remaining = 5 - $otpRecord->attempts;
            return back()->withErrors([
                'otp' => "Kode OTP tidak cocok. Sisa kesempatan mencoba: {$remaining} kali.",
            ]);
        }

        // Success: Clean up OTPs and log user in
        UserOtp::where('user_id', $user->id)->delete();

        Auth::login($user, $request->boolean('remember', true));
        $request->session()->regenerate();

        return redirect()->intended($user->getRedirectRoute());
    }

    /**
     * Find user by phone number variations or email.
     */
    private function findUserByPhoneOrEmail(string $input): ?User
    {
        if (filter_var($input, FILTER_VALIDATE_EMAIL)) {
            return User::where('email', strtolower($input))->first();
        }

        $clean = preg_replace('/[^0-9]/', '', $input);

        if (empty($clean)) {
            return null;
        }

        // Generate potential phone variants:
        // 0812... <-> 62812...
        $variants = [$clean];
        if (str_starts_with($clean, '62')) {
            $variants[] = '0' . substr($clean, 2);
        } elseif (str_starts_with($clean, '0')) {
            $variants[] = '62' . substr($clean, 1);
        } elseif (str_starts_with($clean, '8')) {
            $variants[] = '0' . $clean;
            $variants[] = '62' . $clean;
        }

        return User::whereIn('phone', $variants)->first();
    }

    /**
     * Mask phone number for display (e.g. 0812****0502).
     */
    private function maskPhoneNumber(string $phone): string
    {
        $len = strlen($phone);
        if ($len <= 6) {
            return $phone;
        }

        $start = substr($phone, 0, 4);
        $end   = substr($phone, -3);
        $stars = str_repeat('*', max(3, $len - 7));

        return $start . $stars . $end;
    }
}
