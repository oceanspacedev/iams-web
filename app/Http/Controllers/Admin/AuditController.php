<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Models\AuditCategory;
use App\Models\AuditNotification;
use App\Models\Finding;
use App\Models\Sop;
use App\Models\Store;
use App\Models\User;
use App\Services\AuditNotificationService;
use App\Services\WhatsAppService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditController extends Controller
{
    public function index(Request $request): Response
    {
        $audits = Audit::with(['store', 'auditor'])
            ->withCount('findings')
            ->orderBy('audit_date', 'desc')
            ->get()
            ->map(fn ($audit) => [
                'id'             => $audit->id,
                'audit_number'   => $audit->audit_number,
                'title'          => $audit->title,
                'store'          => $audit->store->name,
                'store_code'     => $audit->store->code,
                'auditor'        => $audit->auditor->name,
                'audit_date'     => $audit->audit_date->format('d M Y'),
                'audit_time'     => $audit->audit_time ?: '09:00',
                'status'         => $audit->status,
                'findings_count' => $audit->findings_count,
            ]);

        return Inertia::render('Admin/Audits/Index', [
            'audits' => $audits,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Audits/Create', [
            'stores'   => Store::active()->orderBy('name')->get(['id', 'name', 'code']),
            'auditors' => User::whereHas('roles', fn ($q) => $q->where('name', 'auditor'))
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'suggested_number' => Audit::generateNumber(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'audit_number' => 'required|string|max:50|unique:audits,audit_number',
            'title'        => 'nullable|string|max:255',
            'store_id'     => 'required|exists:stores,id',
            'auditor_id'   => 'required|exists:users,id',
            'audit_date'   => 'required|date',
            'audit_time'   => 'nullable|string',
            'location'     => 'nullable|string|max:255',
            'status'       => 'required|in:PLANNED,IN_PROGRESS,COMPLETED,CLOSED',
            'notes'        => 'nullable|string',
        ]);

        $audit = Audit::create($validated);

        // Auto calculate notification schedules based on rules (H-7, H-3, H-1, Hari H)
        $audit->syncNotificationSchedules();

        WhatsAppService::notifyAuditScheduled($audit);

        return redirect()->route('admin.audits.show', $audit)->with('success', 'Audit berhasil dibuat & jadwal notifikasi otomatis telah disinkronkan.');
    }

    public function show(Audit $audit): Response
    {
        $audit->load([
            'store',
            'auditor',
            'notifications.rule',
            'findings.category',
            'findings.sop',
            'findings.actionPlan',
            'findings.evidences.uploader',
        ]);

        // If no notifications scheduled yet, sync now
        if ($audit->notifications->isEmpty() && $audit->status === 'PLANNED') {
            $audit->syncNotificationSchedules();
            $audit->load('notifications.rule');
        }

        return Inertia::render('Admin/Audits/Show', [
            'audit' => [
                'id'           => $audit->id,
                'audit_number' => $audit->audit_number,
                'title'        => $audit->title,
                'store'        => $audit->store->only(['name', 'code', 'area', 'regional']),
                'auditor'      => $audit->auditor->only(['name', 'email']),
                'audit_date'   => $audit->audit_date->format('d M Y'),
                'audit_time'   => $audit->audit_time ?: '09:00',
                'location'     => $audit->location ?: ($audit->store ? "{$audit->store->name} ({$audit->store->code})" : '-'),
                'status'       => $audit->status,
                'notes'        => $audit->notes,
                'notifications' => $audit->notifications->sortBy('scheduled_at')->values()->map(fn ($n) => [
                    'id'           => $n->id,
                    'rule_name'    => $n->rule->name,
                    'days_before'  => $n->rule->days_before,
                    'scheduled_at' => $n->scheduled_at->format('d M Y H:i'),
                    'sent_at'      => $n->sent_at?->format('d M Y H:i'),
                    'channel'      => $n->channel,
                    'recipient'    => $n->recipient,
                    'status'       => $n->status,
                    'error_message'=> $n->error_message,
                ]),
                'findings'     => $audit->findings->map(fn ($f) => [
                    'id'             => $f->id,
                    'category'       => $f->category->name,
                    'sop'            => $f->sop?->only(['code', 'title']),
                    'finding'        => $f->finding,
                    'loss_amount'    => $f->loss_amount,
                    'severity'       => $f->severity,
                    'status'         => $f->status,
                    'recommendation' => $f->recommendation,
                    'action_plan'    => $f->actionPlan,
                ]),
            ],
            'categories' => AuditCategory::orderBy('name')->get(['id', 'name']),
            'sops'       => Sop::active()->orderBy('code')->get(['id', 'code', 'title']),
        ]);
    }

    public function edit(Audit $audit): Response
    {
        return Inertia::render('Admin/Audits/Edit', [
            'audit' => [
                'id'           => $audit->id,
                'audit_number' => $audit->audit_number,
                'title'        => $audit->title,
                'store_id'     => $audit->store_id,
                'auditor_id'   => $audit->auditor_id,
                'audit_date'   => $audit->audit_date->format('Y-m-d'),
                'audit_time'   => $audit->audit_time ?: '09:00',
                'location'     => $audit->location,
                'status'       => $audit->status,
                'notes'        => $audit->notes,
            ],
            'stores'   => Store::active()->orderBy('name')->get(['id', 'name', 'code']),
            'auditors' => User::role('auditor')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Audit $audit): RedirectResponse
    {
        $validated = $request->validate([
            'audit_number' => 'required|string|max:50|unique:audits,audit_number,' . $audit->id,
            'title'        => 'nullable|string|max:255',
            'store_id'     => 'required|exists:stores,id',
            'auditor_id'   => 'required|exists:users,id',
            'audit_date'   => 'required|date',
            'audit_time'   => 'nullable|string',
            'location'     => 'nullable|string|max:255',
            'status'       => 'required|in:PLANNED,IN_PROGRESS,COMPLETED,CLOSED',
            'notes'        => 'nullable|string',
        ]);

        $audit->update($validated);

        // Resync schedules with updated audit_date
        $audit->syncNotificationSchedules();

        return redirect()->route('admin.audits.show', $audit)->with('success', 'Audit berhasil diperbarui & jadwal notifikasi disinkronkan.');
    }

    public function destroy(Audit $audit): RedirectResponse
    {
        $audit->delete();

        return redirect()->route('admin.audits.index')->with('success', 'Audit berhasil dihapus.');
    }

    public function sendNotificationNow(AuditNotification $notification): RedirectResponse
    {
        $ok = AuditNotificationService::dispatch($notification);

        return back()->with($ok ? 'success' : 'error', $ok ? 'Notifikasi WhatsApp berhasil dikirim.' : ($notification->error_message ?: 'Gagal mengirim notifikasi.'));
    }

    public function storeFinding(Request $request, Audit $audit): RedirectResponse
    {
        $validated = $request->validate([
            'category_id'     => 'required|exists:audit_categories,id',
            'sop_id'          => 'nullable|exists:sops,id',
            'finding'         => 'required|string',
            'loss_amount'     => 'nullable|numeric|min:0',
            'auditor_opinion' => 'nullable|string',
            'recommendation'  => 'required|string',
            'severity'        => 'required|in:CRITICAL,MAJOR,MINOR,OBSERVATION',
            'status'          => 'required|in:OPEN,IN_PROGRESS,WAITING_VERIFICATION,VERIFIED,CLOSED',
        ]);

        $finding = $audit->findings()->create($validated);
        $finding->actionPlan()->create(['status' => 'OPEN']);
        $finding->followUp()->create([]);

        WhatsAppService::notifyFindingCreated($finding);

        return back()->with('success', 'Finding berhasil ditambahkan & notifikasi WhatsApp terkirim.');
    }
}
