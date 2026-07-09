<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QualityIndicator extends BaseModel
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'quality_program_id', 'indicator_code', 'name', 'unit',
        'target_value', 'direction', 'is_active',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'target_value' => 'decimal:2',
            'is_active' => 'boolean',
        ]);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(QualityProgram::class, 'quality_program_id');
    }

    public function measurements(): HasMany
    {
        return $this->hasMany(QualityMeasurement::class)->orderByDesc('measurement_date');
    }
}
