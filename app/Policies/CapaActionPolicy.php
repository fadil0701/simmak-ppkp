<?php

namespace App\Policies;

use App\Models\CapaAction;
use App\Models\User;

class CapaActionPolicy
{
    public function view(?User $user, CapaAction $action): bool
    {
        $capa = $action->capaCase;

        return $user && $user->is_active && $capa && $user->belongsToOrganization($capa->organization_id);
    }

    public function viewAny(?User $user): bool
    {
        return $user && $user->is_active && $user->can('audit.action.view');
    }

    public function create(User $user): bool
    {
        return $user->is_active && ($user->can('audit.action.update') || $user->can('capa.manage'));
    }

    public function update(User $user, CapaAction $action): bool
    {
        $capa = $action->capaCase;
        if (! $capa || ! $user->belongsToOrganization($capa->organization_id)) {
            return false;
        }
        if (! $user->is_active) {
            return false;
        }
        if ($capa->status === 'closed') {
            return false;
        }

        $canAct = $user->can('audit.action.update') || $user->can('capa.manage');
        if (! $canAct) {
            return false;
        }

        if ($action->assigned_to === $user->id || $capa->owner_id === $user->id) {
            return true;
        }

        return $user->can('capa.manage') && $user->hasClinicAccess($capa->clinic_id);
    }

    public function delete(User $user, CapaAction $action): bool
    {
        return $this->update($user, $action);
    }
}
