<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'is_active',
        'approval_status',
        'requested_role',
        'rejection_reason',
    ];

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    public function isPendingApproval(): bool
    {
        return $this->approval_status === 'pending';
    }

    public function isRejected(): bool
    {
        return $this->approval_status === 'rejected';
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    // Relationships
    public function audits(): HasMany
    {
        return $this->hasMany(Audit::class, 'auditor_id');
    }

    public function assignedAudits(): BelongsToMany
    {
        return $this->belongsToMany(Audit::class, 'audit_auditor');
    }

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'store_user');
    }

    public function uploadedEvidences(): HasMany
    {
        return $this->hasMany(Evidence::class, 'uploaded_by');
    }

    public function verifiedEvidences(): HasMany
    {
        return $this->hasMany(Evidence::class, 'verified_by');
    }

    // Helpers
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isChief(): bool
    {
        return $this->hasRole('chief');
    }

    public function isAsmen(): bool
    {
        return $this->hasRole('asmen');
    }

    public function isCoordinator(): bool
    {
        return $this->hasRole('coordinator');
    }

    public function isAuditor(): bool
    {
        return $this->hasRole('auditor');
    }

    public function isAuditee(): bool
    {
        return $this->hasRole('auditee');
    }

    public function getRedirectRoute(): string
    {
        if ($this->isAdmin()) {
            return '/admin/dashboard';
        }
        if ($this->isChief() || $this->isAsmen() || $this->isCoordinator()) {
            return '/coordinator/dashboard';
        }
        if ($this->isAuditor()) {
            return '/auditor/dashboard';
        }

        return '/coordinator/dashboard';
    }
}
