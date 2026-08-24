<?php

namespace App\Policies;

use App\Models\Finding;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FindingPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['finding.view-all', 'finding.view-assigned']);
    }

    public function view(User $user, Finding $finding): bool
    {
        if ($user->hasPermissionTo('finding.view-all')) {
            return true;
        }

        if ($user->isAuditor()) {
            return $finding->audit->auditor_id === $user->id;
        }

        if ($user->isAuditee()) {
            return $user->stores()->where('stores.id', $finding->audit->store_id)->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('finding.create');
    }

    public function update(User $user, Finding $finding): bool
    {
        if ($user->hasPermissionTo('finding.edit') && $user->isAuditor()) {
            return $finding->audit->auditor_id === $user->id;
        }

        // Admin can always edit
        return $user->hasPermissionTo('finding.view-all');
    }

    public function delete(User $user, Finding $finding): bool
    {
        return $user->hasPermissionTo('finding.delete');
    }

    public function verify(User $user, Finding $finding): bool
    {
        if (! $user->hasPermissionTo('finding.verify')) {
            return false;
        }

        return $finding->audit->auditor_id === $user->id;
    }

    public function close(User $user, Finding $finding): bool
    {
        if (! $user->hasPermissionTo('finding.close')) {
            return false;
        }

        return $finding->audit->auditor_id === $user->id && $finding->isCloseable();
    }
}
