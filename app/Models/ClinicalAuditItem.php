<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Elemen penilaian audit klinis (indikator/kriteria + hasil per elemen).
 * Memakai skala kesesuaian 0/5/10 seperti baris checklist audit internal.
 */
class ClinicalAuditItem extends BaseModel
{
    public const RESULTS = ['conform', 'partial', 'non_conform', 'na'];

    public const SCORES = [0, 5, 10];

    protected $fillable = [
        'organization_id', 'clinical_audit_id', 'code', 'element', 'criteria', 'indicator',
        'objective_evidence', 'result', 'score', 'finding_note', 'sort_order', 'metadata',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'score' => 'integer',
            'sort_order' => 'integer',
        ]);
    }

    public function audit(): BelongsTo
    {
        return $this->belongsTo(ClinicalAudit::class, 'clinical_audit_id');
    }
}
