<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tabel pivot `response_evidence` tidak punya soft delete/organization_id/metadata,
 * jadi tidak memakai BaseModel.
 */
class ResponseEvidence extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

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
