<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\ClinicScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditAssignment extends BaseModel
{
    use BelongsToOrganization;
    use ClinicScope;

    protected $fillable = [
        'organization_id', 'audit_program_id', 'clinic_id', 'instrument_version_id', 'assignment_code', 'title',
        'scope_notes', 'lead_auditor_id', 'scheduled_start', 'scheduled_end', 'status',
        'conflict_check_status', 'submitted_at', 'activated_at', 'metadata', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'scheduled_start' => 'date',
            'scheduled_end' => 'date',
            'submitted_at' => 'datetime',
            'activated_at' => 'datetime',
            'metadata' => 'array',
        ]);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(AuditProgram::class, 'audit_program_id');
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function leadAuditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lead_auditor_id');
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(AuditTeamMember::class);
    }

    public function executions(): HasMany
    {
        return $this->hasMany(AuditExecution::class);
    }

    /**
     * Instrumen yang direncanakan untuk penugasan ini (opsional, data lama kosong).
     */
    public function instrumentVersion(): BelongsTo
    {
        return $this->belongsTo(InstrumentVersion::class);
    }

    /**
     * Judul standar instrumen: dari penugasan, atau turunan dari eksekusi pertama.
     */
    public function instrumentTitle(): ?string
    {
        $version = $this->instrumentVersion
            ?? $this->executions->first()?->instrumentVersion;

        $standard = $version?->standard;

        if ($standard === null) {
            return null;
        }

        $label = $version->version_label ?? null;

        return $label ? $standard->title.' ('.$label.')' : $standard->title;
    }
}
