<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditStandard extends BaseModel
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'source_document_id',
        'code',
        'title',
        'status',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'status' => 'string',
        ]);
    }

    public function sourceDocument(): BelongsTo
    {
        return $this->belongsTo(RegulatorySource::class, 'source_document_id');
    }

    public function instrumentVersions(): HasMany
    {
        return $this->hasMany(InstrumentVersion::class);
    }
}
