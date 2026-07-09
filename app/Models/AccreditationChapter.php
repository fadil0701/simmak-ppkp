<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccreditationChapter extends BaseModel
{
    protected $fillable = [
        'accreditation_type_id', 'chapter_number', 'title', 'description',
        'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(AccreditationType::class, 'accreditation_type_id');
    }

    public function standards(): HasMany
    {
        return $this->hasMany(AccreditationStandard::class, 'chapter_id')->orderBy('sort_order');
    }
}
