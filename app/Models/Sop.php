<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    // Relationships
    public function findings(): HasMany
    {
        return $this->hasMany(Finding::class, 'sop_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
