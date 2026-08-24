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
        'deadline' => 'date',
        'status'   => 'string',
    ];

    // Relationships
    public function finding(): BelongsTo
    {
        return $this->belongsTo(Finding::class);
    }

    // Scopes
    public function scopeOverdue($query)
    {
        return $query->where('deadline', '<', now()->toDateString())
                     ->where('status', '!=', 'COMPLETED');
    }

    public function isOverdue(): bool
    {
        return $this->deadline && $this->deadline->isPast() && $this->status !== 'COMPLETED';
    }
}
