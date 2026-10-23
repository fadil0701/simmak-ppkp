<?php

namespace Tests\Feature\Audit;

use App\Models\AuditAssignment;
use App\Models\AuditExecution;
use App\Models\AuditFinding;
use App\Models\AuditProgram;
use App\Models\AuditTeamMember;
use App\Models\CapaAction;
use App\Models\CapaCase;
use App\Models\Clinic;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AssignmentAndFollowUpScopeTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;

    protected Clinic $clinicA;

    protected Clinic $clinicB;

    protected User $auditor;

    protected User $otherAuditor;

    protected User $auditi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Organization::create([
            'code' => 'PPKP-DKI',
            'name' => 'PPKP DKI',
            'org_type' => 'central',
            'is_active' => true,
        ]);

        $this->clinicA = Clinic::create([
            'organization_id' => $this->org->id,
            'code' => 'KA',
            'name' => 'Klinik A',
            'clinic_type' => 'pratama',
            'is_main_clinic' => false,
            'is_auditable' => true,
            'is_active' => true,
        ]);
        $this->clinicB = Clinic::create([
            'organization_id' => $this->org->id,
            'code' => 'KB',
            'name' => 'Klinik B',
            'clinic_type' => 'pratama',
            'is_main_clinic' => false,
            'is_auditable' => true,
            'is_active' => true,
        ]);

        foreach ([
            'audit.view', 'audit.assignment.view', 'audit.execution.view',
            'audit.execution.execute', 'audit.execution.submit',
            'audit.finding.view', 'audit.finding.create', 'audit.finding.close',
            'audit.action.view', 'audit.action.update', 'audit.capa.verify',
            'capa.view', 'capa.manage',
        ] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $auditorRole = Role::firstOrCreate(['name' => 'auditor', 'guard_name' => 'web']);
        $auditorRole->syncPermissions([
            'audit.view', 'audit.assignment.view', 'audit.execution.view',
            'audit.execution.execute', 'audit.execution.submit',
            'audit.finding.view', 'audit.finding.create',
            'audit.action.view', 'audit.action.update', 'audit.capa.verify',
            'capa.view',
        ]);

        $auditiRole = Role::firstOrCreate(['name' => 'auditi', 'guard_name' => 'web']);
        $auditiRole->syncPermissions([
            'audit.view', 'audit.finding.view', 'audit.action.view', 'audit.action.update',
            'capa.view', 'capa.manage',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->auditor = $this->makeUser('auditor-a@test.local', 'auditor', $this->clinicA);
        $this->otherAuditor = $this->makeUser('auditor-b@test.local', 'auditor', $this->clinicB);
        $this->auditi = $this->makeUser('auditi-a@test.local', 'auditi', $this->clinicA);
    }

    protected function makeUser(string $email, string $role, Clinic $clinic): User
    {
        $user = User::create([
            'organization_id' => $this->org->id,
            'name' => $email,
            'email' => $email,
            'password' => bcrypt('password'),
            'auth_provider' => 'local',
            'is_active' => true,
            'default_clinic_id' => $clinic->id,
        ]);
        $user->clinics()->attach($clinic->id, ['is_default' => true]);
        $user->assignRole($role);

        return $user;
    }

    protected function makeAssignedExecution(User $auditor, Clinic $clinic): AuditExecution
    {
        $program = AuditProgram::create([
            'organization_id' => $this->org->id,
            'program_code' => 'PROG-'.$clinic->code,
            'name' => 'Program '.$clinic->code,
            'status' => 'approved',
            'created_by' => $auditor->id,
            'updated_by' => $auditor->id,
        ]);

        $assignment = AuditAssignment::create([
            'organization_id' => $this->org->id,
            'audit_program_id' => $program->id,
            'clinic_id' => $clinic->id,
            'assignment_code' => 'ASG-'.$clinic->code,
            'title' => 'Tugas '.$clinic->code,
            'lead_auditor_id' => $auditor->id,
            'scheduled_start' => now()->toDateString(),
            'scheduled_end' => now()->addDays(3)->toDateString(),
            'status' => 'active',
            'created_by' => $auditor->id,
            'updated_by' => $auditor->id,
        ]);

        AuditTeamMember::create([
            'organization_id' => $this->org->id,
            'audit_assignment_id' => $assignment->id,
            'user_id' => $auditor->id,
            'team_role' => 'lead_auditor',
            'active' => true,
            'created_by' => $auditor->id,
            'updated_by' => $auditor->id,
        ]);

        return AuditExecution::create([
            'organization_id' => $this->org->id,
            'audit_program_id' => $program->id,
            'audit_assignment_id' => $assignment->id,
            'clinic_id' => $clinic->id,
            'execution_code' => 'EXEC-'.$clinic->code,
            'title' => 'Eksekusi '.$clinic->code,
            'status' => 'in_progress',
            'started_at' => now(),
            'created_by' => $auditor->id,
            'updated_by' => $auditor->id,
        ]);
    }

    /** @test */
    public function unassigned_auditor_cannot_start_execution(): void
    {
        $execution = $this->makeAssignedExecution($this->auditor, $this->clinicA);

        $this->actingAs($this->otherAuditor)
            ->post('/audit/executions/'.$execution->id.'/start')
            ->assertForbidden();
    }

    /** @test */
    public function auditi_cannot_view_finding_from_other_clinic(): void
    {
        $finding = AuditFinding::create([
            'organization_id' => $this->org->id,
            'clinic_id' => $this->clinicB->id,
            'finding_code' => 'TMN-B-1',
            'title' => 'Temuan klinik lain',
            'severity' => 'minor',
            'status' => 'in_capa',
            'identified_at' => now(),
            'created_by' => $this->otherAuditor->id,
            'updated_by' => $this->otherAuditor->id,
        ]);

        $this->actingAs($this->auditi)
            ->get('/findings/'.$finding->id)
            ->assertForbidden();
    }

    /** @test */
    public function evidence_submit_does_not_close_finding_until_auditor_validates(): void
    {
        $execution = $this->makeAssignedExecution($this->auditor, $this->clinicA);
        $finding = AuditFinding::create([
            'organization_id' => $this->org->id,
            'clinic_id' => $this->clinicA->id,
            'audit_execution_id' => $execution->id,
            'finding_code' => 'TMN-CAPA-1',
            'title' => 'Temuan untuk CAPA',
            'severity' => 'major',
            'recommendation' => 'Perbaiki SOP',
            'status' => 'in_capa',
            'identified_at' => now(),
            'created_by' => $this->auditor->id,
            'updated_by' => $this->auditor->id,
        ]);
        $capa = CapaCase::create([
            'organization_id' => $this->org->id,
            'clinic_id' => $this->clinicA->id,
            'case_number' => 'CAPA-1',
            'source_type' => 'audit_finding',
            'source_id' => $finding->id,
            'priority' => 'high',
            'problem_statement' => 'Perbaiki SOP',
            'status' => 'action',
            'owner_id' => $this->auditi->id,
            'target_close_date' => now()->addDays(14)->toDateString(),
            'created_by' => $this->auditi->id,
            'updated_by' => $this->auditi->id,
        ]);
        $action = CapaAction::create([
            'capa_case_id' => $capa->id,
            'action_type' => 'corrective',
            'description' => 'Revisi SOP',
            'assigned_to' => $this->auditi->id,
            'status' => 'pending',
        ]);

        $this->actingAs($this->auditi)
            ->post('/capa/actions/'.$action->id.'/evidence', [
                'evidence_notes' => 'SOP sudah direvisi dan disosialisasikan.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('audit_findings', [
            'id' => $finding->id,
            'status' => 'pending_verification',
        ]);
        $this->assertDatabaseHas('capa_cases', [
            'id' => $capa->id,
            'status' => 'verification',
        ]);

        $this->actingAs($this->auditor)
            ->post('/capa/'.$capa->id.'/verification', [
                'verification_date' => now()->toDateString(),
                'result' => 'effective',
                'notes' => 'Tindak lanjut sesuai rekomendasi.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('audit_findings', [
            'id' => $finding->id,
            'status' => 'closed',
        ]);
        $this->assertDatabaseHas('capa_cases', [
            'id' => $capa->id,
            'status' => 'closed',
        ]);
    }
}
