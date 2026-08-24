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
    ];

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
        if ($this->isCoordinator()) {
            return '/coordinator/dashboard';
        }
        if ($this->isAuditor()) {
            return '/auditor/dashboard';
        }

        return '/auditee/dashboard';
    }
}
