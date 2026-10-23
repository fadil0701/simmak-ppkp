<?php

namespace App\Policies;

use App\Models\Evidence;
use App\Models\User;
use App\Support\AuditWorkScope;

class EvidencePolicy
{
    public function view(?User $user, Evidence $evidence): bool
    {
        if (! $user || ! $user->is_active || ! $user->belongsToOrganization($evidence->organization_id)) {
            return false;
        }

        return AuditWorkScope::canViewWork($user, $evidence->clinic_id, $evidence->execution?->assignment);
    }

    public function viewAny(?User $user): bool
    {
        return $user && $user->is_active && $user->can('audit.evidence.view');
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->can('audit.evidence.upload');
    }

    public function update(User $user, Evidence $evidence): bool
    {
        if (! $user->belongsToOrganization($evidence->organization_id)) {
            return false;
        }

        return $user->is_active && $user->can('audit.evidence.upload')
            && $evidence->uploaded_by === $user->id;
    }

    public function delete(User $user, Evidence $evidence): bool
    {
        return $this->update($user, $evidence);
    }

    public function download(User $user, Evidence $evidence): bool
    {
        return $this->view($user, $evidence);
    }
}
