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
use Illuminate\Support\Collection;

class AuditDashboardService
{
    /** @var list<string> */
    public const OPEN_FINDING_STATUSES = [
        'draft',
        'under_review',
        'confirmed',
        'action_required',
        'pending_verification',
        'in_capa',
        'open',
    ];

    public function auditDashboardData(User $user): array
    {
        $orgId = $user->organization_id;

        return [
            'stats' => $this->stats($orgId),
            'overdueActions' => $this->overdueActions($orgId),
            'reviewQueue' => $this->reviewQueue($orgId),
            'highRiskFindings' => $this->highRiskFindings($orgId),
            'upcomingAssignments' => $this->upcomingAssignments($orgId),
            'recentActivities' => $this->recentActivities($orgId),
            'findingsByClinic' => $this->findingsByClinic($orgId),
        ];
    }

    private function stats(string $orgId): array
    {
        return [
            'programs' => AuditProgram::forOrganization($orgId)->count(),
            'programs_approved' => AuditProgram::forOrganization($orgId)->where('status', 'approved')->count(),
            'assignments_active' => AuditAssignment::forOrganization($orgId)->where('status', 'active')->count(),
            'executions_in_progress' => AuditExecution::forOrganization($orgId)->where('status', 'in_progress')->count(),
            'executions_submitted' => AuditExecution::forOrganization($orgId)->where('status', 'submitted_for_review')->count(),
            'findings_open' => AuditFinding::forOrganization($orgId)->whereIn('status', self::OPEN_FINDING_STATUSES)->count(),
            'findings_closed' => AuditFinding::forOrganization($orgId)->where('status', 'closed')->count(),
            'capa_open' => CapaCase::forOrganization($orgId)->whereNotIn('status', ['closed'])->count(),
            'capa_closed' => CapaCase::forOrganization($orgId)->where('status', 'closed')->count(),
            'capa_overdue' => CapaCase::forOrganization($orgId)
                ->whereNotIn('status', ['closed'])
                ->where('target_close_date', '<', now()->toDateString())
                ->count(),
        ];
    }

    private function overdueActions(string $orgId): Collection
    {
        return CapaCase::forOrganization($orgId)
            ->whereNotIn('status', ['closed'])
            ->where('target_close_date', '<', now()->toDateString())
            ->with('clinic', 'owner')
            ->orderBy('target_close_date')
            ->limit(10)
            ->get();
    }

    private function reviewQueue(string $orgId): Collection
    {
        return AuditExecution::forOrganization($orgId)
            ->where('status', 'submitted_for_review')
            ->with('clinic', 'assignment')
            ->orderBy('submitted_at')
            ->limit(10)
            ->get();
    }

    private function highRiskFindings(string $orgId): Collection
    {
        return AuditFinding::forOrganization($orgId)
            ->where('severity', 'critical')
            ->whereIn('status', self::OPEN_FINDING_STATUSES)
            ->with('clinic', 'owner')
            ->orderByDesc('identified_at')
            ->limit(10)
            ->get();
    }

    private function upcomingAssignments(string $orgId): Collection
    {
        return AuditAssignment::forOrganization($orgId)
            ->whereIn('status', ['active', 'proposed'])
            ->with(['clinic', 'program', 'leadAuditor'])
            ->orderBy('scheduled_start')
            ->limit(8)
            ->get();
    }

    private function recentActivities(string $orgId): array
    {
        $activities = [];

        $trails = AuditTrail::query()
            ->where('organization_id', $orgId)
            ->with(['user', 'clinic'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        foreach ($trails as $trail) {
            $activities[] = [
                'event' => $trail->event,
                'actor' => $trail->user?->name ?? 'Sistem',
                'clinic' => $trail->clinic?->name ?? '-',
                'time' => $trail->created_at?->diffForHumans() ?? '—',
            ];
        }

        return $activities;
    }

    private function findingsByClinic(string $orgId): Collection
    {
        $clinics = Clinic::forOrganization($orgId)
            ->auditable()
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'city']);

        $findingsByClinic = AuditFinding::forOrganization($orgId)
            ->whereIn('status', self::OPEN_FINDING_STATUSES)
            ->selectRaw('clinic_id, COUNT(*) as total')
            ->groupBy('clinic_id')
            ->pluck('total', 'clinic_id');

        $criticalByClinic = AuditFinding::forOrganization($orgId)
            ->whereIn('status', self::OPEN_FINDING_STATUSES)
            ->where('severity', 'critical')
            ->selectRaw('clinic_id, COUNT(*) as total')
            ->groupBy('clinic_id')
            ->pluck('total', 'clinic_id');

        $capaOverdueByClinic = CapaCase::forOrganization($orgId)
            ->whereNotIn('status', ['closed'])
            ->where('target_close_date', '<', now()->toDateString())
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
}
