<?php

namespace App\Policies;

use App\Models\Audit;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AuditPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['audit.view-all', 'audit.view-assigned']);
    }

    public function view(User $user, Audit $audit): bool
    {
        if ($user->hasPermissionTo('audit.view-all')) {
            return true;
        }

        if ($user->hasPermissionTo('audit.view-assigned') && $user->isAuditor()) {
            return $audit->auditor_id === $user->id || $audit->auditors()->where('users.id', $user->id)->exists();
        }

        if ($user->isAuditee()) {
            return $user->stores()->where('stores.id', $audit->store_id)->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('audit.create');
    }

    public function update(User $user, Audit $audit): bool
    {
        return $user->hasPermissionTo('audit.edit');
    }

    public function delete(User $user, Audit $audit): bool
    {
        return $user->hasPermissionTo('audit.delete');
    }

    public function changeStatus(User $user, Audit $audit): bool
    {
        return $user->hasPermissionTo('audit.change-status');
    }
}
