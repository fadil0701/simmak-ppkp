<?php

namespace App\Services;

use App\Models\AuditAssignment;
use App\Models\AuditTeamMember;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AuditAssignmentService
{
    public function createAssignment(array $data, User $user): AuditAssignment
    {
        return AuditAssignment::create([
            'organization_id' => $user->organization_id,
            'audit_program_id' => $data['audit_program_id'],
            'clinic_id' => $data['clinic_id'] ?? null,
            'assignment_code' => $data['assignment_code'],
            'title' => $data['title'],
            'scope_notes' => $data['scope_notes'] ?? null,
            'lead_auditor_id' => $data['lead_auditor_id'] ?? null,
            'scheduled_start' => $data['scheduled_start'],
            'scheduled_end' => $data['scheduled_end'],
            'status' => 'draft',
            'conflict_check_status' => 'pending',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    public function addTeamMember(AuditAssignment $assignment, array $data, User $user): AuditTeamMember
    {
        return AuditTeamMember::create([
            'organization_id' => $user->organization_id,
            'audit_assignment_id' => $assignment->id,
            'user_id' => $data['user_id'],
            'team_role' => $data['team_role'],
            'independence_declared' => $data['independence_declared'] ?? false,
            'independence_declared_at' => $data['independence_declared'] ? now() : null,
            'conflict_declared' => $data['conflict_declared'] ?? false,
            'conflict_notes' => $data['conflict_notes'] ?? null,
            'active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    public function declareConflict(AuditTeamMember $member, array $data, User $user): void
    {
        $member->update([
            'independence_declared' => true,
            'independence_declared_at' => now(),
            'conflict_declared' => $data['conflict_declared'] ?? false,
            'conflict_notes' => $data['conflict_notes'] ?? null,
            'updated_by' => $user->id,
        ]);

        // Setelah deklarasi independensi dan konflik, tandai assignment conflict_check_status = resolved
        // sehingga assignment dapat diaktivasi (asumsi ADR-0001: satu assignment = satu klinik).
        $member->assignment->update([
            'conflict_check_status' => 'resolved',
            'updated_by' => $user->id,
        ]);
    }

    public function activateAssignment(AuditAssignment $assignment, User $user): void
    {
        if ($assignment->status !== 'proposed') {
            throw new ModelNotFoundException('Hanya assignment yang diajukan yang dapat diaktifkan.');
        }
        if (! $assignment->clinic_id) {
            throw new ModelNotFoundException('Klinik sasaran harus ditetapkan sebelum assignment diaktifkan.');
        }
        if ($assignment->conflict_check_status !== 'resolved') {
            throw new ModelNotFoundException('Konflik kepentingan harus diselesaikan sebelum assignment diaktifkan.');
        }
        $assignment->update([
            'status' => 'active',
            'activated_at' => now(),
            'updated_by' => $user->id,
        ]);
    }

    public function submitAssignment(AuditAssignment $assignment, User $user): void
    {
        if ($assignment->status !== 'draft') {
            throw new ModelNotFoundException('Hanya assignment draft yang dapat diajukan.');
        }
        $assignment->update([
            'status' => 'proposed',
            'submitted_at' => now(),
            'updated_by' => $user->id,
        ]);
    }

    public function getAssignmentsForOrganization(string $organizationId)
    {
        return AuditAssignment::where('organization_id', $organizationId)
            ->with(['program', 'clinic', 'leadAuditor', 'teamMembers.user'])
            ->orderByDesc('created_at')
            ->get();
    }
}
