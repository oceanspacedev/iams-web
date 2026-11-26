<?php

namespace App\Policies;

use App\Models\Evidence;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EvidencePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('evidence.view');
    }

    public function view(User $user, Evidence $evidence): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isAuditor()) {
            return $this->isAuditTeamMember($user, $evidence);
        }

        if ($user->isAuditee()) {
            return $user->stores()->where('stores.id', $evidence->finding->audit->store_id)->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('evidence.upload');
    }

    public function verify(User $user, Evidence $evidence): bool
    {
        if (! $user->hasPermissionTo('evidence.verify')) {
            return false;
        }

        // Lead auditor or any member of the audit team can verify
        return $this->isAuditTeamMember($user, $evidence);
    }

    public function delete(User $user, Evidence $evidence): bool
    {
        // Auditee can delete their own pending or rejected evidence, never on a closed finding
        if ($user->isAuditee()) {
            return $evidence->uploaded_by === $user->id
                && in_array($evidence->verification_status, ['PENDING', 'REJECTED'], true)
                && $evidence->finding
                && ! $evidence->finding->isClosed();
        }

        return $user->isAdmin();
    }

    private function isAuditTeamMember(User $user, Evidence $evidence): bool
    {
        $audit = $evidence->finding?->audit;
        if (! $audit) {
            return false;
        }

        return (int) $audit->auditor_id === (int) $user->id
            ||$audit->auditors()->where('users.id', $user->id)->exists();
    }
}
