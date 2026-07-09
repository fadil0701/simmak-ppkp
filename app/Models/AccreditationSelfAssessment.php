<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccreditationSelfAssessment extends BaseModel
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'program_id', 'clinic_id', 'assessment_date',
        'assessor_id', 'overall_score', 'status', 'summary', 'created_by',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'assessment_date' => 'date',
            'overall_score' => 'decimal:2',
        ]);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(AccreditationProgram::class, 'program_id');
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessor_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AccreditationSelfAssessmentItem::class, 'self_assessment_id');
    }
}
