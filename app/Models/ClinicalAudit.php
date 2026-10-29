<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\ClinicScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Audit klinis: penilaian kesesuaian pelayanan klinis terhadap standar/kriteria
 * yang dipakai organisasi. Temuan yang lahir dari audit klinis masuk ke modul
 * Temuan Audit bersama (audit_findings.clinical_audit_id).
 */
class ClinicalAudit extends BaseModel
{
    use BelongsToOrganization;
    use ClinicScope;

    public const OPEN_STATUSES = ['draft', 'in_progress'];

    protected $fillable = [
        'organization_id', 'clinic_id', 'audit_assignment_id', 'audit_number', 'title', 'audit_date',
        'period_start', 'period_end', 'auditor_id', 'pic_user_id', 'audit_standard_id',
        'standard_reference', 'scope', 'criteria', 'indicators', 'objective_evidence',
        'assessment_result', 'conformity_score', 'medical_record_ref', 'nonconformity_notes',
        'problem_analysis', 'notes', 'conclusion',
        'status', 'metadata', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'audit_date' => 'date',
            'period_start' => 'date',
            'period_end' => 'date',
            'conformity_score' => 'integer',
        ]);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(AuditAssignment::class, 'audit_assignment_id');
    }

    public function auditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auditor_id');
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }

    public function standard(): BelongsTo
    {
        return $this->belongsTo(AuditStandard::class, 'audit_standard_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ClinicalAuditItem::class)->orderBy('sort_order')->orderBy('created_at');
    }

    /** Temuan bersama (modul Temuan Audit) yang berasal dari audit klinis ini. */
    public function findings(): HasMany
    {
        return $this->hasMany(AuditFinding::class);
    }

    /** Dokumentasi pendukung audit klinis (tabel evidence yang sudah ada). */
    public function evidences(): HasMany
    {
        return $this->hasMany(Evidence::class)->orderByDesc('uploaded_at');
    }

    public function isLocked(): bool
    {
        return in_array($this->status, ['completed', 'closed'], true);
    }

    public function openFindingsCount(): int
    {
        return $this->findings()->whereIn('status', AuditFinding::OPEN_STATUSES)->count();
    }
}
