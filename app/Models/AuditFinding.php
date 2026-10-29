<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\ClinicScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AuditFinding extends BaseModel
{
    use BelongsToOrganization;
    use ClinicScope;

    /** @var list<string> */
    public const OPEN_STATUSES = [
        'draft',
        'under_review',
        'returned_for_revision',
        'confirmed',
        'action_required',
        'pending_verification',
        'in_capa',
        'open',
    ];

    protected $fillable = [
        'organization_id', 'audit_schedule_id', 'audit_execution_id', 'clinical_audit_id', 'clinic_id', 'finding_code', 'title',
        'severity', 'is_escalated', 'escalated_at', 'escalated_by', 'escalation_reason',
        'finding_type', 'description', 'condition', 'requirement', 'objective_evidence_summary',
        'impact', 'recommendation', 'auditee_response', 'status', 'assigned_to', 'owner_user_id', 'due_at',
        'standard_criterion_id', 'identified_at', 'metadata', 'created_by', 'updated_by',
        'reviewed_by', 'reviewed_at', 'closed_at',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'identified_at' => 'datetime',
            'due_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'closed_at' => 'datetime',
            'escalated_at' => 'datetime',
            'is_escalated' => 'boolean',
        ]);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(AuditSchedule::class, 'audit_schedule_id');
    }

    public function execution(): BelongsTo
    {
        return $this->belongsTo(AuditExecution::class, 'audit_execution_id');
    }

    /**
     * Audit klinis asal temuan (null untuk temuan audit internal periodik).
     */
    public function clinicalAudit(): BelongsTo
    {
        return $this->belongsTo(ClinicalAudit::class, 'clinical_audit_id');
    }

    /**
     * Sumber audit temuan: klinis atau audit internal (program/eksekusi).
     */
    public function sourceLabel(): string
    {
        return $this->clinical_audit_id ? 'Audit Klinis' : 'Audit Internal';
    }

    /** Laporan yang dilampirkan langsung pada temuan ini. */
    public function reports(): HasMany
    {
        return $this->hasMany(AuditReport::class, 'audit_finding_id');
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(StandardCriterion::class, 'standard_criterion_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function escalatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_by');
    }

    public function isEscalated(): bool
    {
        return (bool) $this->is_escalated;
    }

    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }

    /**
     * Scope: temuan kritis yang belum ditutup dan perlu eskalasi/tindak lanjut segera.
     */
    public function scopeCriticalOpen($query)
    {
        return $query->where('severity', 'critical')
            ->where('status', '!=', 'closed')
            ->orderBy('escalated_at', 'desc')
            ->orderBy('due_at', 'asc');
    }

    public function capaCase(): HasOne
    {
        return $this->hasOne(CapaCase::class, 'source_id')
            ->where('source_type', 'audit_finding');
    }

    public function evidenceLinks(): HasMany
    {
        return $this->hasMany(FindingEvidence::class, 'finding_id');
    }
}
