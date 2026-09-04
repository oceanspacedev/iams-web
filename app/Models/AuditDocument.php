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
        if (str_starts_with($this->file_path, 'http://') || str_starts_with($this->file_path, 'https://')) {
            return $this->file_path;
        }

        $disk = config('filesystems.default');

        if ($disk === 's3' || Storage::disk('s3')->exists($this->file_path)) {
            try {
                return Storage::disk('s3')->temporaryUrl($this->file_path, now()->addDays(7));
            } catch (\Throwable $e) {
                return Storage::disk('s3')->url($this->file_path);
            }
        }

        if (Storage::disk('public')->exists($this->file_path)) {
            return Storage::disk('public')->url($this->file_path);
        }

        return Storage::disk($disk)->url($this->file_path);
    }
}
