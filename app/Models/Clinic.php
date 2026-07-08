<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Clinic extends BaseModel
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'code',
        'name',
        'clinic_type',
        'is_main_clinic',
        'accreditation_type',
        'accreditation_status',
        'city',
        'address',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'is_main_clinic' => 'boolean',
            'is_active' => 'boolean',
        ]);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
