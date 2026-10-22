<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistResponseRevision extends BaseModel
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'checklist_response_id', 'audit_execution_id', 'standard_criterion_id',
        'response_value', 'score', 'facts_analysis', 'recommendation',
        'assessment_notes', 'method_used', 'sample_description',
        'not_applicable_reason', 'not_assessable_reason', 'assessed_by', 'assessed_at',
        'revision_reason', 'metadata', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'assessed_at' => 'datetime',
            'metadata' => 'array',
        ]);
    }

    public function response(): BelongsTo
    {
        return $this->belongsTo(ChecklistResponse::class, 'checklist_response_id');
    }

    public function execution(): BelongsTo
    {
        return $this->belongsTo(AuditExecution::class, 'audit_execution_id');
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(StandardCriterion::class, 'standard_criterion_id');
    }

    public function assessedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }
}
