<?php

namespace App\Policies;

use App\Models\AuditProgram;
use App\Models\User;

class AuditProgramPolicy
{
    public function view(?User $user, AuditProgram $program): bool
    {
        return $user && $user->is_active && $user->belongsToOrganization($program->organization_id);
    }

    public function viewAny(?User $user): bool
    {
        return $user && $user->is_active && $user->can('audit.program.view');
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->can('audit.program.create');
    }

    public function update(User $user, AuditProgram $program): bool
    {
        if (! $user->belongsToOrganization($program->organization_id)) {
            return false;
        }
        if (! $user->is_active || ! $user->can('audit.program.update')) {
            return false;
        }

        return in_array($program->status, ['draft', 'returned_for_revision'], true);
    }

    public function submit(User $user, AuditProgram $program): bool
    {
        if (! $user->belongsToOrganization($program->organization_id)) {
            return false;
        }

        return $user->is_active && $user->can('audit.program.submit')
            && $program->status === 'draft';
    }

    public function approve(User $user, AuditProgram $program): bool
    {
        if (! $user->belongsToOrganization($program->organization_id)) {
            return false;
        }
        if (! $user->is_active || ! $user->can('audit.program.approve')) {
            return false;
        }

        // Separation of duties: cannot approve own submission
        return $program->status === 'submitted_for_review'
            && $program->created_by !== $user->id;
    }

    public function delete(User $user, AuditProgram $program): bool
    {
        return $user->is_active && $user->can('audit.program.update')
            && $user->belongsToOrganization($program->organization_id)
            && $program->status === 'draft';
    }
}
