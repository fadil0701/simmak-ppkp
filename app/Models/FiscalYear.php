<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FiscalYear extends BaseModel
{
    use BelongsToOrganization;

    public function auditPrograms(): HasMany
    {
        return $this->hasMany(AuditProgram::class, 'fiscal_year_id');
    }

    protected $fillable = [
        'organization_id', 'code', 'name', 'start_date', 'end_date', 'is_current', 'is_active',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_current' => 'boolean',
            'is_active' => 'boolean',
        ]);
    }
}
