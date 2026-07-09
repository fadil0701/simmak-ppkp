<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AccreditationProgram extends BaseModel
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'clinic_id', 'accreditation_type_id', 'program_name',
        'cycle', 'start_date', 'target_survey_date', 'status', 'metadata', 'created_by',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'start_date' => 'date',
            'target_survey_date' => 'date',
        ]);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(AccreditationType::class, 'accreditation_type_id');
    }

    public function selfAssessments(): HasMany
    {
        return $this->hasMany(AccreditationSelfAssessment::class, 'program_id');
    }

    public function latestSelfAssessment(): HasOne
    {
        return $this->hasOne(AccreditationSelfAssessment::class, 'program_id')->latestOfMany();
    }

    public function gapItems(): HasMany
    {
        return $this->hasMany(AccreditationGapItem::class, 'program_id');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(AccreditationEvidence::class, 'program_id');
    }

    public function mockSurveys(): HasMany
    {
        return $this->hasMany(AccreditationMockSurvey::class, 'program_id');
    }

    public function latestMockSurvey(): HasOne
    {
        return $this->hasOne(AccreditationMockSurvey::class, 'program_id')->latestOfMany();
    }
}
