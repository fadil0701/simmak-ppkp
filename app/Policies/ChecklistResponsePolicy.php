<?php

namespace App\Policies;

use App\Models\ChecklistResponse;
use App\Models\User;

class ChecklistResponsePolicy
{
    public function view(?User $user, ChecklistResponse $response): bool
    {
        return $user && $user->is_active && $user->belongsToOrganization($response->organization_id);
    }

    public function viewAny(?User $user): bool
    {
        return $user && $user->is_active && $user->can('audit.execution.view');
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->can('audit.execution.execute');
    }

    public function update(User $user, ChecklistResponse $response): bool
    {
        if (! $user->belongsToOrganization($response->organization_id)) {
            return false;
        }
        if (! $user->is_active || ! $user->can('audit.execution.execute')) {
            return false;
        }
        // Only the assessor can update their own response while execution is in progress
        $execution = $response->execution;
        if (! $execution || $execution->status !== 'in_progress') {
            return false;
        }

        return $response->assessed_by === $user->id;
    }

    public function delete(User $user, ChecklistResponse $response): bool
    {
        return $this->update($user, $response);
    }
}
