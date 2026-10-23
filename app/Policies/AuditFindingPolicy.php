<?php

namespace App\Policies;

use App\Models\AuditFinding;
use App\Models\User;
use App\Support\AuditWorkScope;
use App\Support\SimmakRoles;

class AuditFindingPolicy
{
    public function view(?User $user, AuditFinding $finding): bool
    {
        if (! $user || ! $user->is_active || ! $user->belongsToOrganization($finding->organization_id)) {
            return false;
        }

        return AuditWorkScope::canViewWork($user, $finding->clinic_id, $finding->execution?->assignment);
    }

    public function viewAny(?User $user): bool
    {
        return $user && $user->is_active && $user->can('audit.finding.view');
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->can('audit.finding.create');
    }

    public function update(User $user, AuditFinding $finding): bool
    {
        if (! $user->belongsToOrganization($finding->organization_id)) {
            return false;
        }
        if (! $user->is_active || ! $user->can('audit.finding.update')) {
            return false;
        }

        return in_array($finding->status, ['draft', 'under_review', 'returned_for_revision'], true);
    }

    public function review(User $user, AuditFinding $finding): bool
    {
        if (! $user->belongsToOrganization($finding->organization_id)) {
            return false;
        }

        return $user->is_active && $user->can('audit.finding.review')
            && $finding->status === 'under_review';
    }

    public function close(User $user, AuditFinding $finding): bool
    {
        if (! $user->belongsToOrganization($finding->organization_id) || ! $user->is_active) {
            return false;
        }
        if ($user->hasRole(SimmakRoles::ADMIN_SISTEM) && ! $user->hasRole(SimmakRoles::SUPER_ADMIN)) {
            return false;
        }
        if (! $user->can('audit.finding.close')) {
            return false;
        }
        if ($finding->status === 'closed') {
            return false;
        }
        $capa = $finding->capaCase;

        return $capa && $capa->hasEffectiveVerification();
    }

    public function reopen(User $user, AuditFinding $finding): bool
    {
        if (! $user->belongsToOrganization($finding->organization_id)) {
            return false;
        }
        if ($user->hasRole(SimmakRoles::ADMIN_SISTEM) && ! $user->hasRole(SimmakRoles::SUPER_ADMIN)) {
            return false;
        }

        return $user->is_active
            && $user->can('audit.finding.reopen')
            && $finding->status === 'closed';
    }

    /**
     * Eskalasi temuan kritis — hanya temuan severity 'critical' yang belum ditutup.
     */
    public function escalate(User $user, AuditFinding $finding): bool
    {
        if (! $user->belongsToOrganization($finding->organization_id)) {
            return false;
        }

        return $user->is_active
            && $user->can('audit.finding.escalate')
            && $finding->severity === 'critical'
            && $finding->status !== 'closed';
    }

    public function delete(User $user, AuditFinding $finding): bool
    {
        return $user->is_active && $user->can('audit.finding.update')
            && $user->belongsToOrganization($finding->organization_id)
            && $finding->status === 'draft';
    }
}
