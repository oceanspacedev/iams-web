<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditNotification extends Model
{
    protected $fillable = [
        'audit_id',
        'notification_rule_id',
        'scheduled_at',
        'sent_at',
        'channel',
        'recipient',
        'status',
        'error_message',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at'      => 'datetime',
    ];

    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(NotificationRule::class, 'notification_rule_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }

    public function scopeDue($query)
    {
        return $query->where('status', 'PENDING')->where('scheduled_at', '<=', now());
    }
}
