<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditProgram extends BaseModel
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'fiscal_year_id', 'program_code', 'audit_type',
        'scope_notes', 'name', 'objective', 'status', 'metadata',
        'created_by', 'updated_by',
    ];

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(AuditSchedule::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
