<?php

namespace App\Policies;

use App\Models\CapaCase;
use App\Models\User;

class CapaCasePolicy
{
    public function view(?User $user, CapaCase $capa): bool
    {
        return $user && $user->is_active
            && $user->belongsToOrganization($capa->organization_id)
            && $user->hasClinicAccess($capa->clinic_id);
    }

    public function viewAny(?User $user): bool
    {
        return $user && $user->is_active && $user->can('audit.action.view');
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

        // Owner or PIC can update their own case while not closed
        return $capa->owner_id === $user->id
            && $capa->status !== 'closed';
    }

    public function verify(User $user, CapaCase $capa): bool
    {
        if (! $user->belongsToOrganization($capa->organization_id)) {
            return false;
        }
        if (! $user->is_active || ! $user->can('audit.capa.verify')) {
            return false;
        }

        // Separation of duties: verifier cannot be the owner/PIC
        return $capa->owner_id !== $user->id;
    }

    public function delete(User $user, CapaCase $capa): bool
    {
        return $user->is_active && $user->can('audit.action.update')
            && $user->belongsToOrganization($capa->organization_id)
            && $capa->status !== 'closed';
    }
}
