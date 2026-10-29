<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\ClinicScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evidence extends BaseModel
{
    use BelongsToOrganization;
    use ClinicScope;

    protected $fillable = [
        'organization_id', 'clinic_id', 'audit_execution_id', 'clinical_audit_id', 'uploaded_by', 'storage_disk',
        'storage_path', 'original_filename', 'mime_type', 'size_bytes', 'checksum',
        'evidence_type', 'access_classification', 'description', 'uploaded_at', 'metadata',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'size_bytes' => 'integer',
            'uploaded_at' => 'datetime',
        ]);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function execution(): BelongsTo
    {
        return $this->belongsTo(AuditExecution::class, 'audit_execution_id');
    }

    /** Audit klinis pemilik dokumentasi (null untuk bukti audit internal). */
    public function clinicalAudit(): BelongsTo
    {
        return $this->belongsTo(ClinicalAudit::class, 'clinical_audit_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function responseLinks(): HasMany
    {
        return $this->hasMany(ResponseEvidence::class, 'evidence_id');
    }

    public function findingLinks(): HasMany
    {
        return $this->hasMany(FindingEvidence::class, 'evidence_id');
    }

    public function actionLinks(): HasMany
    {
        return $this->hasMany(ActionEvidence::class, 'evidence_id');
    }

    /**
     * Tindakan tindak lanjut yang memiliki berkas ini (bila ada).
     *
     * Wajib memuat relasi `actionLinks.action` lebih dulu agar tidak memicu
     * query tambahan per baris pada daftar arsip bukti.
     */
    public function linkedFollowUp(): ?CapaAction
    {
        return $this->actionLinks->first()?->action;
    }
}
