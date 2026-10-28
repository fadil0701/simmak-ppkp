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
            'source_snapshot' => 'array',
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
     * True jika versi ini sudah terhubung ke eksekusi audit.
     *
     * Informasi jejak pemakaian saja: perbaikan isi seksi/EP tetap diizinkan
     * pada versi yang sama (tanpa wajib membuat versi baru).
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
