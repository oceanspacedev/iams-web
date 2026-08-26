<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationRule extends Model
{
    protected $fillable = [
        'name',
        'days_before',
        'send_time',
        'channel',
        'recipient_type',
        'is_active',
    ];

    protected $casts = [
        'days_before' => 'integer',
        'is_active'   => 'boolean',
    ];

    public function auditNotifications(): HasMany
    {
        return $this->hasMany(AuditNotification::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
