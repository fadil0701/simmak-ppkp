<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalEvent extends BaseModel
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'entity_type', 'entity_id', 'step_key', 'actor_user_id',
        'decision', 'reason', 'metadata', 'acted_at',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'acted_at' => 'datetime',
            'metadata' => 'array',
        ]);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
