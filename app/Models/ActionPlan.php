<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActionPlan extends Model
{
    protected $fillable = [
        'finding_id',
        'action_plan',
        'response',
        'pic',
        'deadline',
        'status',
    ];

    protected $casts = [
        'deadline' => 'date:Y-m-d',
        'status'   => 'string',
    ];

    protected $appends = [
        'formatted_deadline',
    ];

    public function getFormattedDeadlineAttribute(): ?string
    {
        return $this->deadline ? $this->deadline->format('d M Y') : null;
    }

    // Relationships
    public function finding(): BelongsTo
    {
        return $this->belongsTo(Finding::class);
    }

    // Scopes
    public function scopeOverdue($query)
    {
        return $query->where('deadline', '<', now()->toDateString())
                     ->where('status', '!=', 'COMPLETED')
                     ->whereHas('finding', fn ($q) => $q->where('severity', '!=', Finding::SEVERITY_CONTINUOUS));
    }

    public function isOverdue(): bool
    {
        if ($this->finding && $this->finding->severity === Finding::SEVERITY_CONTINUOUS) {
            return false;
        }
        return $this->deadline && $this->deadline->isPast() && $this->status !== 'COMPLETED';
    }
}
