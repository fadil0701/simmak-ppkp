<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccreditationGapItem extends BaseModel
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'program_id', 'clinic_id', 'standard_id',
        'gap_description', 'severity', 'status', 'recommended_action',
        'target_date', 'created_by',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), ['target_date' => 'date']);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(AccreditationProgram::class, 'program_id');
    }

    public function standard(): BelongsTo
    {
        return $this->belongsTo(AccreditationStandard::class, 'standard_id');
    }
}
