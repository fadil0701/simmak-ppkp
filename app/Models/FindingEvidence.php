<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FindingEvidence extends BaseModel
{
    protected $fillable = [
        'finding_id', 'evidence_id', 'relationship_note',
    ];

    public function finding(): BelongsTo
    {
        return $this->belongsTo(AuditFinding::class);
    }

    public function evidence(): BelongsTo
    {
        return $this->belongsTo(Evidence::class);
    }
}
