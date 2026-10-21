<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\ClinicScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditExecution extends BaseModel
{
    use BelongsToOrganization;
    use ClinicScope;

    protected $fillable = [
        'organization_id', 'audit_program_id', 'audit_assignment_id', 'clinic_id',
        'instrument_version_id', 'execution_code', 'title', 'status', 'started_at',
        'submitted_at', 'finalized_at', 'finalized_by', 'report_number', 'summary_snapshot',
        'metadata', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'finalized_at' => 'datetime',
            'summary_snapshot' => 'array',
            'metadata' => 'array',
        ]);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(AuditProgram::class, 'audit_program_id');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(AuditAssignment::class, 'audit_assignment_id');
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function instrumentVersion(): BelongsTo
    {
        return $this->belongsTo(InstrumentVersion::class, 'instrument_version_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(ChecklistResponse::class);
    }

    public function findings(): HasMany
    {
        return $this->hasMany(AuditFinding::class);
    }
}
