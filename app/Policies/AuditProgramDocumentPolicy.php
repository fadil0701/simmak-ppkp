<?php

namespace App\Policies;

use App\Models\AuditAssignment;
use App\Models\AuditProgramDocument;
use App\Models\User;
use App\Support\AuditWorkScope;

class AuditProgramDocumentPolicy
{
    public function view(?User $user, AuditProgramDocument $document): bool
    {
        if (! $user || ! $user->is_active || ! $user->belongsToOrganization($document->organization_id)) {
            return false;
        }

        $program = $document->program;
        if ($program === null) {
            return false;
        }

        if ($user->can('view', $program)
            && ($user->can('audit.program.create') || $user->can('audit.program.update') || $user->can('audit.program.approve'))) {
            return true;
        }

        return AuditWorkScope::assignments(
            AuditAssignment::query()->where('audit_program_id', $program->id),
            $user
        )->exists();
    }

    public function download(User $user, AuditProgramDocument $document): bool
    {
        return $this->view($user, $document);
    }
}
