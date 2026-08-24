<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FindingFollowUp extends Model
{
    protected $fillable = [
        'finding_id',
        'retail_status',
        'autoev_status',
        'depo_ho_tokomas_status',
        'csn_status',
        'team',
        'category',
        'tanggal_so',
        'tanggal_selesai',
        'status_penyelesaian',
        'tanggal_update',
    ];

    protected $casts = [
        'tanggal_so'      => 'date',
        'tanggal_selesai' => 'date',
        'tanggal_update'  => 'date',
    ];

    // Relationships
    public function finding(): BelongsTo
    {
        return $this->belongsTo(Finding::class);
    }
}
