<?php

namespace App\Services;

use App\Models\AuditProgram;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AuditProgramService
{
    public function __construct(private DocumentNumberService $numbers) {}

    public function createProgram(array $data, User $user): AuditProgram
    {
        return $this->numbers->insertWithAllocatedNumber(
            $user->organization_id,
            DocumentNumberService::PROGRAM,
            function (string $code) use ($data, $user) {
                return AuditProgram::create([
                    'organization_id' => $user->organization_id,
                    'fiscal_year_id' => $data['fiscal_year_id'] ?? null,
                    'program_code' => $code,
                    'audit_type' => $data['audit_type'] ?? null,
                    'scope_notes' => $data['scope_notes'] ?? null,
                    'name' => $data['name'],
                    'objective' => $data['objective'] ?? null,
                    'status' => 'draft',
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);
            }
        );
    }

    public function submitProgram(AuditProgram $program, User $user): void
    {
        // Super admin boleh mengajukan program dari status mana pun.
        if ($program->status !== 'draft' && ! $user->hasUnrestrictedAccess()) {
            throw new AuthorizationException('Hanya program draft yang dapat diajukan.');
        }
        $program->update([
            'status' => 'submitted_for_review',
            'submitted_at' => now(),
            'updated_by' => $user->id,
        ]);
    }

    public function approveProgram(AuditProgram $program, User $user): void
    {
        // Super admin boleh menyetujui program dari status mana pun.
        if ($program->status !== 'submitted_for_review' && ! $user->hasUnrestrictedAccess()) {
            throw new AuthorizationException('Hanya program yang diajukan tinjauan yang dapat disetujui.');
        }
        if ($program->created_by === $user->id && ! $user->hasUnrestrictedAccess()) {
            throw new AuthorizationException('Tidak dapat menyetujui program yang sendiri buat (separation of duties).');
        }
        $program->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'updated_by' => $user->id,
        ]);
    }

    public function returnForRevision(AuditProgram $program, User $user, string $reason): void
    {
        // Super admin boleh mengembalikan program dari status mana pun.
        if ($program->status !== 'submitted_for_review' && ! $user->hasUnrestrictedAccess()) {
            throw new ModelNotFoundException('Hanya program yang diajukan tinjauan yang dapat dikembalikan.');
        }
        $program->update([
            'status' => 'returned_for_revision',
            'updated_by' => $user->id,
        ]);
    }

    public function scheduleProgram(AuditProgram $program, array $data, User $user): void
    {
        // Super admin boleh menjadwalkan program tanpa prasyarat status.
        if ($program->status !== 'approved' && ! $user->hasUnrestrictedAccess()) {
            throw new ModelNotFoundException('Hanya program yang disetujui yang dapat dijadwalkan.');
        }
        $program->update([
            'status' => 'scheduled',
            'updated_by' => $user->id,
        ]);
    }

    public function getProgramsForOrganization(?string $organizationId)
    {
        $query = AuditProgram::forOrganization($organizationId)
            ->withCount('schedules')
            ->orderByDesc('created_at');

        $status = request('status');
        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        $auditType = request('audit_type');
        if (is_string($auditType) && $auditType !== '') {
            $query->where('audit_type', $auditType);
        }

        return $query->paginate(15)->withQueryString();
    }
}
