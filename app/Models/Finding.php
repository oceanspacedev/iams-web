<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Finding extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'audit_id',
        'category_id',
        'sop_id',
        'finding',
        'loss_amount',
        'auditor_opinion',
        'recommendation',
        'severity',
        'severity_status',
        'severity_reviewed_by',
        'severity_reviewed_at',
        'severity_notes',
        'is_severity_locked',
        'status',
    ];

    protected $casts = [
        'loss_amount'          => 'decimal:2',
        'severity'             => 'string',
        'severity_status'      => 'string',
        'is_severity_locked'   => 'boolean',
        'severity_reviewed_at' => 'datetime',
        'status'               => 'string',
    ];

    // Workflow constants
    const STATUS_OPEN                 = 'OPEN';
    const STATUS_IN_PROGRESS          = 'IN_PROGRESS';
    const STATUS_WAITING_VERIFICATION = 'WAITING_VERIFICATION';
    const STATUS_VERIFIED             = 'VERIFIED';
    const STATUS_CLOSED               = 'CLOSED';

    const SEVERITY_CRITICAL    = 'CRITICAL';
    const SEVERITY_MAJOR       = 'MAJOR';
    const SEVERITY_MINOR       = 'MINOR';
    const SEVERITY_OBSERVATION = 'OBSERVATION';

    // Relationships
    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    public function severityReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'severity_reviewed_by');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AuditCategory::class, 'category_id');
    }

    public function sop(): BelongsTo
    {
        return $this->belongsTo(Sop::class, 'sop_id');
    }

    public function actionPlan(): HasOne
    {
        return $this->hasOne(ActionPlan::class);
    }

    public function followUp(): HasOne
    {
        return $this->hasOne(FindingFollowUp::class);
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(Evidence::class);
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeWaitingVerification($query)
    {
        return $query->where('status', self::STATUS_WAITING_VERIFICATION);
    }

    public function scopeOverdue($query)
    {
        return $query->whereHas('actionPlan', function ($q) {
            $q->where('deadline', '<', now()->toDateString())
              ->where('status', '!=', 'COMPLETED');
        })->whereNotIn('status', [self::STATUS_VERIFIED, self::STATUS_CLOSED]);
    }

    // Helpers
    public function isCloseable(): bool
    {
        return $this->status === self::STATUS_VERIFIED;
    }

    public function canUploadEvidence(): bool
    {
        return !in_array($this->status, [self::STATUS_VERIFIED, self::STATUS_CLOSED]);
    }
}
