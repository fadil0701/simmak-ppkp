<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccreditationType extends BaseModel
{
    protected $fillable = ['code', 'name', 'description', 'issuing_body', 'is_active'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), ['is_active' => 'boolean']);
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(AccreditationChapter::class)->orderBy('sort_order');
    }
}
