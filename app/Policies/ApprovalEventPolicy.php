<?php

namespace App\Policies;

use App\Models\ApprovalEvent;
use App\Models\User;

class ApprovalEventPolicy
{
    public function view(?User $user, ApprovalEvent $event): bool
    {
        return $user && $user->is_active && $user->belongsToOrganization($event->organization_id);
    }

    public function viewAny(?User $user): bool
    {
        return $user && $user->is_active && $user->can('audit.log.view');
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->can('audit.program.approve');
    }

    public function update(User $user, ApprovalEvent $event): bool
    {
        // Approval events are append-only; cannot be updated
        return false;
    }

    public function delete(User $user, ApprovalEvent $event): bool
    {
        // Approval events are append-only; cannot be deleted
        return false;
    }
}
