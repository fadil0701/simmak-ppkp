<?php

namespace App\Policies;

use App\Models\AuditAssignment;
use App\Models\User;
use App\Support\AuditWorkScope;

class AuditAssignmentPolicy
{
    public function view(?User $user, AuditAssignment $assignment): bool
    {
        if (! $user || ! $user->is_active || ! $user->belongsToOrganization($assignment->organization_id)) {
            return false;
        }

        return AuditWorkScope::canViewWork($user, $assignment->clinic_id, $assignment);
    }

    public function viewAny(?User $user): bool
    {
        return $user && $user->is_active && $user->can('audit.assignment.view');
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->can('audit.assignment.manage');
    }

    public function update(User $user, AuditAssignment $assignment): bool
    {
        if (! $user->belongsToOrganization($assignment->organization_id)) {
            return false;
        }
        if (! $user->is_active || ! $user->can('audit.assignment.manage')) {
            return false;
        }

        return in_array($assignment->status, ['draft', 'proposed'], true);
    }

    public function activate(User $user, AuditAssignment $assignment): bool
    {
        if (! $user->belongsToOrganization($assignment->organization_id)) {
            return false;
        }
        if (! $user->is_active || ! $user->can('audit.assignment.manage')) {
            return false;
        }

        // Conflict of interest must be resolved before activation
        return $assignment->status === 'proposed'
            && $assignment->conflict_check_status === 'resolved';
    }

    public function execute(User $user, AuditAssignment $assignment): bool
    {
        if (! $user->belongsToOrganization($assignment->organization_id) || ! $user->is_active) {
            return false;
        }

        if (! $user->can('audit.execution.execute')) {
            return false;
        }

        return $assignment->status === 'active'
            && $assignment->teamMembers()
                ->where('user_id', $user->id)
                ->where('active', true)
                ->exists();
    }

    public function delete(User $user, AuditAssignment $assignment): bool
    {
        return $user->is_active && $user->can('audit.assignment.manage')
            && $user->belongsToOrganization($assignment->organization_id)
            && $assignment->status === 'draft';
    }
}
