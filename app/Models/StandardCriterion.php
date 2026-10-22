<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StandardCriterion extends BaseModel
{
    protected $fillable = [
        'standard_section_id',
        'code',
        'title',
        'requirement_text',
        'assessment_guidance',
        'evidence_guidance',
        'assessment_type',
        'scoring_rule',
        'max_score',
        'source_reference',
        'sort_order',
        'active',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'active' => 'boolean',
            'max_score' => 'integer',
        ]);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(StandardSection::class, 'standard_section_id');
    }
}
