<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends BaseModel
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'clinic_id',
        'unit_id',
        'position_id',
        'employee_number',
        'full_name',
        'email',
        'phone',
        'position',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'is_active' => 'boolean',
        ]);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Master jabatan pegawai.
     */
    public function positionMaster(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    /**
     * Akun pengguna yang dibuat dari data pegawai ini.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'employee_id');
    }
}
