<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StandardSection extends BaseModel
{
    protected $fillable = [
        'instrument_version_id',
        'parent_id',
        'code',
        'title',
        'description',
        'sort_order',
    ];

    public function instrumentVersion(): BelongsTo
    {
        return $this->belongsTo(InstrumentVersion::class, 'instrument_version_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(StandardSection::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(StandardSection::class, 'parent_id');
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(StandardCriterion::class);
    }
}
