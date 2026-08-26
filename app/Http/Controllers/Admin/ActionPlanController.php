<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActionPlan;
use App\Services\WhatsAppService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;
use Inertia\Response;

class ActionPlanController extends Controller
{
    public function index(Request $request): Response
    {
        $actionPlans = ActionPlan::with(['finding.audit.store', 'finding.category'])
            ->get()
            ->map(fn ($ap) => [
                'id'             => $ap->id,
                'finding_id'     => $ap->finding_id,
                'audit_number'   => $ap->finding?->audit?->audit_number ?? '—',
                'store'          => $ap->finding?->audit?->store?->name ?? '—',
                'category'       => $ap->finding?->category?->name ?? '—',
                'finding'        => \Str::limit($ap->finding?->finding ?? '', 50),
                'action_plan'    => $ap->action_plan,
                'response'       => $ap->response,
                'pic'            => $ap->pic,
                'deadline'       => $ap->deadline?->format('d M Y'),
                'is_overdue'     => $ap->isOverdue(),
                'status'         => $ap->status,
                'finding_status' => $ap->finding?->status ?? '—',
            ]);

        return Inertia::render('Admin/ActionPlans/Index', [
            'action_plans' => $actionPlans,
        ]);
    }

    public function update(Request $request, ActionPlan $actionPlan): RedirectResponse
    {
        $validated = $request->validate([
            'status'   => 'required|in:OPEN,IN_PROGRESS,COMPLETED,OVERDUE',
            'pic'      => 'nullable|string|max:255',
            'deadline' => 'nullable|date',
        ]);

        $actionPlan->update($validated);

        return back()->with('success', 'Status Action Plan berhasil diperbarui.');
    }

    /**
     * Send instant WhatsApp reminder for specific Action Plan.
     */
    public function sendReminder(ActionPlan $actionPlan): RedirectResponse
    {
        $type = $actionPlan->isOverdue() ? 'OVERDUE' : (empty($actionPlan->action_plan) ? 'UNFILLED' : 'UPCOMING');
        $sentCount = WhatsAppService::notifyActionPlanReminder($actionPlan, $type);

        if ($sentCount > 0) {
            return back()->with('success', "Pengingat WhatsApp berhasil dikirim ke {$sentCount} kontak tim toko.");
        }

        return back()->with('warning', 'Pesan tidak terkirim. Pastikan nomor WhatsApp toko sudah terisi dengan benar.');
    }

    /**
     * Trigger batch reminder cron command manually from admin.
     */
    public function broadcastReminders(): RedirectResponse
    {
        Artisan::call('audit:send-reminders');

        return back()->with('success', 'Proses pengingat WhatsApp otomatis (Cron Job) berhasil dijalankan.');
    }
}
