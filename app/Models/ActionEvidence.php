<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActionEvidence extends BaseModel
{
    protected $fillable = [
        'corrective_action_id', 'evidence_id', 'description',
    ];

    public function action(): BelongsTo
    {
        return $this->belongsTo(CapaAction::class, 'corrective_action_id');
    }

    public function evidence(): BelongsTo
    {
        return $this->belongsTo(Evidence::class);
    }
}
