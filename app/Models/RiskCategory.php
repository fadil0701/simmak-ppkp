<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RiskCategory extends BaseModel
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'code', 'name', 'is_active'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), ['is_active' => 'boolean']);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RiskItem::class);
    }
}
