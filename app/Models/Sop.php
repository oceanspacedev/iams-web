<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Sop extends Model
{
    use SoftDeletes;

    protected $table = 'sops';

    protected $fillable = [
        'code',
        'title',
        'description',
        'document',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = ['document_url'];

    // Relationships
    public function findings(): HasMany
    {
        return $this->hasMany(Finding::class, 'sop_id');
    }

    // Accessors
    public function getDocumentUrlAttribute(): ?string
    {
        if (!$this->document) {
            return null;
        }

        if (str_starts_with($this->document, 'http://') || str_starts_with($this->document, 'https://')) {
            return $this->document;
        }

        $disk = config('filesystems.default');

        if ($disk === 's3' || Storage::disk('s3')->exists($this->document)) {
            try {
                return Storage::disk('s3')->temporaryUrl($this->document, now()->addDays(7));
            } catch (\Throwable $e) {
                return Storage::disk('s3')->url($this->document);
            }
        }

        if (Storage::disk('public')->exists($this->document)) {
            return Storage::disk('public')->url($this->document);
        }

        return Storage::disk($disk)->url($this->document);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
