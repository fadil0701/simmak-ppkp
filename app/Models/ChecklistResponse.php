<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChecklistResponse extends BaseModel
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'audit_execution_id', 'standard_criterion_id', 'response_value',
        'score', 'max_score', 'facts_analysis', 'recommendation',
        'assessment_notes', 'method_used', 'sample_description', 'not_applicable_reason',
        'not_assessable_reason', 'assessed_by', 'assessed_at', 'metadata', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'assessed_at' => 'datetime',
            'score' => 'integer',
            'max_score' => 'integer',
            'metadata' => 'array',
        ]);
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

    public function evidence(): HasMany
    {
        return $this->hasMany(ResponseEvidence::class, 'checklist_response_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ChecklistResponseRevision::class, 'checklist_response_id')
            ->orderByDesc('created_at');
    }
}
