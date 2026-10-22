<?php

namespace App\Services;

use App\Models\AuditExecution;
use App\Models\AuditFinding;
use App\Models\CapaCase;
use App\Models\ChecklistResponse;
use App\Models\StandardCriterion;
use App\Models\User;
use Illuminate\Support\Str;

class AuditCapaService
{
    public function generateFindingCode(User $user): string
    {
        $year = now()->format('Y');
        $seq = AuditFinding::where('organization_id', $user->organization_id)
            ->whereYear('created_at', $year)
            ->count() + 1;

        return sprintf('TMN-%s-%04d', $year, $seq);
    }

    /**
     * Membuat temuan audit dari baris tabel pemeriksaan (EP yang sudah dinilai).
     *
     * Kondisi diambil dari kolom FAKTA DAN ANALISIS, persyaratan dari redaksi
     * EP, dan rekomendasi dari kolom REKOMENDASI pada tabel instrumen.
     */
    public function createFromCriterion(
        AuditExecution $execution,
        StandardCriterion $criterion,
        ?ChecklistResponse $response,
        array $data
    ): AuditFinding {
        $user = auth()->user();

        $finding = AuditFinding::create([
            'organization_id' => $execution->organization_id,
            'audit_execution_id' => $execution->id,
            'clinic_id' => $execution->clinic_id,
            'finding_code' => $this->generateFindingCode($user),
            'title' => Str::limit($criterion->title, 200),
            'severity' => $data['severity'] ?? 'minor',
            'finding_type' => $data['finding_type'] ?? 'non_conformity',
            'description' => $response?->facts_analysis,
            'condition' => $response?->facts_analysis,
            'requirement' => $criterion->requirement_text ?: $criterion->title,
            'objective_evidence_summary' => $criterion->evidence_guidance,
            'recommendation' => $response?->recommendation,
            'owner_user_id' => $data['owner_user_id'] ?? null,
            'assigned_to' => $data['owner_user_id'] ?? null,
            'due_at' => $data['due_at'] ?? null,
            'standard_criterion_id' => $criterion->id,
            'status' => 'draft',
            'identified_at' => now(),
            'created_by' => $data['created_by'] ?? $user?->id,
            'updated_by' => $data['created_by'] ?? $user?->id,
        ]);

        (new AuditTrailService)->log(
            'audit.finding.created_from_table',
            $finding,
            null,
            [
                'finding_code' => $finding->finding_code,
                'execution_id' => $execution->id,
                'criterion_code' => $criterion->code,
                'score' => $response?->score,
            ],
            ['actor' => $user?->name]
        );

        return $finding;
    }

    public function generateCapaNumber(User $user): string
    {
        $year = now()->format('Y');
        $seq = CapaCase::where('organization_id', $user->organization_id)
            ->whereYear('created_at', $year)
            ->count() + 1;

        return sprintf('CAPA-%s-%04d', $year, $seq);
    }

    public function createCapaFromFinding(AuditFinding $finding, User $user): CapaCase
    {
        $capa = CapaCase::create([
            'organization_id' => $finding->organization_id,
            'clinic_id' => $finding->clinic_id,
            'case_number' => $this->generateCapaNumber($user),
            'source_type' => 'audit_finding',
            'source_id' => $finding->id,
            'priority' => match ($finding->severity) {
                'critical' => 'critical',
                'major' => 'high',
                default => 'medium',
            },
            'problem_statement' => $finding->title.': '.($finding->description ?? ''),
            'status' => 'open',
            'owner_id' => $finding->assigned_to ?? $user->id,
            'target_close_date' => now()->addDays(30)->toDateString(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $finding->update(['status' => 'in_capa', 'updated_by' => $user->id]);

        return $capa;
    }
}
