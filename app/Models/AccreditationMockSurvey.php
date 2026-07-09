<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccreditationMockSurvey extends BaseModel
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'program_id', 'clinic_id', 'survey_date',
        'score', 'surveyor_name', 'status', 'summary',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'survey_date' => 'date',
            'score' => 'decimal:2',
        ]);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(AccreditationProgram::class, 'program_id');
    }
}
