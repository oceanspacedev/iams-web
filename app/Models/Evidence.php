<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evidence extends Model
{
    protected $table = 'evidences';

    protected $fillable = [
        'finding_id',
        'uploaded_by',
        'file',
        'description',
        'verification_status',
        'verified_by',
        'verified_at',
        'rejection_reason',
    ];

    protected $casts = [
        'verified_at'         => 'datetime',
        'verification_status' => 'string',
    ];

    // Relationships
    public function finding(): BelongsTo
    {
        return $this->belongsTo(Finding::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // Helpers
    public function isPending(): bool
    {
        return $this->verification_status === 'PENDING';
    }

    public function isApproved(): bool
    {
        return $this->verification_status === 'APPROVED';
    }

    public function isRejected(): bool
    {
        return $this->verification_status === 'REJECTED';
    }

    public function getFileUrlAttribute(): string
    {
        return asset('storage/' . $this->file);
    }
}
