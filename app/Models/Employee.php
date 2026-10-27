<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    /**
     * Seluruh penugasan klinik/unit kerja pegawai (termasuk klinik utama).
     */
    public function clinicAssignments(): HasMany
    {
        return $this->hasMany(EmployeeClinicAssignment::class);
    }

    /**
     * Klinik/unit kerja yang dipegang pegawai.
     */
    public function clinics(): BelongsToMany
    {
        return $this->belongsToMany(Clinic::class, 'employee_clinic_assignments')
            ->using(EmployeeClinicAssignment::class)
            ->withPivot(['unit_id', 'is_primary'])
            ->withTimestamps();
    }

    /**
     * Penugasan pada klinik utama.
     */
    public function primaryAssignment(): HasOne
    {
        return $this->hasOne(EmployeeClinicAssignment::class)->where('is_primary', true);
    }

    /**
     * Klinik utama pegawai; jatuh ke relasi lama `clinic()` bila pivot belum terisi.
     */
    public function primaryClinicId(): ?string
    {
        return $this->primaryAssignment?->clinic_id ?? $this->clinic_id;
    }

    /**
     * ID seluruh klinik yang dipegang pegawai (fallback: klinik utama).
     *
     * @return array<int, string>
     */
    public function clinicScopeIds(): array
    {
        $ids = $this->relationLoaded('clinicAssignments')
            ? $this->clinicAssignments->pluck('clinic_id')->all()
            : $this->clinicAssignments()->pluck('clinic_id')->all();

        if ($ids === [] && $this->clinic_id !== null) {
            $ids = [$this->clinic_id];
        }

        return array_values(array_unique($ids));
    }

    /**
     * Selaraskan penugasan klinik/unit pegawai.
     *
     * Klinik utama disimpan ganda: pada pivot (`is_primary`) dan pada kolom lama
     * `clinic_id`/`unit_id` agar query dan tampilan lama tetap berjalan.
     *
     * @param  array<int, string>  $clinicIds
     */
    public function syncClinicAssignments(array $clinicIds, ?string $primaryClinicId, ?string $primaryUnitId = null): void
    {
        $clinicIds = array_values(array_unique(array_filter($clinicIds)));

        if ($clinicIds === [] && $primaryClinicId !== null) {
            $clinicIds = [$primaryClinicId];
        }

        if ($primaryClinicId === null || ! in_array($primaryClinicId, $clinicIds, true)) {
            $primaryClinicId = $clinicIds[0] ?? null;
        }

        $this->forceFill([
            'clinic_id' => $primaryClinicId,
            'unit_id' => $primaryClinicId !== null ? $primaryUnitId : null,
        ])->save();

        $sync = [];

        foreach ($clinicIds as $clinicId) {
            $isPrimary = $clinicId === $primaryClinicId;

            $sync[$clinicId] = [
                'organization_id' => $this->organization_id,
                'is_primary' => $isPrimary,
                'unit_id' => $isPrimary ? $primaryUnitId : null,
            ];
        }

        // sync() juga menghapus penugasan klinik yang tidak lagi dipilih.
        $this->clinics()->sync($sync);
        $this->unsetRelation('clinicAssignments');
        $this->unsetRelation('primaryAssignment');
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
