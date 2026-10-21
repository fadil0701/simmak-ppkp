<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\ClinicScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Clinic extends BaseModel
{
    use BelongsToOrganization;
    use ClinicScope;

    protected $fillable = [
        'organization_id',
        'code',
        'name',
        'clinic_type',
        'is_main_clinic',
        'is_auditable',
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
            'is_auditable' => 'boolean',
            'is_active' => 'boolean',
        ]);
    }

    /**
     * Unit kerja yang menjadi sasaran audit internal (klinik/Satpelkes).
     * Manajemen Pusat dikecualikan.
     */
    public function scopeAuditable(Builder $query): Builder
    {
        return $query->where($this->getTable().'.is_auditable', true);
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
