<?php

namespace App\Providers;

use App\Models\ApprovalEvent;
use App\Models\AuditAssignment;
use App\Models\AuditExecution;
use App\Models\AuditFinding;
use App\Models\AuditProgram;
use App\Models\AuditReport;
use App\Models\CapaAction;
use App\Models\CapaCase;
use App\Models\ChecklistResponse;
use App\Models\Evidence;
use App\Models\InstrumentVersion;
use App\Models\User;
use App\Policies\ApprovalEventPolicy;
use App\Policies\AuditAssignmentPolicy;
use App\Policies\AuditExecutionPolicy;
use App\Policies\AuditFindingPolicy;
use App\Policies\AuditProgramPolicy;
use App\Policies\AuditReportPolicy;
use App\Policies\CapaActionPolicy;
use App\Policies\CapaCasePolicy;
use App\Policies\ChecklistResponsePolicy;
use App\Policies\EvidencePolicy;
use App\Policies\InstrumentVersionPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        AuditProgram::class => AuditProgramPolicy::class,
        AuditAssignment::class => AuditAssignmentPolicy::class,
        AuditExecution::class => AuditExecutionPolicy::class,
        ChecklistResponse::class => ChecklistResponsePolicy::class,
        Evidence::class => EvidencePolicy::class,
        AuditFinding::class => AuditFindingPolicy::class,
        AuditReport::class => AuditReportPolicy::class,
        CapaCase::class => CapaCasePolicy::class,
        CapaAction::class => CapaActionPolicy::class,
        InstrumentVersion::class => InstrumentVersionPolicy::class,
        ApprovalEvent::class => ApprovalEventPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        /*
         * Super admin (administrator sistem) tidak dibatasi policy, gate,
         * maupun middleware `permission:` mana pun — seluruh aksi CRUD pada
         * semua modul terbuka. Mengembalikan null (bukan false) meneruskan
         * keputusan ke policy/gate normal untuk peran lain.
         */
        Gate::before(function (?User $user, string $ability) {
            if (! $user instanceof User || ! $user->hasUnrestrictedAccess()) {
                return null;
            }

            return true;
        });
    }
}
