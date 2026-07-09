<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccreditationStandard extends BaseModel
{
    protected $fillable = [
        'chapter_id', 'standard_code', 'title', 'description', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(AccreditationChapter::class, 'chapter_id');
    }
}
