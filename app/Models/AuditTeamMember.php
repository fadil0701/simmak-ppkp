<?php

namespace App\Models;

use App\Models\Concerns\BaseModel;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditTeamMember extends BaseModel
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'audit_assignment_id', 'user_id', 'team_role',
        'independence_declared', 'independence_declared_at', 'conflict_declared',
        'conflict_notes', 'active', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'independence_declared' => 'boolean',
            'independence_declared_at' => 'datetime',
            'conflict_declared' => 'boolean',
            'active' => 'boolean',
        ]);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(AuditAssignment::class, 'audit_assignment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
