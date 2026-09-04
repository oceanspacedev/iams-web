<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

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

    protected $appends = ['file_url'];

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
        if (str_starts_with($this->file, 'http://') || str_starts_with($this->file, 'https://')) {
            return $this->file;
        }

        $disk = config('filesystems.default');

        if ($disk === 's3' || Storage::disk('s3')->exists($this->file)) {
            try {
                return Storage::disk('s3')->temporaryUrl($this->file, now()->addDays(7));
            } catch (\Throwable $e) {
                return Storage::disk('s3')->url($this->file);
            }
        }

        if (Storage::disk('public')->exists($this->file)) {
            return Storage::disk('public')->url($this->file);
        }

        return Storage::disk($disk)->url($this->file);
    }
}
