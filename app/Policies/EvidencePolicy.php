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
            return $evidence->finding->audit->auditor_id === $user->id;
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

        // Only the assigned auditor can verify
        return $evidence->finding->audit->auditor_id === $user->id;
    }

    public function delete(User $user, Evidence $evidence): bool
    {
        // Auditee can delete only their own pending evidence
        if ($user->isAuditee()) {
            return $evidence->uploaded_by === $user->id
                && $evidence->verification_status === 'PENDING';
        }

        return $user->isAdmin();
    }
}
