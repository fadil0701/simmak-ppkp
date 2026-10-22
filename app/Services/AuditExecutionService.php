<?php

namespace App\Services;

use App\Models\AuditExecution;
use App\Models\ChecklistResponse;
use App\Models\ChecklistResponseRevision;
use App\Models\StandardCriterion;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuditExecutionService
{
    /**
     * Fields yang dijejak perubahan-nya sebagai revisi jawaban checklist (B-13).
     */
    private const REVISIONED_FIELDS = [
        'response_value', 'score', 'max_score', 'facts_analysis', 'recommendation',
        'assessment_notes', 'method_used', 'sample_description',
        'not_applicable_reason', 'not_assessable_reason',
    ];

    public function startExecution(AuditExecution $execution, User $user): void
    {
        if ($execution->status !== 'preparation') {
            throw new ModelNotFoundException('Hanya execution pada persiapan yang dapat dimulai.');
        }
        $execution->update([
            'status' => 'in_progress',
            'started_at' => now(),
            'updated_by' => $user->id,
        ]);
    }

    /**
     * Menyerahkan eksekusi untuk tinjauan. Memvalidasi kelengkapan pengisian
     * terlebih dahulu — semua kriteria pada versi instrumen wajib punya jawaban.
     *
     * @throws ValidationException|\ModelNotFoundException
     */
    public function submitExecution(AuditExecution $execution, User $user): void
    {
        if ($execution->status !== 'in_progress') {
            throw new ModelNotFoundException('Hanya execution yang sedang berjalan yang dapat diserahkan.');
        }

        $this->validateCompleteness($execution);

        $execution->update([
            'status' => 'submitted_for_review',
            'submitted_at' => now(),
            'updated_by' => $user->id,
        ]);
    }

    public function finalizeExecution(AuditExecution $execution, User $user): void
    {
        if ($execution->status !== 'submitted_for_review') {
            throw new ModelNotFoundException('Hanya execution yang diserahkan untuk tinjauan yang dapat difinalisasi.');
        }
        $execution->update([
            'status' => 'finalized',
            'finalized_at' => now(),
            'finalized_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    public function saveResponse(array $data, User $user): ChecklistResponse
    {
        return DB::transaction(function () use ($data, $user) {
            $execution = AuditExecution::findOrFail($data['audit_execution_id']);

            abort_unless($user->belongsToOrganization($execution->organization_id), 403);
            abort_unless($execution->status === 'in_progress', 403);

            $existing = ChecklistResponse::where('audit_execution_id', $execution->id)
                ->where('standard_criterion_id', $data['standard_criterion_id'])
                ->first();

            // Catat revisi hanya jika sudah ada jawaban dan ada field yang berubah (B-13).
            if ($existing && $this->hasTrackedChange($existing, $data)) {
                $this->createRevision($existing, $user);
            }

            $response = ChecklistResponse::updateOrCreate(
                [
                    'audit_execution_id' => $execution->id,
                    'standard_criterion_id' => $data['standard_criterion_id'],
                ],
                [
                    'organization_id' => $user->organization_id,
                    'response_value' => $data['response_value'] ?? null,
                    'score' => $data['score'] ?? null,
                    'max_score' => $data['max_score'] ?? null,
                    'facts_analysis' => $data['facts_analysis'] ?? null,
                    'recommendation' => $data['recommendation'] ?? null,
                    'assessment_notes' => $data['assessment_notes'] ?? null,
                    'method_used' => $data['method_used'] ?? null,
                    'sample_description' => $data['sample_description'] ?? null,
                    'not_applicable_reason' => $data['not_applicable_reason'] ?? null,
                    'not_assessable_reason' => $data['not_assessable_reason'] ?? null,
                    'assessed_by' => $user->id,
                    'assessed_at' => now(),
                    'updated_by' => $user->id,
                ]
            );

            return $response;
        });
    }

    /**
     * Menyimpan seluruh baris tabel pemeriksaan sekaligus (form tabel).
     *
     * Setiap baris berisi: skor, fakta & analisis, dan rekomendasi sesuai
     * tata letak instrumen (kolom SKOR, FAKTA DAN ANALISIS, REKOMENDASI).
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return int jumlah baris yang tersimpan
     */
    public function saveTableRows(AuditExecution $execution, array $rows, User $user): int
    {
        abort_unless($user->belongsToOrganization($execution->organization_id), 403);
        abort_unless($execution->status === 'in_progress', 403);

        $criterionIds = $this->getInstrumentCriteriaIds($execution)->all();

        $saved = 0;

        foreach ($rows as $row) {
            $criterionId = $row['standard_criterion_id'] ?? null;
            if (! $criterionId || ! in_array($criterionId, $criterionIds, true)) {
                continue;
            }

            $score = $row['score'] ?? null;
            $score = ($score === null || $score === '') ? null : (int) $score;
            $facts = $this->trimOrNull($row['facts_analysis'] ?? null);
            $recommendation = $this->trimOrNull($row['recommendation'] ?? null);

            // Baris kosong diabaikan agar tidak menimpa nilai yang sudah tersimpan.
            if ($score === null && $facts === null && $recommendation === null) {
                continue;
            }

            $criterion = StandardCriterion::find($criterionId);
            $maxScore = $criterion?->max_score ?: 10;

            $this->saveResponse([
                'audit_execution_id' => $execution->id,
                'standard_criterion_id' => $criterionId,
                'response_value' => $row['response_value'] ?? $this->deriveResponseValue($score, $maxScore),
                'score' => $score,
                'max_score' => $maxScore,
                'facts_analysis' => $facts,
                'recommendation' => $recommendation,
                'not_applicable_reason' => $this->trimOrNull($row['not_applicable_reason'] ?? null),
                'not_assessable_reason' => $this->trimOrNull($row['not_assessable_reason'] ?? null),
            ], $user);

            $saved++;
        }

        return $saved;
    }

    /**
     * Menurunkan status JH/EP dari skor yang diinput petugas.
     *
     * - Skor = skor maksimal  → Terpenuhi
     * - Skor > 0 (< maksimal)  → Terpenuhi Sebagian
     * - Skor = 0              → Tidak Terpenuhi
     * - Skor kosong           → Tidak Dapat Dinilai (bukan otomatis "tidak terpenuhi")
     */
    private function deriveResponseValue(?int $score, int $maxScore): ?string
    {
        if ($score === null) {
            return 'not_assessable';
        }

        if ($score >= $maxScore) {
            return 'compliant';
        }

        if ($score > 0) {
            return 'partial';
        }

        return 'non_compliant';
    }

    private function trimOrNull($value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * Statistik kelengkapan: total = jumlah kriteria pada versi instrumen
     * (bukan jumlah baris respons), completed = kriteria yang sudah diberi nilai.
     */
    public function getCompletionStats(AuditExecution $execution): array
    {
        $criteriaIds = $this->getInstrumentCriteriaIds($execution);

        $total = $criteriaIds->count();
        $completed = $execution->responses()
            ->whereIn('standard_criterion_id', $criteriaIds)
            ->whereNotNull('response_value')
            ->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'percentage' => $total > 0 ? round(($completed / $total) * 100) : 0,
        ];
    }

    /**
     * Daftar kriteria pada versi instrumen yang belum dijawab.
     *
     * @return Collection<int, StandardCriterion>
     */
    public function getIncompleteCriteria(AuditExecution $execution): Collection
    {
        $criteriaIds = $this->getInstrumentCriteriaIds($execution);

        if ($criteriaIds->isEmpty()) {
            return collect();
        }

        $answeredIds = $execution->responses()
            ->whereIn('standard_criterion_id', $criteriaIds)
            ->whereNotNull('response_value')
            ->pluck('standard_criterion_id');

        return StandardCriterion::whereIn('id', $criteriaIds)
            ->whereNotIn('id', $answeredIds)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Memvalidasi bahwa semua kriteria pada versi instrumen sudah dijawab.
     *
     * @throws ValidationException
     */
    public function validateCompleteness(AuditExecution $execution): void
    {
        $stats = $this->getCompletionStats($execution);

        if ($stats['total'] === 0) {
            throw ValidationException::withMessages([
                'completeness' => 'Versi instrumen tidak memiliki kriteria. Tidak dapat menyerahkan eksekusi tanpa kriteria yang dinilai.',
            ]);
        }

        if ($stats['completed'] < $stats['total']) {
            $remaining = $stats['total'] - $stats['completed'];
            throw ValidationException::withMessages([
                'completeness' => "Masih ada {$remaining} kriteria yang belum dinilai. Lengkapi seluruh kriteria sebelum menyerahkan untuk tinjauan.",
            ]);
        }
    }

    /**
     * Kumpulan ID kriteria aktif pada versi instrumen eksekusi.
     *
     * @return Collection<int, string>
     */
    private function getInstrumentCriteriaIds(AuditExecution $execution): Collection
    {
        $version = $execution->instrumentVersion;

        if (! $version) {
            return collect();
        }

        if (! $version->relationLoaded('sections')) {
            $version->load(['sections.criteria']);
        }

        return $version->sections->flatMap->criteria
            ->where('active', true)
            ->pluck('id')
            ->values();
    }

    /**
     * Memeriksa apakah ada field yang dilacak berubah pada data baru.
     */
    private function hasTrackedChange(ChecklistResponse $existing, array $data): bool
    {
        foreach (self::REVISIONED_FIELDS as $field) {
            $old = $existing->{$field};
            $new = $data[$field] ?? null;

            if ((string) ($old ?? '') !== (string) ($new ?? '')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Membuat snapshot revisi dari nilai jawaban saat ini sebelum ditimpa.
     */
    private function createRevision(ChecklistResponse $response, User $user): ChecklistResponseRevision
    {
        return ChecklistResponseRevision::create([
            'organization_id' => $response->organization_id,
            'checklist_response_id' => $response->id,
            'audit_execution_id' => $response->audit_execution_id,
            'standard_criterion_id' => $response->standard_criterion_id,
            'response_value' => $response->response_value,
            'score' => $response->score,
            'facts_analysis' => $response->facts_analysis,
            'recommendation' => $response->recommendation,
            'assessment_notes' => $response->assessment_notes,
            'method_used' => $response->method_used,
            'sample_description' => $response->sample_description,
            'not_applicable_reason' => $response->not_applicable_reason,
            'not_assessable_reason' => $response->not_assessable_reason,
            'assessed_by' => $response->assessed_by,
            'assessed_at' => $response->assessed_at,
            'revision_reason' => 'Jawaban diperbarui oleh auditor/tim audit.',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }
}
