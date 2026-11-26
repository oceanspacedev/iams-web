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
        'surplus_amount',
        'auditor_opinion',
        'recommendation',
        'severity',
        'severity_status',
        'severity_reviewed_by',
        'severity_reviewed_at',
        'severity_notes',
        'is_severity_locked',
        'status',
        'closed_at',
    ];

    protected $casts = [
        'closed_at'            => 'datetime',
        'loss_amount'          => 'decimal:2',
        'surplus_amount'       => 'decimal:2',
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

    const SEVERITY_MINOR      = 'MINOR';
    const SEVERITY_MEDIUM     = 'MEDIUM';
    const SEVERITY_MAJOR      = 'MAJOR';
    const SEVERITY_CONTINUOUS = 'CONTINUOUS';

    // Legacy fallback
    const SEVERITY_CRITICAL    = 'CRITICAL';
    const SEVERITY_OBSERVATION = 'OBSERVATION';

    protected static function booted(): void
    {
        // closed_at selalu mengikuti status: diisi saat menjadi CLOSED, dikosongkan saat keluar dari CLOSED.
        static::saving(function (Finding $finding) {
            if (! $finding->isDirty('status')) {
                return;
            }

            if ($finding->status === self::STATUS_CLOSED) {
                $finding->closed_at ??= now();
            } elseif ($finding->closed_at !== null) {
                $finding->closed_at = null;
            }
        });
    }

    public static function severityOptions(): array
    {
        return [
            self::SEVERITY_MINOR  => [
                'id'       => self::SEVERITY_MINOR,
                'label'    => 'Minor',
                'timeline' => '3 - 7 hari',
                'badge'    => 'bg-sky-100 text-sky-800 border-sky-200',
            ],
            self::SEVERITY_MEDIUM => [
                'id'       => self::SEVERITY_MEDIUM,
                'label'    => 'Medium',
                'timeline' => '8 - 14 hari',
                'badge'    => 'bg-amber-100 text-amber-800 border-amber-300',
            ],
            self::SEVERITY_MAJOR  => [
                'id'       => self::SEVERITY_MAJOR,
                'label'    => 'Major',
                'timeline' => '15 - 30 hari',
                'badge'    => 'bg-rose-100 text-rose-800 border-rose-300',
            ],
            self::SEVERITY_CONTINUOUS => [
                'id'       => self::SEVERITY_CONTINUOUS,
                'label'    => 'Continuous',
                'timeline' => 'Dalam Monitoring',
                'badge'    => 'bg-purple-100 text-purple-800 border-purple-300',
            ],
        ];
    }

    // Relationships
    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class)->withTrashed();
    }

    public function severityReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'severity_reviewed_by');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AuditCategory::class, 'category_id')->withTrashed();
    }

    public function sop(): BelongsTo
    {
        return $this->belongsTo(Sop::class, 'sop_id')->withTrashed();
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

    public function qualityReport(): HasOne
    {
        return $this->hasOne(QualityFinding::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(AuditDocument::class);
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
        return $query->where('severity', '!=', self::SEVERITY_CONTINUOUS)
            ->whereHas('actionPlan', function ($q) {
                $q->where('deadline', '<', now()->toDateString())
                  ->where('status', '!=', 'COMPLETED');
            })->whereNotIn('status', [self::STATUS_VERIFIED, self::STATUS_CLOSED]);
    }

    // Tanggal penutupan: closed_at, fallback ke updated_at untuk baris yang ditutup di luar Eloquent.
    private const CLOSED_DATE_SQL = 'DATE(COALESCE(findings.closed_at, findings.updated_at))';

    public function scopeClosedOnTime($query)
    {
        return $query->where('status', self::STATUS_CLOSED)
            ->where(function ($q) {
                $q->where('severity', self::SEVERITY_CONTINUOUS)
                  ->orWhereDoesntHave('actionPlan')
                  ->orWhereHas('actionPlan', function ($ap) {
                      $ap->whereNull('deadline')
                         ->orWhereRaw(self::CLOSED_DATE_SQL . ' <= action_plans.deadline');
                  });
            });
    }

    public function scopeClosedOverdue($query)
    {
        return $query->where('status', self::STATUS_CLOSED)
            ->where('severity', '!=', self::SEVERITY_CONTINUOUS)
            ->whereHas('actionPlan', function ($ap) {
                $ap->whereNotNull('deadline')
                   ->whereRaw(self::CLOSED_DATE_SQL . ' > action_plans.deadline');
            });
    }

    // Helpers
    public function isContinuous(): bool
    {
        return $this->severity === self::SEVERITY_CONTINUOUS;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    /** Temuan hanya bisa ditutup setelah bukti diverifikasi. */
    public function isCloseable(): bool
    {
        return $this->status === self::STATUS_VERIFIED;
    }

    public function canUploadEvidence(): bool
    {
        return in_array($this->status, [
            self::STATUS_OPEN,
            self::STATUS_IN_PROGRESS,
            self::STATUS_WAITING_VERIFICATION,
        ], true);
    }

    /** Auditee hanya boleh mengisi/mengubah action plan sebelum bukti dikirim. */
    public function canEditActionPlan(): bool
    {
        return in_array($this->status, [self::STATUS_OPEN, self::STATUS_IN_PROGRESS], true);
    }

    /**
     * Hitung ulang status dari bukti yang tersisa (dipakai setelah bukti dihapus).
     * Tidak mengubah temuan yang sudah CLOSED.
     */
    public function recalculateStatusFromEvidence(): void
    {
        if ($this->isClosed()) {
            return;
        }

        $evidences = $this->evidences();

        if ((clone $evidences)->where('verification_status', 'PENDING')->exists()) {
            $status = self::STATUS_WAITING_VERIFICATION;
        } elseif ((clone $evidences)->where('verification_status', 'APPROVED')->exists()) {
            $status = self::STATUS_VERIFIED;
        } elseif (! empty($this->actionPlan?->action_plan)) {
            $status = self::STATUS_IN_PROGRESS;
        } else {
            $status = self::STATUS_OPEN;
        }

        $this->update(['status' => $status]);
    }

    /**
     * Tutup temuan: status CLOSED, action plan COMPLETED, dan audit IN_PROGRESS
     * menjadi COMPLETED bila semua temuannya sudah CLOSED.
     */
    public function markClosed(): void
    {
        $this->update(['status' => self::STATUS_CLOSED]);
        $this->actionPlan?->update(['status' => 'COMPLETED']);

        $audit = $this->audit;
        if ($audit && $audit->status === 'IN_PROGRESS'
            && ! $audit->findings()->where('status', '!=', self::STATUS_CLOSED)->exists()) {
            $audit->update(['status' => 'COMPLETED']);
        }
    }
}
