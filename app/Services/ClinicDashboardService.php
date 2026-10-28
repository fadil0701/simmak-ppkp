<?php

namespace App\Services;

use App\Models\AuditSchedule;
use App\Models\CapaCase;
use App\Models\Clinic;
use App\Models\Document;
use App\Models\QualityIndicator;
use App\Models\QualityMeasurement;
use App\Models\RiskItem;
use App\Models\User;
use Illuminate\Support\Str;

class ClinicDashboardService
{
    public function __construct(
        private AccreditationService $accreditationService,
        private RiskService $riskService,
    ) {}

    public function data(User $user, ?Clinic $clinic): array
    {
        $orgId = $user->organization_id;
        $clinicName = $clinic?->name ?? 'Klinik';
        $clinicId = $clinic?->id;

        $documents = Document::query()
            ->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
            ->forOrganization($orgId)
            ->with('documentType')
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        $auditScheduled = AuditSchedule::forOrganization($orgId)
            ->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
            ->whereIn('status', ['scheduled', 'ongoing'])
            ->count();

        $auditDone = AuditSchedule::forOrganization($orgId)
            ->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
            ->where('status', 'completed')
            ->count();

        $capaOpen = CapaCase::forOrganization($orgId)
            ->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
            ->whereIn('status', ['open', 'analysis'])
            ->count();

        $capaInProgress = CapaCase::forOrganization($orgId)
            ->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
            ->whereIn('status', ['action', 'verification'])
            ->count();

        $riskItems = RiskItem::forOrganization($orgId)
            ->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
            ->whereNotIn('status', ['closed'])
            ->get(['inherent_likelihood', 'inherent_impact']);

        $accreditation = $this->accreditationService->dashboardData($orgId, $clinic);

        return [
            'clinic' => $clinic,
            'clinic_name' => $clinicName,
            'audit' => ['scheduled' => $auditScheduled, 'done' => $auditDone],
            'capa' => ['open' => $capaOpen, 'in_progress' => $capaInProgress],
            'risk_heatmap' => $this->riskService->buildHeatmap($riskItems),
            'accreditation_readiness' => collect($accreditation['chapter_progress'] ?? [])->map(fn ($ch) => [
                'name' => Str::limit($ch['title'], 42),
                'pct' => $ch['pct'],
            ])->all(),
            'accreditation_score' => $accreditation['readiness'] ?? 0,
            'quality_trends' => $this->qualityTrends($orgId, $clinicId),
            'documents' => $documents,
            'tasks' => $this->clinicTasks($orgId, $clinicId),
            'vision' => 'Menjadi klinik terdepan dalam pelayanan kesehatan berkualitas dan berorientasi keselamatan pasien di DKI Jakarta.',
            'mission' => 'Menyelenggarakan pelayanan kesehatan yang aman, efektif, efisien, dan berkeadilan melalui sistem manajemen mutu terintegrasi.',
        ];
    }

    /**
     * @return array<int, array{month: string, kepuasan: int, waktu_tunggu: int, infeksi: int}>
     */
    private function qualityTrends(string $orgId, ?string $clinicId): array
    {
        $indicators = QualityIndicator::forOrganization($orgId)
            ->whereIn('indicator_code', ['KPI-01', 'KPI-02', 'KPI-03'])
            ->get()
            ->keyBy('indicator_code');

        if ($indicators->isEmpty()) {
            return $this->qualityTrendsPlaceholder();
        }

        $trends = [];
        $hasData = false;

        foreach (range(5, 0) as $monthsAgo) {
            $month = now()->subMonths($monthsAgo)->startOfMonth();
            $measurementDate = $month->toDateString();

            $kepuasan = $this->measurementForMonth($indicators->get('KPI-01'), $measurementDate, $clinicId);
            $waktu = $this->measurementForMonth($indicators->get('KPI-02'), $measurementDate, $clinicId);
            $infeksi = $this->measurementForMonth($indicators->get('KPI-03'), $measurementDate, $clinicId);

            if ($kepuasan !== null || $waktu !== null || $infeksi !== null) {
                $hasData = true;
            }

            $kpi02 = $indicators->get('KPI-02');
            $waktuChart = $waktu !== null && $kpi02
                ? (int) min(100, max(0, round(((float) $kpi02->target_value / max((float) $waktu, 0.01)) * 100)))
                : 80;

            $trends[] = [
                'month' => $month->format("M 'y"),
                'kepuasan' => $kepuasan !== null ? (int) min(100, max(0, round($kepuasan))) : 80,
                'waktu_tunggu' => $waktuChart,
                'infeksi' => $infeksi !== null ? (int) round($infeksi) : 0,
            ];
        }

        return $hasData ? $trends : $this->qualityTrendsPlaceholder();
    }

    private function measurementForMonth(?QualityIndicator $indicator, string $measurementDate, ?string $clinicId): ?float
    {
        if (! $indicator) {
            return null;
        }

        $query = QualityMeasurement::query()
            ->where('quality_indicator_id', $indicator->id)
            ->whereDate('measurement_date', $measurementDate);

        if ($clinicId) {
            $query->where('clinic_id', $clinicId);
        }

        $value = $query->value('value');

        return $value !== null ? (float) $value : null;
    }

    /**
     * @return array<int, array{month: string, kepuasan: int, waktu_tunggu: int, infeksi: int}>
     */
    private function qualityTrendsPlaceholder(): array
    {
        return [
            ['month' => "Des '24", 'kepuasan' => 82, 'waktu_tunggu' => 75, 'infeksi' => 12],
            ['month' => "Jan '25", 'kepuasan' => 84, 'waktu_tunggu' => 78, 'infeksi' => 10],
            ['month' => "Feb '25", 'kepuasan' => 83, 'waktu_tunggu' => 80, 'infeksi' => 9],
            ['month' => "Mar '25", 'kepuasan' => 86, 'waktu_tunggu' => 82, 'infeksi' => 8],
            ['month' => "Apr '25", 'kepuasan' => 88, 'waktu_tunggu' => 85, 'infeksi' => 7],
            ['month' => "Mei '25", 'kepuasan' => 90, 'waktu_tunggu' => 88, 'infeksi' => 6],
        ];
    }

    /**
     * @return array<int, array{name: string, task: string, category: string, due: string, status: string, initials: string}>
     */
    private function clinicTasks(string $orgId, ?string $clinicId): array
    {
        $tasks = [];

        $capas = CapaCase::forOrganization($orgId)
            ->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
            ->whereNotIn('status', ['closed'])
            ->with('owner')
            ->orderBy('target_close_date')
            ->limit(3)
            ->get();

        foreach ($capas as $capa) {
            $tasks[] = [
                'name' => $capa->owner?->name ?? 'Tim Mutu',
                'task' => $capa->case_number,
                'category' => 'Tindak Lanjut',
                'due' => $capa->target_close_date->format('d M Y'),
                'status' => $capa->target_close_date->isPast() ? 'overdue' : 'in_progress',
                'initials' => strtoupper(substr($capa->owner?->name ?? 'TM', 0, 2)),
            ];
        }

        $audits = AuditSchedule::forOrganization($orgId)
            ->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
            ->whereIn('status', ['scheduled', 'ongoing'])
            ->orderBy('scheduled_start')
            ->limit(2)
            ->get();

        foreach ($audits as $audit) {
            $tasks[] = [
                'name' => 'Tim Audit',
                'task' => 'Persiapan audit: '.$audit->title,
                'category' => 'Audit',
                'due' => $audit->scheduled_start?->format('d M Y') ?? '—',
                'status' => 'todo',
                'initials' => 'TA',
            ];
        }

        $docs = Document::forOrganization($orgId)
            ->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
            ->where('status', 'review')
            ->orderByDesc('updated_at')
            ->limit(2)
            ->get();

        foreach ($docs as $doc) {
            $tasks[] = [
                'name' => 'Admin Dokumen',
                'task' => 'Review dokumen: '.$doc->title,
                'category' => 'Dokumen',
                'due' => $doc->expiry_date?->format('d M Y') ?? now()->addDays(7)->format('d M Y'),
                'status' => 'in_progress',
                'initials' => 'AD',
            ];
        }

        if (empty($tasks)) {
            $tasks[] = [
                'name' => 'Tim Mutu',
                'task' => 'Review kesiapan akreditasi',
                'category' => 'Akreditasi',
                'due' => now()->addWeek()->format('d M Y'),
                'status' => 'todo',
                'initials' => 'TM',
            ];
        }

        return array_slice($tasks, 0, 5);
    }
}
