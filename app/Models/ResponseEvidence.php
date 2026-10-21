<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResponseEvidence extends BaseModel
{
    protected $fillable = [
        'checklist_response_id', 'evidence_id', 'caption',
    ];

    public function response(): BelongsTo
    {
        return $this->belongsTo(ChecklistResponse::class, 'checklist_response_id');
    }

    public function evidence(): BelongsTo
    {
        return $this->belongsTo(Evidence::class);
    }
}
