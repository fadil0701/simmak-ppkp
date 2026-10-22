<?php

namespace App\Policies;

use App\Models\AuditExecution;
use App\Models\User;

class AuditExecutionPolicy
{
    public function view(?User $user, AuditExecution $execution): bool
    {
        if (! $user || ! $user->is_active || ! $user->belongsToOrganization($execution->organization_id)) {
            return false;
        }

        // Anggota tim yang ditugaskan boleh melihat.
        if ($execution->assignment) {
            $isTeamMember = $execution->assignment->teamMembers()
                ->where('user_id', $user->id)
                ->where('active', true)
                ->exists();
            if ($isTeamMember) {
                return true;
            }
        }

        // Pengawas (role/permission tinjauan) dengan akses klinik boleh melihat.
        $supervisory = $user->can('audit.finding.review')
            || $user->can('audit.program.approve')
            || $user->can('audit.report.manage');
        if ($supervisory && $user->hasClinicAccess($execution->clinic_id)) {
            return true;
        }

        return false;
    }

    public function viewAny(?User $user): bool
    {
        return $user && $user->is_active && $user->can('audit.execution.view');
    }

    public function execute(User $user, AuditExecution $execution): bool
    {
        if (! $user->belongsToOrganization($execution->organization_id)) {
            return false;
        }
        if (! $user->is_active || ! $user->can('audit.execution.execute')) {
            return false;
        }
        // Auditor must be assigned to this execution's assignment
        if ($execution->assignment) {
            return $execution->assignment->teamMembers()
                ->where('user_id', $user->id)
                ->where('active', true)
                ->exists();
        }

        return false;
    }

    public function submit(User $user, AuditExecution $execution): bool
    {
        if (! $user->belongsToOrganization($execution->organization_id)) {
            return false;
        }

        return $user->is_active && $user->can('audit.execution.submit')
            && $execution->status === 'in_progress';
    }

    public function finalize(User $user, AuditExecution $execution): bool
    {
        if (! $user->belongsToOrganization($execution->organization_id)) {
            return false;
        }

        return $user->is_active
            && $user->can('audit.execution.submit')
            && $execution->status === 'submitted_for_review';
    }

    public function delete(User $user, AuditExecution $execution): bool
    {
        return $user->is_active && $user->can('audit.execution.view')
            && $user->belongsToOrganization($execution->organization_id)
            && $execution->status === 'preparation';
    }
}
