<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Audit extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'audit_number',
        'title',
        'store_id',
        'category_id',
        'location',
        'auditor_id',
        'audit_date',
        'audit_time',
        'status',
        'notes',
    ];

    protected $casts = [
        'audit_date' => 'date',
        'status'     => 'string',
    ];

    // Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(AuditCategory::class, 'category_id')->withTrashed();
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function auditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auditor_id');
    }

    public function auditors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'audit_auditor');
    }

    public function findings(): HasMany
    {
        return $this->hasMany(Finding::class);
    }

    public function qualityFindings(): HasMany
    {
        return $this->hasMany(QualityFinding::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(AuditDocument::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(AuditNotification::class);
    }

    // Scopes
    public function scopeForAuditor($query, int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('auditor_id', $userId)
              ->orWhereHas('auditors', fn ($sub) => $sub->where('users.id', $userId));
        });
    }

    public function scopeForStore($query, int $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['PLANNED', 'IN_PROGRESS']);
    }

    // Auto-generate audit number
    public static function generateNumber(): string
    {
        $prefix = 'AUD';
        $year   = now()->format('Y');
        $month  = now()->format('m');
        $count  = static::whereYear('created_at', $year)->whereMonth('created_at', $month)->count() + 1;

        return sprintf('%s/%s/%s/%04d', $prefix, $year, $month, $count);
    }

    /**
     * Automatically calculate and synchronize notification schedules based on NotificationRules.
     */
    public function syncNotificationSchedules(): void
    {
        $rules = NotificationRule::all();

        foreach ($rules as $rule) {
            $sendTime = $rule->send_time ?: '08:00';
            $dateStr = $this->audit_date->format('Y-m-d');
            $scheduledAt = Carbon::parse("{$dateStr} {$sendTime}")->subDays($rule->days_before);

            $notification = $this->notifications()->firstOrNew([
                'notification_rule_id' => $rule->id,
            ]);

            // Don't modify already sent notifications
            if ($notification->status === 'SENT') {
                continue;
            }

            $notification->scheduled_at = $scheduledAt;
            $notification->channel      = $rule->channel ?: 'whatsapp';
            $notification->status       = $rule->is_active ? 'PENDING' : 'INACTIVE';
            $notification->save();
        }
    }
}
