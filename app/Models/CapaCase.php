<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\ClinicScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CapaCase extends BaseModel
{
    use BelongsToOrganization;
    use ClinicScope;

    protected $fillable = [
        'organization_id', 'clinic_id', 'case_number', 'source_type', 'source_id',
        'priority', 'problem_statement', 'status', 'owner_id', 'target_close_date',
        'closed_at', 'metadata', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'target_close_date' => 'date',
            'closed_at' => 'date',
        ]);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(CapaAction::class);
    }

    public function rca(): HasMany
    {
        return $this->hasMany(CapaRootCauseAnalysis::class);
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(CapaVerification::class);
    }

    public function sourceFinding(): BelongsTo
    {
        return $this->belongsTo(AuditFinding::class, 'source_id');
    }
}
