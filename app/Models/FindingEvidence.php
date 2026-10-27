<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tabel pivot `finding_evidence` hanya punya id/finding_id/evidence_id/
 * relationship_note + timestamps — tanpa soft delete, organization_id, metadata.
 * Karena itu model ini TIDAK memakai BaseModel.
 */
class FindingEvidence extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'finding_id', 'evidence_id', 'relationship_note',
    ];

    public function finding(): BelongsTo
    {
        return $this->belongsTo(AuditFinding::class, 'finding_id');
    }

    public function evidence(): BelongsTo
    {
        return $this->belongsTo(Evidence::class);
    }
}
