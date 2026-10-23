<?php

namespace App\Policies;

use App\Models\CapaCase;
use App\Models\User;
use App\Support\AuditWorkScope;
use App\Support\SimmakRoles;

class CapaCasePolicy
{
    public function view(?User $user, CapaCase $capa): bool
    {
        if (! $user || ! $user->is_active || ! $user->belongsToOrganization($capa->organization_id)) {
            return false;
        }

        $assignment = $capa->sourceFinding?->execution?->assignment;

        return AuditWorkScope::canViewWork($user, $capa->clinic_id, $assignment);
    }

    public function viewAny(?User $user): bool
    {
        // Menu CAPA dijaga izin capa.view (08_CAPA.md §2); audit.action.view tetap
        // diterima agar peran audit yang memakai menu Temuan tidak terkunci.
        return $user && $user->is_active
            && ($user->can('capa.view') || $user->can('audit.action.view'));
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->can('audit.action.update');
    }

    public function update(User $user, CapaCase $capa): bool
    {
        if (! $user->belongsToOrganization($capa->organization_id)) {
            return false;
        }
        if (! $user->is_active || ! $user->can('audit.action.update')) {
            return false;
        }

        if ($capa->status === 'closed') {
            return false;
        }

        if ($capa->owner_id === $user->id) {
            return true;
        }

        return $user->can('capa.manage') && $user->hasClinicAccess($capa->clinic_id);
    }

    public function verify(User $user, CapaCase $capa): bool
    {
        if (! $user->belongsToOrganization($capa->organization_id)) {
            return false;
        }
        if (! $user->is_active || ! $user->can('audit.capa.verify')) {
            return false;
        }
        if ($user->hasRole(SimmakRoles::ADMIN_SISTEM) && ! $user->hasRole(SimmakRoles::SUPER_ADMIN)) {
            return false;
        }
        if ($capa->owner_id === $user->id) {
            return false;
        }
        if ($capa->status === 'closed') {
            return false;
        }
        if (! $capa->hasSubmittedActionEvidence()) {
            return false;
        }

        $assignment = $capa->sourceFinding?->execution?->assignment;
        if ($assignment) {
            return AuditWorkScope::isAssignedTo($user, $assignment);
        }

        // Temuan tanpa penugasan tim (temuan manual): validasi hanya untuk peran
        // pengawas audit. Auditi/kepala klinik tidak boleh memvalidasi kasusnya
        // sendiri (08_CAPA.md §3).
        return $user->hasAnyRole([
            SimmakRoles::AUDITOR,
            SimmakRoles::KETUA_TIM_AUDIT,
            SimmakRoles::KETUA_TIM_MUTU,
            SimmakRoles::KEPALA_PUSAT,
        ]);
    }

    public function delete(User $user, CapaCase $capa): bool
    {
        return $user->is_active && $user->can('audit.action.update')
            && $user->belongsToOrganization($capa->organization_id)
            && $capa->status !== 'closed';
    }
}
