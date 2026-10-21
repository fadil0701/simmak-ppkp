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
        'organization_id', 'audit_execution_id', 'clinic_id', 'report_number', 'title',
        'report_type', 'period_start', 'period_end', 'executive_summary', 'findings_summary',
        'capa_summary', 'file_path', 'file_type', 'file_size', 'checksum', 'status',
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

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
