<?php

namespace App\Services;

use App\Models\AuditAssignment;
use App\Models\AuditFinding;
use App\Models\ClinicalAudit;
use App\Models\User;
use App\Support\AuditWorkScope;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ClinicalAuditService
{
    public function __construct(
        private AuditCapaService $capaService,
        private DocumentNumberService $numbers,
    ) {}

    /**
     * Jadwal yang boleh dipakai untuk membuat audit klinis: sudah dipublikasikan
     * dan melibatkan pengguna yang login sebagai anggota tim.
     *
     * @return Collection<int, AuditAssignment>
     */
    public function eligibleAssignments(User $user): Collection
    {
        $query = AuditAssignment::query()
            ->where('organization_id', $user->organization_id)
            ->published()
            ->where('status', '!=', 'cancelled')
            ->with(['clinic:id,code,name', 'leadAuditor:id,name', 'teamMembers' => fn ($q) => $q->where('active', true)]);

        if (! $user->hasUnrestrictedAccess()) {
            $query->whereHas('teamMembers', function ($members) use ($user) {
                $members->where('user_id', $user->id)->where('active', true);
            });
        }

        return $query->orderByDesc('scheduled_start')->get();
    }

    public function resolveAssignment(User $user, string $assignmentId): AuditAssignment
    {
        $assignment = AuditAssignment::query()
            ->where('organization_id', $user->organization_id)
            ->whereKey($assignmentId)
            ->first();

        if (! $assignment || ! $assignment->isPublished() || $assignment->status === 'cancelled') {
            throw ValidationException::withMessages([
                'audit_assignment_id' => 'Hanya jadwal penugasan yang sudah disetujui Kepala Pusat yang dapat dipakai.',
            ]);
        }

        if (! $user->hasUnrestrictedAccess() && ! AuditWorkScope::isAssignedTo($user, $assignment)) {
            throw ValidationException::withMessages([
                'audit_assignment_id' => 'Anda tidak tercatat sebagai anggota tim pada jadwal ini.',
            ]);
        }

        if (! $assignment->clinic_id) {
            throw ValidationException::withMessages([
                'audit_assignment_id' => 'Jadwal belum memiliki klinik/unit sasaran.',
            ]);
        }

        return $assignment;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): ClinicalAudit
    {
        $assignment = $this->resolveAssignment($user, $data['audit_assignment_id']);

        $payload = $this->payloadFromAssignment($user, $assignment, $data);

        $audit = $this->insertWithUniqueNumber($user, $payload);

        $this->syncFinding($audit, $user, $data);

        return $audit;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ClinicalAudit $audit, User $user, array $data): ClinicalAudit
    {
        $audit->update(array_merge(
            $this->formFields($data),
            ['updated_by' => $user->id],
        ));

        $this->syncFinding($audit->fresh(), $user, $data);

        return $audit->fresh();
    }

    public function peekNumber(string $organizationId): string
    {
        return $this->numbers->peek($organizationId, DocumentNumberService::CLINICAL_AUDIT);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function insertWithUniqueNumber(User $user, array $payload): ClinicalAudit
    {
        return $this->numbers->insertWithAllocatedNumber(
            $user->organization_id,
            DocumentNumberService::CLINICAL_AUDIT,
            function (string $number) use ($payload) {
                $payload['audit_number'] = $number;

                return ClinicalAudit::create($payload);
            }
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payloadFromAssignment(User $user, AuditAssignment $assignment, array $data): array
    {
        return array_merge($this->formFields($data), [
            'organization_id' => $user->organization_id,
            'audit_assignment_id' => $assignment->id,
            'clinic_id' => $assignment->clinic_id,
            'auditor_id' => $user->id,
            'title' => 'Audit Klinis '.$assignment->assignment_code.' — '.($assignment->clinic?->name ?? 'Klinik'),
            'audit_date' => now()->toDateString(),
            'status' => 'draft',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    /**
     * Hanya field formulir audit klinis. Kolom lama di luar form tidak diubah.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function formFields(array $data): array
    {
        return [
            'medical_record_ref' => $data['medical_record_ref'] ?? null,
            'nonconformity_notes' => $data['nonconformity_notes'] ?? null,
            'problem_analysis' => $data['problem_analysis'] ?? null,
        ];
    }

    /**
     * Satu temuan bersama per audit klinis bila ada ketidaksesuaian.
     * Pembaruan mengisi ulang uraian, tidak membuat baris baru.
     *
     * @param  array<string, mixed>  $data
     */
    private function syncFinding(ClinicalAudit $audit, User $user, array $data): void
    {
        $hasNonconformity = (bool) ($data['has_nonconformity'] ?? false)
            || filled($data['nonconformity_notes'] ?? $audit->nonconformity_notes);

        if (! $hasNonconformity || ! filled($audit->nonconformity_notes)) {
            return;
        }

        $code = 'TMN-'.$audit->audit_number;
        $title = 'Ketidaksesuaian audit klinis '.$audit->audit_number;
        $description = trim($audit->nonconformity_notes."\n\n".$audit->problem_analysis);
        $assignmentCode = $audit->assignment?->assignment_code;
        $requirement = 'Audit Klinis '.$audit->audit_number
            .($assignmentCode ? ' — jadwal '.$assignmentCode : '');

        $finding = AuditFinding::query()
            ->where('clinical_audit_id', $audit->id)
            ->orderBy('created_at')
            ->first();

        if ($finding) {
            if (in_array($finding->status, ['closed', 'verified'], true)) {
                return;
            }

            $finding->update([
                'title' => $finding->title ?: $title,
                'description' => $description,
                'condition' => $audit->nonconformity_notes,
                'requirement' => $finding->requirement ?: $requirement,
                'recommendation' => $audit->problem_analysis,
                'clinic_id' => $audit->clinic_id,
                'updated_by' => $user->id,
            ]);

            return;
        }

        $finding = AuditFinding::create([
            'organization_id' => $audit->organization_id,
            'clinical_audit_id' => $audit->id,
            'clinic_id' => $audit->clinic_id,
            'finding_code' => $code,
            'title' => $title,
            'severity' => 'major',
            'finding_type' => 'non_conformity',
            'description' => $description,
            'condition' => $audit->nonconformity_notes,
            'requirement' => $requirement,
            'recommendation' => $audit->problem_analysis,
            'status' => 'confirmed',
            'owner_user_id' => $audit->pic_user_id ?? $user->id,
            'identified_at' => now(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        if ($user->can('capa.manage') || $user->can('audit.action.update')) {
            $this->capaService->createCapaFromFinding($finding, $user);
        }
    }
}
