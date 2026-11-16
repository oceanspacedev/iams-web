<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class AuditDocument extends Model
{
    protected $fillable = [
        'audit_id',
        'finding_id',
        'document_type',
        'title',
        'file_path',
        'file_name',
        'file_size',
        'notes',
        'uploaded_by',
    ];

    protected $appends = ['file_url'];

    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    public function finding(): BelongsTo
    {
        return $this->belongsTo(Finding::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getFileUrlAttribute(): string
    {
        if (empty($this->file_path)) {
            return '';
        }

        if (str_starts_with($this->file_path, 'http://') || str_starts_with($this->file_path, 'https://')) {
            return $this->file_path;
        }

        $disk = config('filesystems.default', 'local');

        // Check S3 only if disk is explicitly configured as s3 or safely handled
        if ($disk === 's3') {
            try {
                if (Storage::disk('s3')->exists($this->file_path)) {
                    return Storage::disk('s3')->temporaryUrl($this->file_path, now()->addDays(7));
                }
            } catch (\Throwable $e) {
                try {
                    return Storage::disk('s3')->url($this->file_path);
                } catch (\Throwable $e2) {}
            }
        }

        try {
            if (Storage::disk('public')->exists($this->file_path)) {
                return Storage::disk('public')->url($this->file_path);
            }
        } catch (\Throwable $e) {}

        try {
            return Storage::disk($disk)->url($this->file_path);
        } catch (\Throwable $e) {
            return Storage::url($this->file_path);
        }
    }
}
