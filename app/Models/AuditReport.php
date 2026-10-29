<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\ClinicScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditReport extends BaseModel
{
    use BelongsToOrganization;
    use ClinicScope;

    protected $fillable = [
        'organization_id', 'audit_execution_id', 'capa_action_id', 'audit_finding_id', 'clinic_id',
        'report_number', 'title',
        'report_type', 'period_start', 'period_end', 'executive_summary', 'findings_summary',
        'capa_summary', 'file_path', 'original_filename', 'file_type', 'file_size', 'checksum', 'status',
        'generated_by', 'generated_at', 'metadata',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'period_start' => 'date',
            'period_end' => 'date',
            'file_size' => 'integer',
            'generated_at' => 'datetime',
            'findings_summary' => 'array',
            'capa_summary' => 'array',
            'metadata' => 'array',
        ]);
    }

    public function execution(): BelongsTo
    {
        return $this->belongsTo(AuditExecution::class, 'audit_execution_id');
    }

    /** Tindakan tindak lanjut pemilik laporan (null untuk laporan tingkat audit). */
    public function action(): BelongsTo
    {
        return $this->belongsTo(CapaAction::class, 'capa_action_id');
    }

    /** Temuan pemilik laporan (null bila laporan tidak terikat temuan). */
    public function finding(): BelongsTo
    {
        return $this->belongsTo(AuditFinding::class, 'audit_finding_id');
    }

    /**
     * Nama berkas untuk unduhan: nama asli unggahan, atau nomor laporan untuk
     * laporan PDF yang dihasilkan sistem.
     */
    public function downloadName(): string
    {
        if (filled($this->original_filename)) {
            return $this->original_filename;
        }

        return $this->report_number.'.'.($this->file_type ?: 'pdf');
    }

    public function isFollowUpReport(): bool
    {
        return filled($this->capa_action_id);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
