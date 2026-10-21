<?php

namespace App\Policies;

use App\Models\AuditAssignment;
use App\Models\User;

class AuditAssignmentPolicy
{
    public function view(?User $user, AuditAssignment $assignment): bool
    {
        return $user && $user->is_active
            && $user->belongsToOrganization($assignment->organization_id)
            && $user->hasClinicAccess($assignment->clinic_id);
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

    public function delete(User $user, AuditAssignment $assignment): bool
    {
        return $user->is_active && $user->can('audit.assignment.manage')
            && $user->belongsToOrganization($assignment->organization_id)
            && $assignment->status === 'draft';
    }
}
