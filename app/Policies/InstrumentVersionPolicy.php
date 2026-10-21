<?php

namespace App\Policies;

use App\Models\InstrumentVersion;
use App\Models\User;

class InstrumentVersionPolicy
{
    public function view(?User $user, InstrumentVersion $version): bool
    {
        return $user && $user->is_active && $user->belongsToOrganization($version->standard?->organization_id);
    }

    public function viewAny(?User $user): bool
    {
        return $user && $user->is_active && $user->can('audit.standard.view');
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->can('audit.standard.manage');
    }

    public function update(User $user, InstrumentVersion $version): bool
    {
        if (! $user->is_active || ! $user->can('audit.standard.manage')) {
            return false;
        }
        if (! $user->belongsToOrganization($version->standard?->organization_id)) {
            return false;
        }

        // Published versions are immutable; only draft/review can be edited
        return in_array($version->status, ['draft', 'review'], true);
    }

    public function publish(User $user, InstrumentVersion $version): bool
    {
        if (! $user->is_active || ! $user->can('audit.standard.publish')) {
            return false;
        }
        if (! $user->belongsToOrganization($version->standard?->organization_id)) {
            return false;
        }

        return $version->status === 'approved';
    }

    public function delete(User $user, InstrumentVersion $version): bool
    {
        return $user->is_active && $user->can('audit.standard.manage')
            && $user->belongsToOrganization($version->standard?->organization_id)
            && $version->status === 'draft';
    }
}
