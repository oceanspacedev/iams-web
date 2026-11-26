<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Models\AuditNotification;
use App\Models\Store;
use App\Models\User;
use App\Services\AuditNotificationService;
use App\Services\WhatsAppService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AuditController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Audit::with(['store', 'auditor', 'auditors', 'category'])
            ->withCount('findings')
            ->orderBy('audit_date', 'desc');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->query('category_id'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('audit_number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhereHas('store', fn ($s) => $s->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
            });
        }

        $audits = $query->get()
            ->map(fn ($a) => [
                'id'             => $a->id,
                'audit_number'   => $a->audit_number,
                'title'          => $a->title,
                'category'       => $a->category?->name ?? '—',
                'category_id'    => $a->category_id,
                'store'          => $a->store?->name ?? '—',
                'store_code'     => $a->store?->code ?? '—',
                'auditor'        => $a->auditor?->name ?? '—',
                'team_auditors'  => $a->auditors->pluck('name')->join(', '),
                'audit_date'     => $a->audit_date->format('d M Y'),
                'audit_time'     => $a->audit_time ?: '09:00',
                'status'         => $a->status,
                'findings_count' => $a->findings_count,
            ]);

        return Inertia::render('Coordinator/Audits/Index', [
            'audits'     => $audits,
            'categories' => \App\Models\AuditCategory::active()->orderBy('name')->get(['id', 'name']),
            'filters'    => [
                'category_id' => $request->query('category_id', ''),
                'search'      => $request->query('search', ''),
            ],
        ]);
    }

    public function create(): Response
    {
        $upcomingAudits = Audit::with(['store:id,name,code', 'category:id,name', 'auditor:id,name', 'auditors:id,name'])
            ->whereIn('status', ['PLANNED', 'IN_PROGRESS'])
            ->orderBy('audit_date', 'asc')
            ->orderBy('audit_time', 'asc')
            ->limit(6)
            ->get()
            ->map(fn ($a) => [
                'id'                   => $a->id,
                'audit_number'         => $a->audit_number,
                'title'                => $a->title,
                'store_id'             => $a->store_id,
                'store_name'           => $a->store?->name ?? '—',
                'store_code'           => $a->store?->code ?? '',
                'category'             => $a->category?->name ?? '—',
                'audit_date'           => $a->audit_date?->format('Y-m-d'),
                'audit_date_formatted' => $a->audit_date?->format('d M Y'),
                'audit_time'           => $a->audit_time ?: '09:00',
                'status'               => $a->status,
                'lead_auditor'         => $a->auditor?->name ?? '—',
                'auditors_count'       => $a->auditors->count(),
            ]);

        return Inertia::render('Coordinator/Audits/Create', [
            'stores'          => Store::active()->orderBy('name')->get(['id', 'name', 'code', 'business_entity', 'type', 'address', 'area']),
            'categories'      => \App\Models\AuditCategory::active()->orderBy('name')->get(['id', 'name']),
            'auditors'        => $this->auditorOptions(),
            'suggested_number' => Audit::generateNumber(),
            'upcoming_audits'  => $upcomingAudits,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'audit_number'       => 'required|string|max:50|unique:audits,audit_number',
            'title'              => 'nullable|string|max:255',
            'category_id'        => 'nullable|exists:audit_categories,id',
            'store_id'           => 'nullable|exists:stores,id',
            'custom_store_name'  => 'nullable|string|max:255',
            'auditor_ids'        => 'required|array|min:1|max:5',
            'auditor_ids.*'      => 'exists:users,id',
            'audit_date'         => 'required|date',
            'audit_time'         => 'nullable|string',
            'location'           => 'nullable|string|max:255',
            'status'             => 'required|in:PLANNED,IN_PROGRESS,COMPLETED,CLOSED',
            'notes'              => 'nullable|string',
        ]);

        // Resolve store: either selected from existing, or free text input
        if (!empty($validated['custom_store_name'])) {
            $storeName = trim($validated['custom_store_name']);
            $cleanCodeSlug = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $storeName), 0, 4)) ?: 'STR';
            $store = Store::firstOrCreate(
                ['name' => $storeName],
                [
                    'code'            => 'CSA-' . $cleanCodeSlug . '-' . rand(100, 999),
                    'business_entity' => 'CSA Retail / Unit',
                    'type'            => 'toko',
                    'status'          => 'active',
                ]
            );
            $storeId = $store->id;
        } elseif (!empty($validated['store_id'])) {
            $storeId = $validated['store_id'];
        } else {
            return back()->withErrors(['store_id' => 'Silakan pilih toko yang ada atau ketik nama toko / badan usaha baru.'])->withInput();
        }

        $leadAuditorId = $validated['auditor_ids'][0];
        if (!$this->isAuditorRole($leadAuditorId)) {
            return back()->withErrors(['auditor_ids' => 'Lead auditor harus pengguna dengan peran auditor.'])->withInput();
        }

        $audit = Audit::create([
            'audit_number' => $validated['audit_number'],
            'title'        => $validated['title'] ?? null,
            'category_id'  => $validated['category_id'] ?? null,
            'store_id'     => $storeId,
            'auditor_id'   => $leadAuditorId,
            'audit_date'   => $validated['audit_date'],
            'audit_time'   => $validated['audit_time'] ?? '09:00',
            'location'     => $validated['location'] ?? null,
            'status'       => $validated['status'],
            'notes'        => $validated['notes'] ?? null,
        ]);

        $audit->auditors()->sync($validated['auditor_ids']);

        // Auto calculate notification schedules
        $audit->syncNotificationSchedules();

        WhatsAppService::notifyAuditScheduled($audit);

        return redirect()->route('coordinator.audits.show', $audit)->with('success', 'Audit berhasil dijadwalkan dengan ' . count($validated['auditor_ids']) . ' auditor.');
    }

    public function show(Audit $audit): Response
    {
        $audit->load([
            'category',
            'store',
            'auditor',
            'auditors',
            'documents.uploader',
            'notifications.rule',
            'findings.category',
            'findings.sop',
            'findings.severityReviewer',
            'findings.actionPlan',
        ]);

        if ($audit->notifications->isEmpty() && $audit->status === 'PLANNED') {
            $audit->syncNotificationSchedules();
            $audit->load('notifications.rule');
        }

        return Inertia::render('Coordinator/Audits/Show', [
            'audit' => [
                'id'           => $audit->id,
                'audit_number' => $audit->audit_number,
                'title'        => $audit->title,
                'category'     => $audit->category?->name,
                'category_id'  => $audit->category_id,
                'store'        => $audit->store->only(['name', 'code', 'business_entity', 'type', 'area', 'regional']),
                'auditor'      => $audit->auditor->only(['name', 'email', 'phone']),
                'auditors'     => $audit->auditors->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email]),
                'audit_date'   => $audit->audit_date->format('d M Y'),
                'audit_time'   => $audit->audit_time ?: '09:00',
                'location'     => $audit->location ?: ($audit->store ? "{$audit->store->name} ({$audit->store->code})" : '-'),
                'status'       => $audit->status,
                'notes'        => $audit->notes,
                'documents'    => $audit->documents->map(fn ($d) => [
                    'id'            => $d->id,
                    'document_type' => $d->document_type,
                    'title'         => $d->title,
                    'file_name'     => $d->file_name,
                    'file_size'     => $d->file_size,
                    'file_url'      => $d->file_url,
                    'notes'         => $d->notes,
                    'uploaded_by'   => $d->uploader->name,
                    'created_at'    => $d->created_at->copy()->timezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB',
                ]),
                'notifications' => $audit->notifications->sortBy('scheduled_at')->values()->map(fn ($n) => [
                    'id'           => $n->id,
                    'rule_name'    => $n->rule->name,
                    'days_before'  => $n->rule->days_before,
                    'scheduled_at' => $n->scheduled_at->copy()->timezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB',
                    'sent_at'      => $n->sent_at?->copy()->timezone('Asia/Jakarta')->format('d M Y H:i') . ($n->sent_at ? ' WIB' : ''),
                    'channel'      => $n->channel,
                    'recipient'    => $n->recipient,
                    'status'       => $n->status,
                    'error_message'=> $n->error_message,
                ]),
                'findings'     => $audit->findings->map(fn ($f) => [
                    'id'                   => $f->id,
                    'category'             => $f->category->name,
                    'sop'                  => $f->sop?->only(['code', 'title']),
                    'finding'              => $f->finding,
                    'loss_amount'          => $f->loss_amount,
                    'surplus_amount'       => $f->surplus_amount,
                    'severity'             => $f->severity,
                    'severity_status'      => $f->severity_status,
                    'is_severity_locked'   => $f->is_severity_locked,
                    'severity_reviewed_by' => $f->severityReviewer?->name,
                    'status'               => $f->status,
                    'recommendation'       => $f->recommendation,
                    'action_plan'          => $f->actionPlan,
                ]),
            ],
        ]);
    }

    public function edit(Audit $audit): Response
    {
        $audit->load('auditors', 'store');
        $selectedAuditorIds = $audit->auditors->pluck('id')->toArray();
        if (empty($selectedAuditorIds) && $audit->auditor_id) {
            $selectedAuditorIds = [$audit->auditor_id];
        }

        $stores = Store::active()->orderBy('name')->get(['id', 'name', 'code', 'business_entity', 'type', 'address', 'area']);
        if ($audit->store_id && !$stores->contains('id', $audit->store_id) && $audit->store) {
            $stores->prepend($audit->store);
        }

        $auditors = $this->auditorOptions();

        $missingAuditorIds = array_diff($selectedAuditorIds, $auditors->pluck('id')->toArray());
        if (!empty($missingAuditorIds)) {
            $extraAuditors = $this->auditorOptions(User::whereIn('id', $missingAuditorIds));
            $auditors = $auditors->concat($extraAuditors)->values();
        }

        return Inertia::render('Coordinator/Audits/Edit', [
            'audit' => [
                'id'           => $audit->id,
                'audit_number' => $audit->audit_number,
                'title'        => $audit->title,
                'category_id'  => $audit->category_id,
                'store_id'     => $audit->store_id,
                'auditor_id'   => $audit->auditor_id,
                'auditor_ids'  => $selectedAuditorIds,
                'audit_date'   => $audit->audit_date->format('Y-m-d'),
                'audit_time'   => $audit->audit_time ?: '09:00',
                'location'     => $audit->location,
                'status'       => $audit->status,
                'notes'        => $audit->notes,
            ],
            'categories' => \App\Models\AuditCategory::active()->orderBy('name')->get(['id', 'name']),
            'stores'     => $stores,
            'auditors'   => $auditors,
        ]);
    }

    public function update(Request $request, Audit $audit): RedirectResponse
    {
        $validated = $request->validate([
            'audit_number'       => 'required|string|max:50|unique:audits,audit_number,' . $audit->id,
            'title'              => 'nullable|string|max:255',
            'category_id'        => 'nullable|exists:audit_categories,id',
            'store_id'           => 'nullable|exists:stores,id',
            'custom_store_name'  => 'nullable|string|max:255',
            'auditor_ids'        => 'required|array|min:1|max:5',
            'auditor_ids.*'      => 'exists:users,id',
            'audit_date'         => 'required|date',
            'audit_time'         => 'nullable|string',
            'location'           => 'nullable|string|max:255',
            'status'             => 'required|in:PLANNED,IN_PROGRESS,COMPLETED,CLOSED',
            'notes'              => 'nullable|string',
        ]);

        if (!empty($validated['custom_store_name'])) {
            $storeName = trim($validated['custom_store_name']);
            $cleanCodeSlug = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $storeName), 0, 4)) ?: 'STR';
            $store = Store::firstOrCreate(
                ['name' => $storeName],
                [
                    'code'            => 'CSA-' . $cleanCodeSlug . '-' . rand(100, 999),
                    'business_entity' => 'CSA Retail / Unit',
                    'type'            => 'toko',
                    'status'          => 'active',
                ]
            );
            $storeId = $store->id;
        } elseif (!empty($validated['store_id'])) {
            $storeId = $validated['store_id'];
        } else {
            $storeId = $audit->store_id;
        }

        $leadAuditorId = $validated['auditor_ids'][0];
        if (!$this->isAuditorRole($leadAuditorId)) {
            return back()->withErrors(['auditor_ids' => 'Lead auditor harus pengguna dengan peran auditor.'])->withInput();
        }

        $audit->update([
            'audit_number' => $validated['audit_number'],
            'title'        => $validated['title'] ?? null,
            'category_id'  => $validated['category_id'] ?? null,
            'store_id'     => $storeId,
            'auditor_id'   => $leadAuditorId,
            'audit_date'   => $validated['audit_date'],
            'audit_time'   => $validated['audit_time'] ?? '09:00',
            'location'     => $validated['location'] ?? null,
            'status'       => $validated['status'],
            'notes'        => $validated['notes'] ?? null,
        ]);

        $audit->auditors()->sync($validated['auditor_ids']);

        // Resync schedules with updated audit_date
        $audit->syncNotificationSchedules();

        return redirect()->route('coordinator.audits.show', $audit)->with('success', 'Audit berhasil diperbarui.');
    }

    public function destroy(Audit $audit): RedirectResponse
    {
        DB::transaction(function () use ($audit) {
            // Soft-delete child findings so shared readers never see orphans
            $audit->findings()->delete();
            $audit->notifications()->where('status', 'PENDING')->delete();
            $audit->delete();
        });

        return redirect()->route('coordinator.audits.index')->with('success', 'Audit berhasil dihapus.');
    }

    public function sendNotificationNow(AuditNotification $notification): RedirectResponse
    {
        $ok = AuditNotificationService::dispatch($notification);

        return back()->with($ok ? 'success' : 'error', $ok ? 'Notifikasi WhatsApp berhasil dikirim.' : ($notification->error_message ?: 'Gagal mengirim notifikasi.'));
    }

    /**
     * Active auditors and coordinators for the team picker. `can_lead` marks users
     * with the auditor role, the only ones allowed as lead auditor.
     */
    private function auditorOptions($query = null)
    {
        $query ??= User::whereHas('roles', fn ($q) => $q->whereIn('name', ['auditor', 'coordinator']))
            ->where('is_active', true);

        return $query->with('roles:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn ($u) => [
                'id'       => $u->id,
                'name'     => $u->name,
                'email'    => $u->email,
                'can_lead' => $u->roles->contains('name', 'auditor'),
            ])
            ->values();
    }

    private function isAuditorRole($userId): bool
    {
        return User::whereKey($userId)
            ->whereHas('roles', fn ($q) => $q->where('name', 'auditor'))
            ->exists();
    }
}
