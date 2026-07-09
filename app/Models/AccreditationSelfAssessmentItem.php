<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccreditationSelfAssessmentItem extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'organization_id', 'self_assessment_id', 'standard_id',
        'score', 'compliance_status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
        ];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(AccreditationSelfAssessment::class, 'self_assessment_id');
    }

    public function standard(): BelongsTo
    {
        return $this->belongsTo(AccreditationStandard::class, 'standard_id');
    }
}
