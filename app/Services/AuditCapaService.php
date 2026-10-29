<?php

namespace App\Services;

use App\Models\AuditExecution;
use App\Models\AuditFinding;
use App\Models\CapaAction;
use App\Models\CapaCase;
use App\Models\CapaVerification;
use App\Models\ChecklistResponse;
use App\Models\StandardCriterion;
use App\Models\User;
use App\Support\SimmakRoles;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AuditCapaService
{
    public function __construct(private DocumentNumberService $numbers) {}

    public function generateFindingCode(User $user): string
    {
        return $this->numbers->allocate($user->organization_id, DocumentNumberService::FINDING);
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
            'description' => $response?->facts_analysis ?: $response?->recommendation,
            'condition' => $response?->facts_analysis ?: $response?->recommendation,
            'requirement' => $criterion->requirement_text ?: $criterion->title,
            'objective_evidence_summary' => $criterion->evidence_guidance,
            'recommendation' => $response?->recommendation,
            'owner_user_id' => $data['owner_user_id'] ?? null,
            'assigned_to' => $data['owner_user_id'] ?? null,
            'due_at' => $data['due_at'] ?? null,
            'standard_criterion_id' => $criterion->id,
            'status' => $data['status'] ?? 'in_capa',
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
        return $this->numbers->allocate($user->organization_id, DocumentNumberService::CAPA);
    }

    public function createCapaFromFinding(AuditFinding $finding, User $user): CapaCase
    {
        $existing = CapaCase::where('source_type', 'audit_finding')->where('source_id', $finding->id)->first();
        if ($existing) {
            return $existing;
        }

        $ownerId = $finding->assigned_to
            ?? $this->resolveClinicFollowUpOwner($finding)
            ?? $finding->owner_user_id
            ?? $user->id;

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
            'problem_statement' => $finding->title.': '.($finding->description ?? $finding->recommendation ?? ''),
            'status' => 'action',
            'owner_id' => $ownerId,
            'target_close_date' => ($finding->due_at?->toDateString()) ?? now()->addDays(30)->toDateString(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->addAction($capa, [
            'action_type' => 'corrective',
            'description' => $finding->recommendation ?: 'Tindak lanjuti temuan sesuai rekomendasi auditor.',
            'assigned_to' => $ownerId,
            'due_date' => $capa->target_close_date?->toDateString(),
        ], $user);

        $finding->update([
            'status' => 'in_capa',
            'assigned_to' => $finding->assigned_to ?? $ownerId,
            'updated_by' => $user->id,
        ]);

        return $capa;
    }

    public function resolveClinicFollowUpOwner(AuditFinding $finding): ?string
    {
        if (! $finding->clinic_id) {
            return null;
        }

        return User::query()
            ->where('organization_id', $finding->organization_id)
            ->where('is_active', true)
            ->whereHas('roles', function ($query) {
                // 'staff' = alias lama auditi (SimmakRoles::legacyMap()); tetap
                // disertakan agar akun existing tidak kehilangan penanggung jawab.
                $query->whereIn('name', [
                    SimmakRoles::AUDITI,
                    SimmakRoles::ADMIN_KLINIK,
                    SimmakRoles::KEPALA_KLINIK,
                    'staff',
                ]);
            })
            ->whereHas('clinics', function ($query) use ($finding) {
                $query->where('clinics.id', $finding->clinic_id);
            })
            ->orderBy('name')
            ->value('id');
    }

    public function addAction(CapaCase $capa, array $data, User $user): CapaAction
    {
        // Super admin boleh menambah tindakan pada kasus yang sudah ditutup.
        if ($capa->status === 'closed' && ! $user->hasUnrestrictedAccess()) {
            throw new InvalidArgumentException('Kasus tindak lanjut yang sudah ditutup tidak dapat ditambah tindakan.');
        }

        $action = CapaAction::create([
            'capa_case_id' => $capa->id,
            'action_type' => $data['action_type'],
            'description' => $data['description'],
            'assigned_to' => $data['assigned_to'] ?? $capa->owner_id,
            'due_date' => $data['due_date'] ?? null,
            'status' => 'pending',
        ]);

        if (in_array($capa->status, ['open', 'analysis'], true)) {
            $capa->update(['status' => 'action', 'updated_by' => $user->id]);
        }

        (new AuditTrailService)->log(
            'capa.action_created',
            $capa,
            null,
            ['action_id' => $action->id, 'action_type' => $action->action_type],
            ['actor' => $user->name]
        );

        return $action;
    }

    public function submitActionEvidence(CapaAction $action, array $data, User $user): void
    {
        $capa = $action->capaCase;
        // Super admin boleh mengirim bukti pada kasus yang sudah ditutup.
        if ((! $capa || $capa->status === 'closed') && ! $user->hasUnrestrictedAccess()) {
            throw new InvalidArgumentException('Bukti tidak dapat dikirim pada kasus yang ditutup.');
        }

        $action->update([
            'evidence_notes' => $data['evidence_notes'] ?? $action->evidence_notes,
            'evidence_submitted_at' => now(),
            'status' => 'evidence_submitted',
            'completed_at' => now(),
        ]);

        $capa->update(['status' => 'verification', 'updated_by' => $user->id]);

        if ($capa->sourceFinding && $capa->sourceFinding->status === 'in_capa') {
            $capa->sourceFinding->update([
                'status' => 'pending_verification',
                'updated_by' => $user->id,
            ]);
        }

        (new AuditTrailService)->log(
            'capa.evidence_submitted',
            $capa,
            null,
            ['action_id' => $action->id, 'status' => 'evidence_submitted'],
            ['actor' => $user->name]
        );
    }

    public function recordVerification(CapaCase $capa, array $data, User $user): CapaVerification
    {
        return DB::transaction(function () use ($capa, $data, $user) {
            $result = $data['result'];
            $effective = $result === 'effective';

            $verification = CapaVerification::create([
                'organization_id' => $capa->organization_id,
                'capa_case_id' => $capa->id,
                'verification_date' => $data['verification_date'],
                'result' => $result,
                'notes' => $data['notes'] ?? null,
                'is_effective' => $effective,
                'verified_by' => $user->id,
            ]);

            if ($effective) {
                $capa->update([
                    'status' => 'closed',
                    'closed_at' => now()->toDateString(),
                    'updated_by' => $user->id,
                ]);
                $capa->actions()->where('status', 'evidence_submitted')->update(['status' => 'verified']);
                if ($capa->sourceFinding && $capa->sourceFinding->status !== 'closed') {
                    $capa->sourceFinding->update([
                        'status' => 'closed',
                        'closed_at' => now(),
                        'updated_by' => $user->id,
                    ]);
                }
            } else {
                $newDue = $data['new_target_close_date'] ?? $capa->target_close_date?->toDateString();
                $capa->update([
                    'status' => 'action',
                    'target_close_date' => $newDue,
                    'closed_at' => null,
                    'updated_by' => $user->id,
                ]);
                $capa->actions()->whereIn('status', ['evidence_submitted', 'verified'])->update([
                    'status' => 'needs_further_action',
                    'completed_at' => null,
                    'evidence_submitted_at' => null,
                ]);
                if ($capa->sourceFinding && $capa->sourceFinding->status !== 'closed') {
                    $capa->sourceFinding->update([
                        'status' => 'in_capa',
                        'updated_by' => $user->id,
                    ]);
                }
            }

            (new AuditTrailService)->log(
                'capa.verified',
                $capa,
                null,
                [
                    'result' => $result,
                    'is_effective' => $effective,
                    'capa_case_id' => $capa->id,
                    'new_target_close_date' => $capa->fresh()->target_close_date?->toDateString(),
                ],
                ['actor' => $user->name]
            );

            return $verification;
        });
    }
}
