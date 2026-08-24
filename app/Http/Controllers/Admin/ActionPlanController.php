<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActionPlanController extends Controller
{
    public function index(Request $request): Response
    {
        $actionPlans = ActionPlan::with(['finding.audit.store', 'finding.category'])
            ->get()
            ->map(fn ($ap) => [
                'id'           => $ap->id,
                'finding_id'   => $ap->finding_id,
                'audit_number' => $ap->finding->audit->audit_number,
                'store'        => $ap->finding->audit->store->name,
                'category'     => $ap->finding->category->name,
                'finding'      => \Str::limit($ap->finding->finding, 50),
                'action_plan'  => $ap->action_plan,
                'response'     => $ap->response,
                'pic'          => $ap->pic,
                'deadline'     => $ap->deadline?->format('d M Y'),
                'is_overdue'   => $ap->isOverdue(),
                'status'       => $ap->status,
                'finding_status' => $ap->finding->status,
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
}
