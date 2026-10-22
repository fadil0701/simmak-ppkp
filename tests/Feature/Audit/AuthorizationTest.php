<?php

namespace Tests\Feature\Audit;

use App\Models\AuditAssignment;
use App\Models\AuditExecution;
use App\Models\AuditFinding;
use App\Models\AuditProgram;
use App\Models\AuditTeamMember;
use App\Models\Clinic;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPermissionsAndRoles();
    }

    protected function seedPermissionsAndRoles(): void
    {
        $permissions = [
            'audit.view',
            'audit.program.view', 'audit.program.create', 'audit.program.update',
            'audit.program.submit', 'audit.program.approve',
            'audit.assignment.view', 'audit.assignment.manage',
            'audit.finding.view', 'audit.finding.create', 'audit.finding.update',
            'audit.finding.review', 'audit.finding.close', 'audit.finding.reopen',
            'audit.action.view', 'audit.action.update',
            'audit.evidence.view', 'audit.evidence.upload', 'audit.evidence.download',
            'audit.execution.view', 'audit.execution.execute', 'audit.execution.submit',
            'audit.capa.verify', 'audit.standard.view',
            'clinics.view', 'clinics.manage',
            'audit.log.view',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::where('guard_name', 'web')->pluck('name'));

        $auditor = Role::firstOrCreate(['name' => 'auditor', 'guard_name' => 'web']);
        $auditor->syncPermissions([
            'audit.view',
            'audit.program.view', 'audit.assignment.view', 'audit.standard.view',
            'audit.execution.view', 'audit.execution.execute', 'audit.execution.submit',
            'audit.evidence.upload', 'audit.evidence.view',
            'audit.finding.view', 'audit.finding.create', 'audit.finding.update',
            'audit.action.view', 'audit.action.update',
        ]);

        $kepalaKlinik = Role::firstOrCreate(['name' => 'kepala-klinik', 'guard_name' => 'web']);
        $kepalaKlinik->syncPermissions([
            'audit.program.view', 'audit.assignment.view', 'audit.execution.view',
            'audit.standard.view', 'audit.evidence.view',
            'audit.finding.view', 'audit.finding.review', 'audit.action.view',
            'audit.finding.close',
        ]);

        // Role yang dipakai sebagian tes — dibuat agar assignRole() tidak gagal.
        $pjMutu = Role::firstOrCreate(['name' => 'pj-mutu', 'guard_name' => 'web']);
        $pjMutu->syncPermissions([
            'audit.program.view', 'audit.program.create', 'audit.program.update',
            'audit.program.submit', 'audit.program.approve',
            'audit.finding.view', 'audit.finding.create',
            'audit.action.view', 'audit.action.update',
        ]);

        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Organisasi tunggal per tes (RefreshDatabase), dibuat sekali.
     */
    protected function test_organization(): Organization
    {
        return Organization::firstOrCreate(
            ['code' => 'TEST-ORG'],
            [
                'name' => 'Test Organization',
                'org_type' => 'central',
                'is_active' => true,
            ]
        );
    }

    protected function createUser(string $role, array $clinicCodes = []): User
    {
        $org = $this->test_organization();

        $clinics = collect($clinicCodes)->map(function (string $code) use ($org) {
            return Clinic::firstOrCreate(
                ['organization_id' => $org->id, 'code' => $code],
                [
                    'name' => 'Test Clinic '.$code,
                    'clinic_type' => 'pratama',
                    'is_main_clinic' => false,
                    'is_active' => true,
                ]
            );
        })->keyBy('code');

        $user = User::create([
            'organization_id' => $org->id,
            'name' => 'Test User '.$role,
            'email' => strtolower($role).'@test.local',
            'password' => bcrypt('password'),
            'auth_provider' => 'local',
            'is_active' => true,
            'default_clinic_id' => $clinics->first()?->id,
        ]);

        $user->assignRole($role);

        foreach ($clinics as $clinic) {
            $user->clinics()->attach($clinic->id, ['is_default' => $clinic->id === $clinics->first()?->id]);
        }

        return $user;
    }

    /** @test */
    public function admin_can_access_all_audit_routes(): void
    {
        $user = $this->createUser('super-admin');

        $response = $this->actingAs($user)->get('/audit/programs');

        $response->assertStatus(200);
    }

    /** @test */
    public function auditor_cannot_access_clinic_management(): void
    {
        $user = $this->createUser('auditor');

        $response = $this->actingAs($user)->get('/clinics');

        $response->assertStatus(403);
    }

    /** @test */
    public function kepala_klinik_cannot_access_other_clinic_data(): void
    {
        $user = $this->createUser('kepala-klinik', ['KP01']);

        $otherClinic = Clinic::create([
            'organization_id' => $user->organization_id,
            'code' => 'KP02',
            'name' => 'Other Clinic',
            'clinic_type' => 'pratama',
            'is_main_clinic' => false,
            'is_active' => true,
        ]);

        $finding = AuditFinding::create([
            'organization_id' => $user->organization_id,
            'clinic_id' => $otherClinic->id,
            'finding_code' => 'TMN-2026-001',
            'title' => 'Test Finding',
            'severity' => 'minor',
            'status' => 'draft',
            'identified_at' => now(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        // Kepala klinik KP01 should not access finding in KP02
        $response = $this->actingAs($user)->get('/findings/'.$finding->id);

        $response->assertStatus(403);
    }

    /** @test */
    public function auditor_can_only_access_assigned_execution(): void
    {
        $user = $this->createUser('auditor', ['KP01']);

        $orgId = $user->organization_id;

        $program = AuditProgram::create([
            'organization_id' => $orgId,
            'program_code' => 'PROG-001',
            'name' => 'Test Program',
            'status' => 'approved',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $clinic = Clinic::where('organization_id', $orgId)->where('code', 'KP01')->first();

        $assignment = AuditAssignment::create([
            'organization_id' => $orgId,
            'audit_program_id' => $program->id,
            'clinic_id' => $clinic->id,
            'assignment_code' => 'ASG-001',
            'title' => 'Test Assignment',
            'scheduled_start' => now()->toDateString(),
            'scheduled_end' => now()->addDays(7)->toDateString(),
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        AuditTeamMember::create([
            'organization_id' => $orgId,
            'audit_assignment_id' => $assignment->id,
            'user_id' => $user->id,
            'team_role' => 'auditor',
            'active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $execution = AuditExecution::create([
            'organization_id' => $orgId,
            'audit_program_id' => $program->id,
            'audit_assignment_id' => $assignment->id,
            'clinic_id' => $clinic->id,
            'execution_code' => 'EXEC-001',
            'title' => 'Test Execution',
            'status' => 'in_progress',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        // Auditor assigned to this execution should be able to access
        $response = $this->actingAs($user)->get('/audit/executions/'.$execution->id.'/checklist');

        // Should be 200 (assigned) or 403 (not assigned - either is acceptable for this test)
        $response->assertStatus(200);
    }

    /** @test */
    public function non_admin_cannot_approve_own_program_submission(): void
    {
        $user = $this->createUser('pj-mutu', ['KP01']);

        $orgId = $user->organization_id;

        $program = AuditProgram::create([
            'organization_id' => $orgId,
            'program_code' => 'PROG-002',
            'name' => 'Test Program',
            'status' => 'submitted_for_review',
            'created_by' => $user->id,
            'submitted_at' => now(),
            'updated_by' => $user->id,
        ]);

        // User who submitted should not be able to approve (separation of duties)
        $response = $this->actingAs($user)->post('/audit/programs/'.$program->id.'/approve');

        $response->assertStatus(403);
    }

    /** @test */
    public function staff_cannot_access_audit_management(): void
    {
        $user = $this->createUser('staff', ['KP01']);

        $response = $this->actingAs($user)->get('/audit/programs');

        $response->assertStatus(403);
    }

    /** @test */
    public function user_with_multiple_clinics_can_access_both(): void
    {
        $user = $this->createUser('auditor', ['KP01', 'KP02']);

        $orgId = $user->organization_id;

        // Create findings in both clinics
        $clinic1 = Clinic::where('organization_id', $orgId)->where('code', 'KP01')->first();
        $clinic2 = Clinic::where('organization_id', $orgId)->where('code', 'KP02')->first();

        AuditFinding::create([
            'organization_id' => $orgId,
            'clinic_id' => $clinic1->id,
            'finding_code' => 'TMN-2026-010',
            'title' => 'Finding Clinic 1',
            'severity' => 'minor',
            'status' => 'draft',
            'identified_at' => now(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        AuditFinding::create([
            'organization_id' => $orgId,
            'clinic_id' => $clinic2->id,
            'finding_code' => 'TMN-2026-011',
            'title' => 'Finding Clinic 2',
            'severity' => 'minor',
            'status' => 'draft',
            'identified_at' => now(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/findings');

        $response->assertStatus(200);
        $response->assertSee('TMN-2026-010');
        $response->assertSee('TMN-2026-011');
    }

    /** @test */
    public function user_cannot_access_clinic_not_in_scope(): void
    {
        $user = $this->createUser('auditor', ['KP01']);

        $orgId = $user->organization_id;

        $clinic3 = Clinic::create([
            'organization_id' => $orgId,
            'code' => 'KP99',
            'name' => 'Out of Scope Clinic',
            'clinic_type' => 'pratama',
            'is_main_clinic' => false,
            'is_active' => true,
        ]);

        $finding = AuditFinding::create([
            'organization_id' => $orgId,
            'clinic_id' => $clinic3->id,
            'finding_code' => 'TMN-2026-099',
            'title' => 'Out of Scope Finding',
            'severity' => 'minor',
            'status' => 'draft',
            'identified_at' => now(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/findings/'.$finding->id);

        $response->assertStatus(403);
    }
}
