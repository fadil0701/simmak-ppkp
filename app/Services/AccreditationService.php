<?php

namespace App\Services;

use App\Models\AccreditationChapter;
use App\Models\AccreditationEvidence;
use App\Models\AccreditationGapItem;
use App\Models\AccreditationProgram;
use App\Models\AccreditationSelfAssessment;
use App\Models\AccreditationSelfAssessmentItem;
use App\Models\Clinic;
use Carbon\Carbon;

class AccreditationService
{
    public function dashboardData(string $organizationId, ?Clinic $clinic): array
    {
        $program = $this->resolveProgram($organizationId, $clinic);

        if (! $program) {
            return $this->emptyDashboard($clinic);
        }

        $program->load(['type', 'clinic', 'latestSelfAssessment', 'latestMockSurvey']);

        $assessment = $program->latestSelfAssessment;
        $mockSurvey = $program->latestMockSurvey;

        $chapterProgress = $this->chapterProgress($program, $assessment);
        $gapSummary = $this->gapSummary($organizationId, $program->id);
        $evidenceStats = $this->evidenceStats($organizationId, $program->id);

        $selfScore = (float) ($assessment?->overall_score ?? 0);
        $mockScore = (float) ($mockSurvey?->score ?? 0);
        $evidencePct = $evidenceStats['pct'];
        $readiness = round(($selfScore * 0.4) + ($mockScore * 0.35) + ($evidencePct * 0.25), 0);

        $daysToSurvey = max(0, now()->diffInDays(Carbon::parse($program->target_survey_date), false));

        return [
            'clinic' => $program->clinic,
            'program' => $program,
            'accreditation_type' => $program->type?->code ?? 'FKTP',
            'readiness' => (int) $readiness,
            'chapter_progress' => $chapterProgress,
            'gap_summary' => $gapSummary,
            'self_assessment' => [
                'score' => (int) round($selfScore),
                'started_at' => $program->start_date?->format('d M Y'),
                'updated_at' => $assessment?->updated_at?->format('d M Y'),
                'status' => $assessment?->status ?? 'belum_dimulai',
            ],
            'mock_survey' => [
                'score' => (int) round($mockScore),
                'survey_date' => $mockSurvey?->survey_date?->format('d M Y'),
                'surveyor' => $mockSurvey?->surveyor_name ?? '—',
                'status' => $mockSurvey?->status ?? 'belum_dijadwalkan',
            ],
            'evidence' => $evidenceStats,
            'timeline' => $this->timeline($program),
            'days_to_survey' => $daysToSurvey,
            'activities' => $this->recentActivities($organizationId, $program->id, $assessment, $mockSurvey),
            'structure' => $this->structureTree($program->accreditation_type_id, $chapterProgress),
        ];
    }

    private function resolveProgram(string $organizationId, ?Clinic $clinic): ?AccreditationProgram
    {
        $query = AccreditationProgram::forOrganization($organizationId)
            ->with(['type', 'clinic'])
            ->whereNotIn('status', ['accredited']);

        if ($clinic) {
            $program = (clone $query)->where('clinic_id', $clinic->id)->latest()->first();
            if ($program) {
                return $program;
            }
        }

        return $query->latest()->first();
    }

    /**
     * @return array<int, array{number: string, title: string, pct: int, color: string}>
     */
    private function chapterProgress(AccreditationProgram $program, ?AccreditationSelfAssessment $assessment): array
    {
        $chapters = AccreditationChapter::query()
            ->where('accreditation_type_id', $program->accreditation_type_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        if (! $assessment) {
            return $chapters->map(fn ($ch) => [
                'number' => $ch->chapter_number,
                'title' => $ch->title,
                'pct' => 0,
                'color' => 'bg-slate-300',
            ])->all();
        }

        $scoresByChapter = AccreditationSelfAssessmentItem::query()
            ->where('self_assessment_id', $assessment->id)
            ->join('accreditation_standards', 'accreditation_standards.id', '=', 'accreditation_self_assessment_items.standard_id')
            ->join('accreditation_chapters', 'accreditation_chapters.id', '=', 'accreditation_standards.chapter_id')
            ->selectRaw('accreditation_chapters.id as chapter_id, AVG(accreditation_self_assessment_items.score) as avg_score')
            ->groupBy('accreditation_chapters.id')
            ->pluck('avg_score', 'chapter_id');

        return $chapters->map(function ($ch) use ($scoresByChapter) {
            $pct = (int) round((float) ($scoresByChapter[$ch->id] ?? 0));

            return [
                'number' => $ch->chapter_number,
                'title' => $ch->title,
                'pct' => $pct,
                'color' => $pct >= 80 ? 'bg-teal-500' : ($pct >= 70 ? 'bg-amber-400' : 'bg-orange-400'),
            ];
        })->all();
    }

    /**
     * @return array{critical: int, major: int, minor: int, total: int}
     */
    private function gapSummary(string $organizationId, string $programId): array
    {
        $gaps = AccreditationGapItem::forOrganization($organizationId)
            ->where('program_id', $programId)
            ->where('status', '!=', 'closed')
            ->selectRaw('severity, COUNT(*) as total')
            ->groupBy('severity')
            ->pluck('total', 'severity');

        $critical = (int) ($gaps['critical'] ?? 0);
        $major = (int) ($gaps['major'] ?? 0);
        $minor = (int) ($gaps['minor'] ?? 0);

        return [
            'critical' => $critical,
            'major' => $major,
            'minor' => $minor,
            'total' => $critical + $major + $minor,
        ];
    }

    /**
     * @return array{total: int, uploaded: int, pct: int}
     */
    private function evidenceStats(string $organizationId, string $programId): array
    {
        $total = AccreditationEvidence::forOrganization($organizationId)
            ->where('program_id', $programId)
            ->count();

        $uploaded = AccreditationEvidence::forOrganization($organizationId)
            ->where('program_id', $programId)
            ->whereIn('status', ['uploaded', 'verified'])
            ->count();

        $pct = $total > 0 ? (int) round(($uploaded / $total) * 100) : 0;

        return compact('total', 'uploaded', 'pct');
    }

    /**
     * @return array<int, array{label: string, status: string, date: ?string, icon: string}>
     */
    private function timeline(AccreditationProgram $program): array
    {
        $status = $program->status;
        $hasAssessment = (bool) $program->latestSelfAssessment;
        $hasMock = (bool) $program->latestMockSurvey;

        $prepDone = in_array($status, ['self_assessment', 'mock', 'survey', 'accredited'], true) || $hasAssessment;
        $saDone = in_array($status, ['mock', 'survey', 'accredited'], true)
            || ($hasAssessment && in_array($program->latestSelfAssessment?->status, ['completed', 'submitted', 'reviewed'], true));
        $saActive = ! $saDone && ($status === 'self_assessment' || ($hasAssessment && ! $saDone));
        $mockDone = in_array($status, ['survey', 'accredited'], true)
            || ($hasMock && in_array($program->latestMockSurvey?->status, ['completed', 'done'], true));
        $mockActive = ! $mockDone && ($status === 'mock' || ($hasMock && ! $mockDone));
        $fixDone = in_array($status, ['survey', 'accredited'], true);
        $fixActive = ! $fixDone && $mockDone;
        $surveyDone = $status === 'accredited';
        $surveyActive = $status === 'survey';

        return [
            [
                'label' => 'Persiapan',
                'status' => $prepDone ? 'done' : ($status === 'planning' ? 'active' : 'pending'),
                'date' => $program->start_date?->format('d M Y'),
                'icon' => 'check',
            ],
            [
                'label' => 'Self Assessment',
                'status' => $saDone ? 'done' : ($saActive ? 'active' : 'pending'),
                'date' => $program->latestSelfAssessment?->updated_at?->format('d M Y'),
                'icon' => 'check',
            ],
            [
                'label' => 'Survei Simulasi',
                'status' => $mockDone ? 'done' : ($mockActive ? 'active' : 'pending'),
                'date' => $program->latestMockSurvey?->survey_date?->format('d M Y'),
                'icon' => 'circle',
            ],
            [
                'label' => 'Perbaikan & Tindak Lanjut',
                'status' => $fixDone ? 'done' : ($fixActive ? 'active' : 'pending'),
                'date' => null,
                'icon' => 'circle',
            ],
            [
                'label' => 'Survei Akreditasi',
                'status' => $surveyDone ? 'done' : ($surveyActive ? 'active' : 'pending'),
                'date' => $program->target_survey_date?->format('d M Y'),
                'icon' => 'star',
            ],
        ];
    }

    /**
     * @return array<int, array{icon: string, text: string, time: string}>
     */
    private function recentActivities(
        string $organizationId,
        string $programId,
        ?AccreditationSelfAssessment $assessment,
        mixed $mockSurvey,
    ): array {
        $activities = [];

        $evidence = AccreditationEvidence::forOrganization($organizationId)
            ->where('program_id', $programId)
            ->whereIn('status', ['uploaded', 'verified'])
            ->latest()
            ->limit(2)
            ->get();

        foreach ($evidence as $item) {
            $activities[] = [
                'icon' => 'upload',
                'text' => 'Unggah bukti: '.$item->title,
                'time' => $item->updated_at->diffForHumans(),
                'sort' => $item->updated_at?->timestamp ?? 0,
            ];
        }

        $gaps = AccreditationGapItem::forOrganization($organizationId)
            ->where('program_id', $programId)
            ->latest()
            ->limit(2)
            ->get();

        foreach ($gaps as $gap) {
            $activities[] = [
                'icon' => 'alert',
                'text' => 'Tindak lanjut kesenjangan '.ucfirst($gap->severity),
                'time' => $gap->updated_at->diffForHumans(),
                'sort' => $gap->updated_at?->timestamp ?? 0,
            ];
        }

        if ($assessment) {
            $activities[] = [
                'icon' => 'doc',
                'text' => 'Pembaruan Self Assessment',
                'time' => $assessment->updated_at->diffForHumans(),
                'sort' => $assessment->updated_at?->timestamp ?? 0,
            ];
        }

        if ($mockSurvey) {
            $activities[] = [
                'icon' => 'check',
                'text' => 'Survei Simulasi — skor '.((int) round((float) $mockSurvey->score)).'%',
                'time' => $mockSurvey->updated_at?->diffForHumans() ?? '—',
                'sort' => $mockSurvey->updated_at?->timestamp ?? 0,
            ];
        }

        usort($activities, fn ($a, $b) => ($b['sort'] ?? 0) <=> ($a['sort'] ?? 0));

        $activities = array_map(function (array $row) {
            unset($row['sort']);

            return $row;
        }, $activities);

        if ($activities === []) {
            return [
                ['icon' => 'doc', 'text' => 'Program akreditasi dimulai', 'time' => 'baru saja'],
            ];
        }

        return array_slice($activities, 0, 4);
    }

    /**
     * @param  array<int, array{number: string, title: string, pct: int, color: string}>  $chapterProgress
     * @return array<int, array{number: string, title: string, pct: int, status: string, expanded: bool, standards: array}>
     */
    private function structureTree(string $typeId, array $chapterProgress = []): array
    {
        $pctByNumber = collect($chapterProgress)->keyBy('number');

        return AccreditationChapter::query()
            ->where('accreditation_type_id', $typeId)
            ->where('is_active', true)
            ->with(['standards' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get()
            ->values()
            ->map(function ($ch, int $index) use ($pctByNumber) {
                $pct = (int) ($pctByNumber[$ch->chapter_number]['pct'] ?? 0);
                $status = $pct >= 80 ? 'good' : ($pct >= 60 ? 'warn' : 'low');

                return [
                    'number' => $ch->chapter_number,
                    'title' => $ch->title,
                    'pct' => $pct,
                    'status' => $status,
                    'expanded' => $index === 0,
                    'standards' => $ch->standards->map(fn ($s) => [
                        'code' => $s->standard_code,
                        'title' => $s->title,
                    ])->all(),
                ];
            })
            ->all();
    }

    private function emptyDashboard(?Clinic $clinic): array
    {
        return [
            'clinic' => $clinic,
            'program' => null,
            'accreditation_type' => $clinic?->accreditation_type ?? 'FKTP',
            'readiness' => 0,
            'chapter_progress' => [],
            'gap_summary' => ['critical' => 0, 'major' => 0, 'minor' => 0, 'total' => 0],
            'self_assessment' => ['score' => 0, 'started_at' => null, 'updated_at' => null, 'status' => 'belum_dimulai'],
            'mock_survey' => ['score' => 0, 'survey_date' => null, 'surveyor' => '—', 'status' => 'belum_dijadwalkan'],
            'evidence' => ['total' => 0, 'uploaded' => 0, 'pct' => 0],
            'timeline' => [],
            'days_to_survey' => 0,
            'activities' => [['icon' => 'doc', 'text' => 'Belum ada program akreditasi aktif', 'time' => '—']],
            'structure' => [],
        ];
    }
}
