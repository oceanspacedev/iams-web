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
    // Overdue = deadline < hari ini, belum COMPLETED, temuan bukan CONTINUOUS dan belum VERIFIED/CLOSED.
    // Harus sama dengan Finding::scopeOverdue().
    public function scopeOverdue($query)
    {
        return $query->where('deadline', '<', now()->toDateString())
                     ->where('status', '!=', 'COMPLETED')
                     ->whereHas('finding', fn ($q) => $q
                         ->where('severity', '!=', Finding::SEVERITY_CONTINUOUS)
                         ->whereNotIn('status', [Finding::STATUS_VERIFIED, Finding::STATUS_CLOSED]));
    }

    public function isOverdue(): bool
    {
        if (! $this->deadline || $this->status === 'COMPLETED') {
            return false;
        }

        $finding = $this->finding;
        if ($finding && (
            $finding->severity === Finding::SEVERITY_CONTINUOUS
            || in_array($finding->status, [Finding::STATUS_VERIFIED, Finding::STATUS_CLOSED], true)
        )) {
            return false;
        }

        return $this->deadline->toDateString() < now()->toDateString();
    }
}
