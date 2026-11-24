<?php

namespace App\Http\Controllers\Auditee;

use App\Http\Controllers\Controller;
use App\Models\Finding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ActionPlanController extends Controller
{
    public function store(Request $request, Finding $finding): RedirectResponse
    {
        $this->authorize('view', $finding);

        if (! $finding->canEditActionPlan()) {
            return back()->with('error', 'Action plan tidak bisa diubah untuk temuan dengan status ini.');
        }

        $existing = $finding->actionPlan;

        // Setelah action plan diisi, deadline dikunci untuk auditee (staf masih bisa mengubahnya).
        $deadlineLocked = $existing && (! empty($existing->action_plan) || $existing->deadline !== null);

        $rules = [
            'action_plan' => 'required|string',
            'response'    => 'required|string',
            'pic'         => 'required|string|max:255',
        ];

        if (! $deadlineLocked) {
            $rules['deadline'] = ($finding->severity === Finding::SEVERITY_CONTINUOUS ? 'nullable' : 'required')
                . '|date|after_or_equal:today';
        }

        $validated = $request->validate($rules, [
            'deadline.after_or_equal' => 'Deadline tidak boleh sebelum hari ini.',
        ]);

        if ($deadlineLocked) {
            unset($validated['deadline']);
        }

        $finding->actionPlan()->updateOrCreate(
            ['finding_id' => $finding->id],
            [
                ...$validated,
                'status' => 'IN_PROGRESS',
            ]
        );

        if ($finding->status === Finding::STATUS_OPEN) {
            $finding->update(['status' => Finding::STATUS_IN_PROGRESS]);
        }

        return back()->with('success', 'Action plan disimpan.');
    }

    public function update(Request $request, Finding $finding): RedirectResponse
    {
        return $this->store($request, $finding);
    }
}
