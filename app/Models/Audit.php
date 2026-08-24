<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Audit extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'audit_number',
        'store_id',
        'auditor_id',
        'audit_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'audit_date' => 'date',
        'status'     => 'string',
    ];

    // Relationships
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function auditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auditor_id');
    }

    public function findings(): HasMany
    {
        return $this->hasMany(Finding::class);
    }

    // Scopes
    public function scopeForAuditor($query, int $userId)
    {
        return $query->where('auditor_id', $userId);
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
}
