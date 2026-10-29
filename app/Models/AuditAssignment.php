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

    /**
     * Status yang dianggap sudah disetujui Kepala Pusat sehingga tampil di
     * kalender dan daftar pengguna yang terlibat.
     *
     * @var list<string>
     */
    public const PUBLISHED_STATUSES = ['approved', 'active', 'completed'];

    /**
     * Status yang menunggu keputusan akhir Kepala Pusat.
     *
     * @var list<string>
     */
    public const PENDING_APPROVAL_STATUSES = ['proposed'];

    protected $fillable = [
        'organization_id', 'audit_program_id', 'clinic_id', 'instrument_version_id', 'assignment_code', 'title',
        'scope_notes', 'lead_auditor_id', 'scheduled_start', 'scheduled_end', 'status',
        'conflict_check_status', 'submitted_at', 'activated_at',
        'approved_by', 'approved_at', 'rejected_by', 'rejected_at', 'rejection_reason',
        'metadata', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'scheduled_start' => 'date',
            'scheduled_end' => 'date',
            'submitted_at' => 'datetime',
            'activated_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'metadata' => 'array',
        ]);
    }

    /**
     * Jadwal yang sudah lolos persetujuan akhir (boleh dipublikasikan).
     */
    public function scopePublished($query)
    {
        return $query->whereIn('status', self::PUBLISHED_STATUSES);
    }

    public function isPublished(): bool
    {
        return in_array($this->status, self::PUBLISHED_STATUSES, true);
    }

    public function awaitsFinalApproval(): bool
    {
        return in_array($this->status, self::PENDING_APPROVAL_STATUSES, true);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
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
