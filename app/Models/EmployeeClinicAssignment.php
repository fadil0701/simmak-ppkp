<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Penugasan klinik/unit kerja pegawai (pivot `employee_clinic_assignments`).
 *
 * UUID digenerate oleh model agar relasi belongsToMany dapat memakai
 * `attach()`/`sync()` tanpa kolom id berisi null.
 */
class EmployeeClinicAssignment extends Pivot
{
    use HasUuids;

    protected $table = 'employee_clinic_assignments';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'organization_id',
        'employee_id',
        'clinic_id',
        'unit_id',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
