<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends BaseModel
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'category_id', 'code', 'name',
        'review_cycle_days', 'requires_acknowledgment', 'is_active', 'metadata',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'requires_acknowledgment' => 'boolean',
            'is_active' => 'boolean',
        ]);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'category_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
