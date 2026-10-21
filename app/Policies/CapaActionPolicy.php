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
        return $user->is_active && $user->can('audit.action.update');
    }

    public function update(User $user, CapaAction $action): bool
    {
        $capa = $action->capaCase;
        if (! $capa || ! $user->belongsToOrganization($capa->organization_id)) {
            return false;
        }
        if (! $user->is_active || ! $user->can('audit.action.update')) {
            return false;
        }

        return $action->assigned_to === $user->id
            && $capa->status !== 'closed';
    }

    public function delete(User $user, CapaAction $action): bool
    {
        return $this->update($user, $action);
    }
}
