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

        $validated = $request->validate([
            'action_plan' => 'required|string',
            'response'    => 'required|string',
            'pic'         => 'required|string|max:255',
            'deadline'    => 'required|date',
        ]);

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

        return back()->with('success', 'Rencana tindak lanjut berhasil disimpan.');
    }

    public function update(Request $request, Finding $finding): RedirectResponse
    {
        return $this->store($request, $finding);
    }
}
