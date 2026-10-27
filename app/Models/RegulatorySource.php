<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegulatorySource extends BaseModel
{
    protected $fillable = [
        'document_number',
        'title',
        'issuer',
        'source_url',
        'published_at',
        'effective_from',
        'effective_until',
        'verification_status',
        'verified_at',
        'verified_by',
        'verification_notes',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'published_at' => 'date',
            'effective_from' => 'date',
            'effective_until' => 'date',
            'verified_at' => 'datetime',
        ]);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Standar audit yang memakai dokumen ini sebagai acuan.
     */
    public function standards(): HasMany
    {
        return $this->hasMany(AuditStandard::class, 'source_document_id');
    }
}
