<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Models\Finding;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $totalFindings = Finding::count();
        $pendingReviews = Finding::where('is_severity_locked', false)->count();
        $reviewedLocked = Finding::where('is_severity_locked', true)->count();
        $totalAudits   = Audit::count();
        $totalLoss     = Finding::sum('loss_amount');

        // Recent findings waiting for review
        $pendingFindingsList = Finding::where('is_severity_locked', false)
            ->with(['audit.store', 'audit.auditor', 'category'])
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get()
            ->map(fn ($f) => [
                'id'           => $f->id,
                'audit_number' => $f->audit->audit_number,
                'store'        => $f->audit->store->name,
                'auditor'      => $f->audit->auditor->name,
                'category'     => $f->category->name,
                'finding'      => $f->finding,
                'severity'     => $f->severity,
                'loss_amount'  => $f->loss_amount,
                'created_at'   => $f->created_at->format('d M Y H:i'),
            ]);

        return Inertia::render('Coordinator/Dashboard', [
            'stats' => [
                'total_findings'  => $totalFindings,
                'pending_reviews' => $pendingReviews,
                'reviewed_locked' => $reviewedLocked,
                'total_audits'    => $totalAudits,
                'total_loss'      => $totalLoss,
            ],
            'pending_findings' => $pendingFindingsList,
        ]);
    }
}
