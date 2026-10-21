<?php

namespace App\Services;

use App\Models\AccreditationProgram;
use App\Models\AccreditationSelfAssessment;
use App\Models\AuditFinding;
use App\Models\AuditTrail;
use App\Models\CapaCase;
use App\Models\Clinic;
use App\Models\Document;
use App\Models\RiskItem;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    public function __construct(private RiskService $riskService) {}

    public function executiveData(User $user): array
    {
        $organizationId = $user->organization_id;

        return Cache::remember("executive_dashboard_{$organizationId}", 300, fn () => $this->buildExecutiveData($organizationId));
    }

    private function buildExecutiveData(string $organizationId): array
    {
        $clinics = Clinic::query()
            ->where('organization_id', $organizationId)
            ->where('is_active', true)
            ->whereNotIn('clinic_type', ['pusat'])
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'city', 'clinic_type', 'accreditation_status', 'accreditation_type']);

        $openFindings = AuditFinding::forOrganization($organizationId)->whereIn('status', ['open', 'in_capa'])->count();
        $highPriorityFindings = AuditFinding::forOrganization($organizationId)
            ->whereIn('status', ['open', 'in_capa'])
            ->whereIn('severity', ['major', 'critical'])
            ->count();

        $capaStats = $this->capaStats($organizationId);
        $expiredDocuments = Document::forOrganization($organizationId)->where('status', 'expired')->count();
        $expiringSoon = Document::forOrganization($organizationId)
            ->where('status', 'published')
            ->whereBetween('expiry_date', [now()->toDateString(), now()->addDays(30)->toDateString()])
            ->count();
        $activeRisks = RiskItem::forOrganization($organizationId)
            ->whereNotIn('status', ['closed'])
            ->count();

        $readinessScores = $this->accreditationScoresByClinic($organizationId);
        $orgReadiness = $readinessScores->isNotEmpty()
            ? (int) round($readinessScores->avg())
            : 0;

        $targetSurvey = AccreditationProgram::forOrganization($organizationId)
            ->whereNotIn('status', ['accredited'])
            ->orderBy('target_survey_date')
            ->value('target_survey_date');

        $riskItems = RiskItem::forOrganization($organizationId)
            ->whereNotIn('status', ['closed'])
            ->get(['inherent_likelihood', 'inherent_impact']);

        return [
            'stats' => [
                'readiness' => $orgReadiness,
                'open_findings' => $openFindings,
                'high_priority_findings' => $highPriorityFindings,
                'capa_open' => $capaStats['open'],
                'capa_total' => $capaStats['total'],
                'capa_progress' => $capaStats['progress_pct'],
                'expired_documents' => $expiredDocuments,
                'expiring_documents' => $expiringSoon,
                'active_risks' => $activeRisks,
                'accreditation_target' => $targetSurvey?->format('d M Y') ?? '—',
            ],
            'capa_breakdown' => $capaStats['breakdown'],
            'heatmap' => $this->riskService->buildHeatmap($riskItems),
            'clinic_ranking' => $this->buildClinicRanking($organizationId, $clinics, $readinessScores),
            'activities' => $this->recentActivities($organizationId),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function buildClinicRanking(string $organizationId, ?Collection $clinics = null, ?Collection $readinessScores = null): Collection
    {
        $clinics ??= Clinic::query()
            ->where('organization_id', $organizationId)
            ->where('is_active', true)
            ->whereNotIn('clinic_type', ['pusat'])
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'city', 'clinic_type', 'accreditation_status', 'accreditation_type']);

        $readinessScores ??= $this->accreditationScoresByClinic($organizationId);

        $findingsByClinic = AuditFinding::forOrganization($organizationId)
            ->whereIn('status', ['open', 'in_capa'])
            ->selectRaw('clinic_id, COUNT(*) as total')
            ->groupBy('clinic_id')
            ->pluck('total', 'clinic_id');

        $capaByClinic = CapaCase::forOrganization($organizationId)
            ->selectRaw("clinic_id, COUNT(*) as total, SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed")
            ->groupBy('clinic_id')
            ->get()
            ->keyBy('clinic_id');

        $riskByClinic = RiskItem::forOrganization($organizationId)
            ->whereNotIn('status', ['closed'])
            ->selectRaw('clinic_id, AVG(inherent_score) as avg_score, COUNT(*) as total')
            ->groupBy('clinic_id')
            ->get()
            ->keyBy('clinic_id');

        return $clinics->map(function (Clinic $clinic) use ($readinessScores, $findingsByClinic, $capaByClinic, $riskByClinic) {
            $readiness = (int) ($readinessScores[$clinic->id] ?? $this->fallbackReadiness($clinic));
            $capa = $capaByClinic[$clinic->id] ?? null;
            $capaTotal = (int) ($capa?->total ?? 0);
            $capaClosed = (int) ($capa?->closed ?? 0);
            $capaDone = $capaTotal > 0 ? (int) round(($capaClosed / $capaTotal) * 100) : 0;
            $risk = $riskByClinic[$clinic->id] ?? null;

            return [
                'clinic_id' => $clinic->id,
                'code' => $clinic->code,
                'name' => $clinic->name,
                'region' => $clinic->city ?? 'DKI Jakarta',
                'readiness' => $readiness,
                'open_findings' => (int) ($findingsByClinic[$clinic->id] ?? 0),
                'capa_done' => $capaDone,
                'risk_score' => round((float) ($risk?->avg_score ?? 0), 1),
                'risk_count' => (int) ($risk?->total ?? 0),
                'accreditation_status' => $clinic->accreditation_status,
                'accreditation_type' => $clinic->accreditation_type,
                'status_label' => $this->statusLabel($readiness),
                'status_color' => $this->statusColor($readiness),
            ];
        })->sortByDesc('readiness')->values()
            ->map(fn ($row, $i) => array_merge($row, ['rank' => $i + 1]));
    }

    /**
     * @return array{open: int, total: int, progress_pct: int, breakdown: array<int, array{label: string, pct: int, color: string}>}
     */
    private function capaStats(string $organizationId): array
    {
        $total = CapaCase::forOrganization($organizationId)->count();
        $closed = CapaCase::forOrganization($organizationId)->where('status', 'closed')->count();
        $inProgress = CapaCase::forOrganization($organizationId)->whereIn('status', ['action', 'verification'])->count();
        $open = CapaCase::forOrganization($organizationId)->whereIn('status', ['open', 'analysis'])->count();
        $overdue = CapaCase::forOrganization($organizationId)
            ->whereNotIn('status', ['closed'])
            ->where('target_close_date', '<', now()->toDateString())
            ->count();

        $pct = fn (int $count) => $total > 0 ? (int) round(($count / $total) * 100) : 0;

        return [
            'open' => $open + $inProgress,
            'total' => $total,
            'progress_pct' => $total > 0 ? (int) round(($closed / $total) * 100) : 0,
            'breakdown' => [
                ['label' => 'Selesai', 'pct' => $pct($closed), 'color' => 'bg-teal-500', 'hex' => '#14b8a6'],
                ['label' => 'Dalam Proses', 'pct' => $pct($inProgress), 'color' => 'bg-blue-500', 'hex' => '#3b82f6'],
                ['label' => 'Belum Dimulai', 'pct' => $pct($open), 'color' => 'bg-amber-400', 'hex' => '#fbbf24'],
                ['label' => 'Terlambat', 'pct' => $pct($overdue), 'color' => 'bg-red-500', 'hex' => '#ef4444'],
            ],
        ];
    }

    private function accreditationScoresByClinic(string $organizationId): Collection
    {
        return AccreditationSelfAssessment::forOrganization($organizationId)
            ->selectRaw('clinic_id, MAX(overall_score) as score')
            ->groupBy('clinic_id')
            ->pluck('score', 'clinic_id')
            ->map(fn ($score) => (int) round((float) $score));
    }

    private function fallbackReadiness(Clinic $clinic): int
    {
        return match ($clinic->accreditation_status) {
            'terakreditasi' => 90,
            'in_progress', 'dalam_proses' => 72,
            'belum' => 55,
            default => 65,
        };
    }

    /**
     * @return array<int, array{icon: string, text: string, time: string}>
     */
    private function recentActivities(string $organizationId): array
    {
        $activities = [];

        $trails = AuditTrail::query()
            ->where('organization_id', $organizationId)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        foreach ($trails as $trail) {
            $activities[] = [
                'icon' => match (true) {
                    str_contains($trail->event, 'login') => 'user',
                    str_contains($trail->event, 'document') => 'doc',
                    str_contains($trail->event, 'capa') => 'check',
                    str_contains($trail->event, 'finding') || str_contains($trail->event, 'audit') => 'alert',
                    default => 'chart',
                },
                'text' => $this->formatTrailEvent($trail->event),
                'subtitle' => $trail->metadata['clinic'] ?? $trail->metadata['module'] ?? null,
                'time' => $trail->created_at?->diffForHumans() ?? '—',
            ];
        }

        if (count($activities) < 4) {
            $recentFindings = AuditFinding::forOrganization($organizationId)
                ->with('clinic:id,name')
                ->orderByDesc('updated_at')
                ->limit(4 - count($activities))
                ->get();

            foreach ($recentFindings as $finding) {
                $activities[] = [
                    'icon' => 'alert',
                    'text' => 'Temuan audit: '.($finding->title ?? $finding->finding_code ?? 'Temuan baru'),
                    'subtitle' => $finding->clinic?->name,
                    'time' => $finding->updated_at?->diffForHumans() ?? '—',
                ];
            }
        }

        if (empty($activities)) {
            $recentDoc = Document::forOrganization($organizationId)->orderByDesc('updated_at')->first();
            if ($recentDoc) {
                $activities[] = [
                    'icon' => 'doc',
                    'text' => 'Dokumen diperbarui: '.$recentDoc->title,
                    'subtitle' => $recentDoc->document_code ?? null,
                    'time' => $recentDoc->updated_at->diffForHumans(),
                ];
            }

            $recentCapa = CapaCase::forOrganization($organizationId)->with('clinic:id,name')->orderByDesc('updated_at')->first();
            if ($recentCapa) {
                $activities[] = [
                    'icon' => 'check',
                    'text' => 'CAPA '.$recentCapa->case_number.' — '.ucfirst($recentCapa->status),
                    'subtitle' => $recentCapa->clinic?->name,
                    'time' => $recentCapa->updated_at->diffForHumans(),
                ];
            }
        }

        return array_slice($activities ?: [
            ['icon' => 'doc', 'text' => 'Belum ada aktivitas tercatat', 'subtitle' => null, 'time' => '—'],
        ], 0, 4);
    }

    private function formatTrailEvent(string $event): string
    {
        return match ($event) {
            'auth.login' => 'Pengguna masuk ke sistem',
            'auth.logout' => 'Pengguna keluar dari sistem',
            'document.created' => 'Dokumen baru dibuat',
            'document.published' => 'Dokumen dipublikasikan',
            default => str_replace(['.', '_'], [' — ', ' '], ucfirst($event)),
        };
    }

    public function availableRegions(string $organizationId): array
    {
        return Clinic::query()
            ->where('organization_id', $organizationId)
            ->where('is_active', true)
            ->whereNotIn('clinic_type', ['pusat'])
            ->whereNotNull('city')
            ->distinct()
            ->orderBy('city')
            ->pluck('city')
            ->all();
    }

    private function statusLabel(int $score): string
    {
        return match (true) {
            $score >= 90 => 'Sangat Baik',
            $score >= 80 => 'Baik',
            $score >= 70 => 'Cukup',
            $score >= 60 => 'Perlu Perbaikan',
            default => 'Kurang',
        };
    }

    private function statusColor(int $score): string
    {
        return match (true) {
            $score >= 90 => 'border-emerald-600 text-emerald-700',
            $score >= 80 => 'border-emerald-500 text-emerald-600',
            $score >= 70 => 'border-amber-500 text-amber-600',
            $score >= 60 => 'border-orange-500 text-orange-600',
            default => 'border-red-500 text-red-600',
        };
    }
}
