<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditReport extends BaseModel
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'audit_schedule_id', 'report_number', 'executive_summary',
        'findings_count', 'status', 'report_date', 'prepared_by',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'findings_count' => 'integer',
            'report_date' => 'date',
        ]);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(AuditSchedule::class, 'audit_schedule_id');
    }

    public function preparer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }
}
