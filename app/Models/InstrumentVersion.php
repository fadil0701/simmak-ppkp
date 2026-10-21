<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstrumentVersion extends BaseModel
{
    protected $fillable = [
        'audit_standard_id',
        'version_label',
        'status',
        'effective_from',
        'effective_until',
        'source_snapshot',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'effective_from' => 'date',
            'effective_until' => 'date',
            'approved_at' => 'datetime',
        ]);
    }

    public function standard(): BelongsTo
    {
        return $this->belongsTo(AuditStandard::class, 'audit_standard_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(StandardSection::class);
    }

    public function executions(): HasMany
    {
        return $this->hasMany(AuditExecution::class, 'instrument_version_id');
    }

    /**
     * Versi dikunci jika sudah dipakai oleh eksekusi audit — isi kriteria/seksi
     * tidak boleh diubah retroaktif. Perubahan wajib dibuat sebagai versi baru.
     */
    public function isLocked(): bool
    {
        return $this->executions()->exists();
    }

    /**
     * Jumlah kriteria aktif pada versi ini (seluruh seksi).
     */
    public function criteriaCount(): int
    {
        return $this->sections()
            ->with('criteria')
            ->get()
            ->flatMap->criteria
            ->where('active', true)
            ->count();
    }
}
