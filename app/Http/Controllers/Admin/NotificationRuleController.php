<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Models\NotificationRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationRuleController extends Controller
{
    public function index(): Response
    {
        $rules = NotificationRule::orderBy('days_before', 'desc')->get();

        return Inertia::render('Admin/NotificationRules/Index', [
            'rules' => $rules,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:50',
            'days_before'    => 'required|integer|min:0|max:90',
            'send_time'      => 'required|string|regex:/^\d{2}:\d{2}$/',
            'channel'        => 'required|in:whatsapp,email,dashboard',
            'recipient_type' => 'required|in:all,auditee,auditor',
            'is_active'      => 'boolean',
        ]);

        NotificationRule::create($validated);

        // Resync for active planned audits
        Audit::active()->get()->each->syncNotificationSchedules();

        return back()->with('success', 'Aturan notifikasi baru berhasil ditambahkan.');
    }

    public function update(Request $request, NotificationRule $notificationRule): RedirectResponse
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:50',
            'days_before'    => 'required|integer|min:0|max:90',
            'send_time'      => 'required|string|regex:/^\d{2}:\d{2}$/',
            'channel'        => 'required|in:whatsapp,email,dashboard',
            'recipient_type' => 'required|in:all,auditee,auditor',
            'is_active'      => 'boolean',
        ]);

        $notificationRule->update($validated);

        // Resync for active planned audits
        Audit::active()->get()->each->syncNotificationSchedules();

        return back()->with('success', 'Aturan notifikasi berhasil diperbarui.');
    }

    public function toggleActive(NotificationRule $notificationRule): RedirectResponse
    {
        $notificationRule->update([
            'is_active' => !$notificationRule->is_active,
        ]);

        // Resync for active planned audits
        Audit::active()->get()->each->syncNotificationSchedules();

        return back()->with('success', 'Status aturan notifikasi berhasil diubah.');
    }

    public function destroy(NotificationRule $notificationRule): RedirectResponse
    {
        $notificationRule->delete();

        return back()->with('success', 'Aturan notifikasi berhasil dihapus.');
    }
}
