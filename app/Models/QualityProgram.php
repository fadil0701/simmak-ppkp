<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QualityProgram extends BaseModel
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'clinic_id', 'fiscal_year_id', 'program_code', 'name',
        'objective', 'status',
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function indicators(): HasMany
    {
        return $this->hasMany(QualityIndicator::class);
    }
}
