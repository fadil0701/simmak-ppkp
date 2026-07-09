<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AuditFinding extends BaseModel
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'audit_schedule_id', 'clinic_id', 'finding_code', 'title',
        'severity', 'description', 'requirement_reference', 'status', 'assigned_to',
        'identified_at', 'metadata', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'identified_at' => 'datetime',
        ]);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(AuditSchedule::class, 'audit_schedule_id');
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function capaCase(): HasOne
    {
        return $this->hasOne(CapaCase::class, 'source_id')
            ->where('source_type', 'audit_finding');
    }
}
