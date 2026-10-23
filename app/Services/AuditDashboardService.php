<?php

namespace App\Services;

use App\Models\AuditAssignment;
use App\Models\AuditExecution;
use App\Models\AuditFinding;
use App\Models\AuditProgram;
use App\Models\AuditTrail;
use App\Models\CapaCase;
use App\Models\Clinic;
use App\Models\User;
use App\Support\AuditWorkScope;
use Illuminate\Support\Collection;

class AuditDashboardService
{
    public function auditDashboardData(User $user): array
    {
        $clinicIds = $this->clinicIdsForActivities($user);

        return [
            'stats' => $this->stats($user),
            'overdueActions' => $this->overdueActions($user),
            'reviewQueue' => $this->reviewQueue($user),
            'highRiskFindings' => $this->highRiskFindings($user),
            'upcomingAssignments' => $this->upcomingAssignments($user),
            'recentActivities' => $this->recentActivities($user, $clinicIds),
            'findingsByClinic' => $this->findingsByClinic($user),
        ];
    }

    public function stats(User $user): array
    {
        return [
            'programs' => AuditProgram::forOrganization($user->organization_id)->count(),
            'programs_approved' => AuditProgram::forOrganization($user->organization_id)->where('status', 'approved')->count(),
            'assignments_active' => AuditWorkScope::assignments(AuditAssignment::query(), $user)->where('status', 'active')->count(),
            'assignments_upcoming' => $this->upcomingAssignmentsQuery($user)->count(),
            'executions_in_progress' => AuditWorkScope::executions(AuditExecution::query(), $user)->where('status', 'in_progress')->count(),
            'executions_submitted' => AuditWorkScope::executions(AuditExecution::query(), $user)->where('status', 'submitted_for_review')->count(),
            'findings_open' => AuditWorkScope::findings(AuditFinding::query(), $user)->whereIn('status', AuditFinding::OPEN_STATUSES)->count(),
            'findings_closed' => AuditWorkScope::findings(AuditFinding::query(), $user)->where('status', 'closed')->count(),
            'findings_critical' => AuditWorkScope::findings(AuditFinding::query(), $user)
                ->where('severity', 'critical')
                ->whereIn('status', AuditFinding::OPEN_STATUSES)
                ->count(),
            'findings_under_review' => AuditWorkScope::findings(AuditFinding::query(), $user)->where('status', 'under_review')->count(),
            'capa_open' => AuditWorkScope::capaCases(CapaCase::query(), $user)->whereNotIn('status', ['closed'])->count(),
            'capa_closed' => AuditWorkScope::capaCases(CapaCase::query(), $user)->where('status', 'closed')->count(),
            'capa_overdue' => $this->overdueCapaQuery($user)->count(),
        ];
    }

    private function overdueCapaQuery(User $user)
    {
        return AuditWorkScope::capaCases(CapaCase::query(), $user)
            ->whereNotIn('status', ['closed'])
            ->where('target_close_date', '<', now()->toDateString());
    }

    private function overdueActions(User $user): Collection
    {
        return $this->overdueCapaQuery($user)
            ->with('clinic', 'owner')
            ->orderBy('target_close_date')
            ->limit(10)
            ->get();
    }

    private function reviewQueue(User $user): Collection
    {
        return AuditWorkScope::executions(AuditExecution::query(), $user)
            ->where('status', 'submitted_for_review')
            ->with('clinic', 'assignment')
            ->orderBy('submitted_at')
            ->limit(10)
            ->get();
    }

    private function highRiskFindings(User $user): Collection
    {
        // 07_TEMUAN_AUDIT.md §5: risiko tinggi = Kritikal dan Mayor.
        return AuditWorkScope::findings(AuditFinding::query(), $user)
            ->whereIn('severity', ['critical', 'major'])
            ->whereIn('status', AuditFinding::OPEN_STATUSES)
            ->with('clinic', 'owner')
            ->orderByDesc('identified_at')
            ->limit(10)
            ->get();
    }

    private function upcomingAssignmentsQuery(User $user)
    {
        return AuditWorkScope::assignments(AuditAssignment::query(), $user)
            ->whereIn('status', ['active', 'proposed'])
            ->whereDate('scheduled_end', '>=', now()->toDateString());
    }

    private function upcomingAssignments(User $user): Collection
    {
        return $this->upcomingAssignmentsQuery($user)
            ->with(['clinic', 'program', 'leadAuditor'])
            ->orderBy('scheduled_start')
            ->limit(8)
            ->get();
    }

    /**
     * @param  list<string>|null  $clinicIds
     */
    private function recentActivities(User $user, ?array $clinicIds): array
    {
        $query = AuditTrail::query()
            ->where('organization_id', $user->organization_id)
            ->with(['user', 'clinic'])
            ->orderByDesc('created_at')
            ->limit(10);

        if ($clinicIds !== null) {
            $query->where(function ($inner) use ($clinicIds) {
                $inner->whereIn('clinic_id', $clinicIds)->orWhereNull('clinic_id');
            });
        }

        return $query->get()->map(fn (AuditTrail $trail) => [
            'event' => $trail->event,
            'actor' => $trail->user?->name ?? 'Sistem',
            'clinic' => $trail->clinic?->name ?? '-',
            'time' => $trail->created_at?->diffForHumans() ?? '—',
        ])->all();
    }

    private function findingsByClinic(User $user): Collection
    {
        $clinics = Clinic::forUserClinics($user)
            ->auditable()
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'city']);

        $findingsByClinic = AuditWorkScope::findings(AuditFinding::query(), $user)
            ->whereIn('status', AuditFinding::OPEN_STATUSES)
            ->selectRaw('clinic_id, COUNT(*) as total')
            ->groupBy('clinic_id')
            ->pluck('total', 'clinic_id');

        $criticalByClinic = AuditWorkScope::findings(AuditFinding::query(), $user)
            ->whereIn('status', AuditFinding::OPEN_STATUSES)
            ->where('severity', 'critical')
            ->selectRaw('clinic_id, COUNT(*) as total')
            ->groupBy('clinic_id')
            ->pluck('total', 'clinic_id');

        $capaOverdueByClinic = $this->overdueCapaQuery($user)
            ->selectRaw('clinic_id, COUNT(*) as total')
            ->groupBy('clinic_id')
            ->pluck('total', 'clinic_id');

        return $clinics->map(function (Clinic $clinic) use ($findingsByClinic, $criticalByClinic, $capaOverdueByClinic) {
            return [
                'clinic_id' => $clinic->id,
                'code' => $clinic->code,
                'name' => $clinic->name,
                'region' => $clinic->city ?? '—',
                'open_findings' => (int) ($findingsByClinic[$clinic->id] ?? 0),
                'critical_findings' => (int) ($criticalByClinic[$clinic->id] ?? 0),
                'capa_overdue' => (int) ($capaOverdueByClinic[$clinic->id] ?? 0),
            ];
        });
    }

    /**
     * @return list<string>|null null = seluruh klinik organisasi (pengawas)
     */
    private function clinicIdsForActivities(User $user): ?array
    {
        if ($user->isSuperAdmin() || $user->hasCrossClinicAccess()) {
            return null;
        }

        return $user->clinics()->pluck('clinics.id')->all();
    }
}
