<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskMitigationPlan extends BaseModel
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'risk_item_id', 'plan_description', 'status',
        'target_date', 'owner_id',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'target_date' => 'date',
        ]);
    }

    public function riskItem(): BelongsTo
    {
        return $this->belongsTo(RiskItem::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
