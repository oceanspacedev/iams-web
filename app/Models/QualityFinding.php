<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class QualityFinding extends Model
{
    use SoftDeletes;

    protected $table = 'quality_findings';

    protected $fillable = [
        'finding_id',
        'audit_id',
        'quality_category',
        'title',
        'impact_amount',
        'root_cause',
        'systemic_issue',
        'recommendation',
        'auditor_notes',
        'reported_by',
        'status',
    ];

    protected $casts = [
        'impact_amount' => 'decimal:2',
    ];

    const CATEGORY_IMPACT_50M    = 'impact_50m';
    const CATEGORY_FRAUD_RISK    = 'fraud_risk';
    const CATEGORY_SYSTEM_CONTROL = 'system_control';
    const CATEGORY_ORG_STRUCTURE = 'org_structure';

    public static function categories(): array
    {
        return [
            self::CATEGORY_IMPACT_50M => [
                'id'          => self::CATEGORY_IMPACT_50M,
                'code'        => '01',
                'label'       => 'Impact > Rp 50 Juta',
                'description' => 'Temuan yang berdampak finansial atau potensi kerugian melebihi Rp 50.000.000,-',
            ],
            self::CATEGORY_FRAUD_RISK => [
                'id'          => self::CATEGORY_FRAUD_RISK,
                'code'        => '02',
                'label'       => 'Risiko Fraud / Manipulasi',
                'description' => 'Indikasi kecurangan, penggelapan, manipulasi data sistem, atau transaksi fiktif',
            ],
            self::CATEGORY_SYSTEM_CONTROL => [
                'id'          => self::CATEGORY_SYSTEM_CONTROL,
                'code'        => '03',
                'label'       => 'Masalah Sistem / Kontrol Besar',
                'description' => 'Kelemahan SOP fundamental, celah keamanan sistem IT/POS, atau breakdown kontrol internal',
            ],
            self::CATEGORY_ORG_STRUCTURE => [
                'id'          => self::CATEGORY_ORG_STRUCTURE,
                'code'        => '04',
                'label'       => 'Struktur Organisasi Bermasalah',
                'description' => 'Rangkap jabatan kritis (conflict of interest), ketiadaan supervisi, atau staffing bermasalah',
            ],
        ];
    }

    public function finding(): BelongsTo
    {
        return $this->belongsTo(Finding::class);
    }

    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
