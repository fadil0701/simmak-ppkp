<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use App\Models\Concerns\BelongsToOrganization;

class RiskMatrix extends BaseModel
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'name', 'likelihood_labels', 'impact_labels',
        'level_thresholds', 'is_default', 'is_active',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'likelihood_labels' => 'array',
            'impact_labels' => 'array',
            'level_thresholds' => 'array',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);
    }
}
