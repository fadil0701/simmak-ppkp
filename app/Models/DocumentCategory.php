<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentCategory extends BaseModel
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'parent_id', 'code', 'name', 'doc_class',
        'retention_days', 'is_active', 'metadata', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'is_active' => 'boolean',
            'retention_days' => 'integer',
        ]);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'parent_id');
    }

    public function types(): HasMany
    {
        return $this->hasMany(DocumentType::class, 'category_id');
    }
}
