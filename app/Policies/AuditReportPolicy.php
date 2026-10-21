<?php

namespace App\Policies;

use App\Models\AuditReport;
use App\Models\User;

class AuditReportPolicy
{
    public function view(?User $user, AuditReport $report): bool
    {
        return $user && $user->is_active
            && $user->belongsToOrganization($report->organization_id)
            && $user->hasClinicAccess($report->clinic_id);
    }

    public function viewAny(?User $user): bool
    {
        return $user && $user->is_active && $user->can('audit.report.view');
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->can('audit.report.view');
    }

    public function delete(User $user, AuditReport $report): bool
    {
        return $user->is_active && $user->can('audit.report.manage')
            && $user->belongsToOrganization($report->organization_id);
    }
}
