<?php

namespace App\Services;

use App\Models\AuditAssignment;
use App\Models\AuditTeamMember;
use App\Models\User;
use App\Support\AuditWorkScope;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class AuditAssignmentService
{
    public function createAssignment(array $data, User $user): AuditAssignment
    {
        return DB::transaction(function () use ($data, $user) {
            $assignment = AuditAssignment::create([
                'organization_id' => $user->organization_id,
                'audit_program_id' => $data['audit_program_id'],
                'clinic_id' => $data['clinic_id'] ?? null,
                'instrument_version_id' => $data['instrument_version_id'] ?? null,
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

            $this->ensureLeadOnTeam($assignment, $user);
            $this->refreshConflictStatus($assignment, $user);

            return $assignment->fresh(['program', 'clinic', 'leadAuditor', 'teamMembers']);
        });
    }

    public function updateAssignment(AuditAssignment $assignment, array $data, User $user): AuditAssignment
    {
        return DB::transaction(function () use ($assignment, $data, $user) {
            $assignment->update(array_merge($data, ['updated_by' => $user->id]));
            $this->ensureLeadOnTeam($assignment->fresh(), $user);
            $this->refreshConflictStatus($assignment->fresh(), $user);

            return $assignment->fresh();
        });
    }

    public function addTeamMember(AuditAssignment $assignment, array $data, User $user): AuditTeamMember
    {
        $existing = AuditTeamMember::withTrashed()
            ->where('audit_assignment_id', $assignment->id)
            ->where('user_id', $data['user_id'])
            ->first();

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }

            $existing->update([
                'team_role' => $data['team_role'],
                'active' => true,
                'updated_by' => $user->id,
            ]);

            $this->refreshConflictStatus($assignment, $user);

            return $existing->fresh();
        }

        $member = AuditTeamMember::create([
            'organization_id' => $user->organization_id,
            'audit_assignment_id' => $assignment->id,
            'user_id' => $data['user_id'],
            'team_role' => $data['team_role'],
            'independence_declared' => $data['independence_declared'] ?? false,
            'independence_declared_at' => ($data['independence_declared'] ?? false) ? now() : null,
            'conflict_declared' => $data['conflict_declared'] ?? false,
            'conflict_notes' => $data['conflict_notes'] ?? null,
            'active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->refreshConflictStatus($assignment, $user);

        return $member;
    }

    public function declareConflict(AuditTeamMember $member, array $data, User $user): void
    {
        $conflictDeclared = (bool) ($data['conflict_declared'] ?? false);

        $member->update([
            'independence_declared' => true,
            'independence_declared_at' => now(),
            'conflict_declared' => $conflictDeclared,
            'conflict_notes' => $data['conflict_notes'] ?? null,
            'updated_by' => $user->id,
        ]);

        $this->refreshConflictStatus($member->assignment, $user);
    }

    public function activateAssignment(AuditAssignment $assignment, User $user): void
    {
        // Super admin boleh mengaktifkan penugasan dari status mana pun.
        if ($assignment->status !== 'proposed' && ! $user->hasUnrestrictedAccess()) {
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
        // Super admin boleh mengajukan penugasan dari status mana pun.
        if ($assignment->status !== 'draft' && ! $user->hasUnrestrictedAccess()) {
            throw new ModelNotFoundException('Hanya assignment draft yang dapat diajukan.');
        }
        if (! $assignment->clinic_id) {
            throw new ModelNotFoundException('Klinik sasaran harus ditetapkan sebelum penugasan diajukan.');
        }
        if (! $assignment->lead_auditor_id) {
            throw new ModelNotFoundException('Ketua tim auditor harus ditetapkan sebelum penugasan diajukan.');
        }
        if ($assignment->teamMembers()->where('active', true)->doesntExist()) {
            throw new ModelNotFoundException('Tim auditor harus memiliki paling sedikit satu anggota.');
        }

        $assignment->update([
            'status' => 'proposed',
            'submitted_at' => now(),
            'updated_by' => $user->id,
        ]);
    }

    public function getAssignmentsForOrganization(?string $organizationId)
    {
        return AuditAssignment::forOrganization($organizationId)
            ->with(['program', 'clinic', 'leadAuditor', 'teamMembers.user'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function getAssignmentsForUser(User $user, bool $upcomingOnly = false)
    {
        $query = AuditWorkScope::assignments(AuditAssignment::query(), $user)
            ->with([
                'program:id,name',
                'clinic:id,name,city',
                'leadAuditor:id,name,employee_id',
                'leadAuditor.employee:id,employee_number,full_name,position',
                'teamMembers' => fn ($q) => $q->where('active', true),
                'teamMembers.user:id,name,employee_id',
                'instrumentVersion.standard:id,title',
                'executions.instrumentVersion.standard:id,title',
            ])
            ->orderByDesc('created_at');

        if ($upcomingOnly) {
            $query->whereIn('status', ['active', 'proposed'])
                ->whereDate('scheduled_end', '>=', now()->toDateString())
                ->reorder()
                ->orderBy('scheduled_start');
        }

        return $query->paginate(15)->withQueryString();
    }

    public function ensureLeadOnTeam(AuditAssignment $assignment, User $user): void
    {
        if (! $assignment->lead_auditor_id) {
            return;
        }

        $existing = AuditTeamMember::withTrashed()
            ->where('audit_assignment_id', $assignment->id)
            ->where('user_id', $assignment->lead_auditor_id)
            ->first();

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }

            $existing->update([
                'team_role' => 'lead_auditor',
                'active' => true,
                'updated_by' => $user->id,
            ]);

            return;
        }

        AuditTeamMember::create([
            'organization_id' => $assignment->organization_id,
            'audit_assignment_id' => $assignment->id,
            'user_id' => $assignment->lead_auditor_id,
            'team_role' => 'lead_auditor',
            'independence_declared' => false,
            'conflict_declared' => false,
            'active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    public function refreshConflictStatus(AuditAssignment $assignment, User $user): void
    {
        $members = $assignment->teamMembers()->where('active', true)->get();

        if ($members->isEmpty()) {
            $status = 'pending';
        } elseif ($members->contains(fn (AuditTeamMember $member) => $member->conflict_declared)) {
            $status = 'blocked';
        } elseif ($members->every(fn (AuditTeamMember $member) => $member->independence_declared)) {
            $status = 'resolved';
        } elseif ($members->contains(fn (AuditTeamMember $member) => $member->independence_declared)) {
            $status = 'declared';
        } else {
            $status = 'pending';
        }

        $assignment->update([
            'conflict_check_status' => $status,
            'updated_by' => $user->id,
        ]);
    }
}
