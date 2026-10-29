<?php

namespace Tests\Feature\Audit;

use App\Models\AuditAssignment;
use App\Models\AuditExecution;
use App\Models\AuditFinding;
use App\Models\AuditProgram;
use App\Models\AuditStandard;
use App\Models\AuditTeamMember;
use App\Models\Clinic;
use App\Models\InstrumentVersion;
use App\Models\Organization;
use App\Models\StandardCriterion;
use App\Models\StandardSection;
use App\Models\User;
use App\Support\SimmakMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Super admin (administrator sistem) — seluruh aksi CRUD pada semua modul.
 *
 * Peran ini melewati policy, gate, middleware `permission:`, pembatasan status
 * alur kerja, dan pembatasan kepemilikan data (lihat User::hasUnrestrictedAccess()
 * dan Gate::before di AuthServiceProvider). Peran lain tetap dibatasi.
 */
class SuperAdminFullAccessTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;

    protected Organization $otherOrg;

    protected Clinic $clinic;

    protected User $superAdmin;

    protected User $auditor;

    protected AuditProgram $program;

    protected InstrumentVersion $version;

    protected AuditAssignment $assignment;

    protected AuditExecution $execution;

    protected StandardCriterion $criterion;

    protected AuditFinding $finding;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'users.view', 'users.manage', 'audit.program.view', 'audit.program.approve',
            'audit.execution.view', 'audit.execution.execute', 'audit.execution.submit',
            'audit.finding.view', 'audit.finding.create', 'audit.finding.update',
            'audit.assignment.view', 'audit.standard.view', 'capa.view',
        ] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Role super-admin sengaja TIDAK diberi permission apa pun: akses penuh
        // berasal dari Gate::before, bukan dari daftar izin.
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->org = Organization::create([
            'code' => 'PPKP-DKI',
            'name' => 'PPKP Provinsi DKI Jakarta',
            'org_type' => 'central',
            'is_active' => true,
        ]);

        $this->otherOrg = Organization::create([
            'code' => 'PPKP-BDG',
            'name' => 'PPKP Provinsi Jawa Barat',
            'org_type' => 'central',
            'is_active' => true,
        ]);

        $this->clinic = Clinic::create([
            'organization_id' => $this->org->id,
            'code' => 'KP01',
            'name' => 'Klinik Pratama Jakarta Pusat',
            'clinic_type' => 'pratama',
            'is_main_clinic' => false,
            'city' => 'Jakarta Pusat',
            'is_active' => true,
        ]);

        $this->superAdmin = User::create([
            'organization_id' => $this->org->id,
            'name' => 'Super Admin Uji',
            'email' => 'superadmin@test.local',
            'password' => bcrypt('password'),
            'auth_provider' => 'local',
            'is_active' => true,
        ]);
        $this->superAdmin->assignRole('super-admin');

        $auditorRole = Role::firstOrCreate(['name' => 'auditor', 'guard_name' => 'web']);
        $auditorRole->syncPermissions([
            'audit.program.view', 'audit.execution.view', 'audit.execution.execute',
            'audit.execution.submit', 'audit.finding.view', 'audit.finding.create',
            'audit.finding.update', 'audit.assignment.view', 'audit.standard.view',
        ]);

        $this->auditor = User::create([
            'organization_id' => $this->org->id,
            'name' => 'Auditor Uji',
            'email' => 'auditor-super@test.local',
            'password' => bcrypt('password'),
            'auth_provider' => 'local',
            'is_active' => true,
            'default_clinic_id' => $this->clinic->id,
        ]);
        $this->auditor->clinics()->attach($this->clinic->id, ['is_default' => true]);
        $this->auditor->assignRole('auditor');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->program = AuditProgram::create([
            'organization_id' => $this->org->id,
            'program_code' => 'PROG-SA-001',
            'name' => 'Program Uji Super Admin',
            'status' => 'approved',
            'created_by' => $this->auditor->id,
            'updated_by' => $this->auditor->id,
        ]);

        $this->version = InstrumentVersion::create([
            'audit_standard_id' => AuditStandard::create([
                'organization_id' => $this->org->id,
                'code' => 'TKK-SA',
                'title' => 'Tata Kelola Klinik (uji super admin)',
                'status' => 'draft',
            ])->id,
            'version_label' => 'v1',
            'status' => 'active',
        ]);

        $section = StandardSection::create([
            'instrument_version_id' => $this->version->id,
            'code' => 'STANDAR 1.1.',
            'title' => 'STANDAR 1.1.',
            'sort_order' => 1,
        ]);

        $this->criterion = StandardCriterion::create([
            'standard_section_id' => $section->id,
            'code' => 'EP 1',
            'title' => 'Tersedia visi, misi dan tujuan klinik',
            'requirement_text' => 'Elemen penilaian uji super admin.',
            'max_score' => 10,
            'sort_order' => 1,
            'active' => true,
        ]);

        $this->assignment = AuditAssignment::create([
            'organization_id' => $this->org->id,
            'audit_program_id' => $this->program->id,
            'clinic_id' => $this->clinic->id,
            'assignment_code' => 'ASG-SA-001',
            'title' => 'Penugasan Uji Super Admin',
            'scheduled_start' => now()->toDateString(),
            'scheduled_end' => now()->addDays(5)->toDateString(),
            'status' => 'active',
            'conflict_check_status' => 'resolved',
            'created_by' => $this->auditor->id,
            'updated_by' => $this->auditor->id,
        ]);

        // Tim hanya berisi auditor: super admin BUKAN anggota tim.
        AuditTeamMember::create([
            'organization_id' => $this->org->id,
            'audit_assignment_id' => $this->assignment->id,
            'user_id' => $this->auditor->id,
            'team_role' => 'auditor',
            'active' => true,
            'created_by' => $this->auditor->id,
            'updated_by' => $this->auditor->id,
        ]);

        $this->execution = AuditExecution::create([
            'organization_id' => $this->org->id,
            'audit_program_id' => $this->program->id,
            'audit_assignment_id' => $this->assignment->id,
            'clinic_id' => $this->clinic->id,
            'instrument_version_id' => $this->version->id,
            'execution_code' => 'EXEC-SA-001',
            'title' => 'Eksekusi Uji Super Admin',
            'status' => 'in_progress',
            'started_at' => now(),
            'created_by' => $this->auditor->id,
            'updated_by' => $this->auditor->id,
        ]);

        $this->finding = AuditFinding::create([
            'organization_id' => $this->org->id,
            'clinic_id' => $this->clinic->id,
            'audit_execution_id' => $this->execution->id,
            'finding_code' => 'TMN-SA-001',
            'title' => 'Temuan uji super admin',
            'severity' => 'major',
            'status' => 'in_capa',
            'identified_at' => now(),
            'created_by' => $this->auditor->id,
            'updated_by' => $this->auditor->id,
        ]);
    }

    /** @test */
    public function super_admin_can_open_permission_gated_modules_without_any_permission(): void
    {
        $this->actingAs($this->superAdmin)->get('/users')->assertStatus(200);
        $this->actingAs($this->superAdmin)->get('/audit/programs')->assertStatus(200);
        $this->actingAs($this->superAdmin)->get('/capa')->assertStatus(200);
        $this->actingAs($this->superAdmin)->get('/audit/executions')->assertStatus(200);
    }

    /** @test */
    public function auditor_is_still_blocked_by_permission_middleware(): void
    {
        $this->actingAs($this->auditor)->get('/users')->assertStatus(403);
    }

    /** @test */
    public function super_admin_sees_every_menu_module(): void
    {
        $visible = collect(SimmakMenu::forUser($this->superAdmin))->pluck('route')->all();

        $this->assertCount(count(SimmakMenu::items()), $visible);
        $this->assertContains('users.index', $visible);
        $this->assertContains('audit.programs.index', $visible);
        $this->assertContains('audit.executions.index', $visible);
        $this->assertContains('findings.index', $visible);
        $this->assertContains('capa.index', $visible);
        $this->assertContains('clinics.index', $visible);
    }

    /** @test */
    public function super_admin_bypasses_policies_on_records_from_another_organization(): void
    {
        $foreignProgram = AuditProgram::create([
            'organization_id' => $this->otherOrg->id,
            'program_code' => 'PROG-LAIN-001',
            'name' => 'Program Organisasi Lain',
            'status' => 'draft',
            'created_by' => $this->auditor->id,
            'updated_by' => $this->auditor->id,
        ]);

        $this->assertTrue(Gate::forUser($this->superAdmin)->allows('view', $foreignProgram));
        $this->assertTrue(Gate::forUser($this->superAdmin)->allows('update', $foreignProgram));
        $this->assertTrue(Gate::forUser($this->superAdmin)->allows('delete', $foreignProgram));

        $this->assertFalse(Gate::forUser($this->auditor)->allows('view', $foreignProgram));
    }

    /** @test */
    public function super_admin_can_delete_finding_that_policy_marks_read_only(): void
    {
        // Policy: hapus temuan hanya untuk status draft.
        $this->assertFalse(Gate::forUser($this->auditor)->allows('delete', $this->finding));
        $this->assertTrue(Gate::forUser($this->superAdmin)->allows('delete', $this->finding));
    }

    /** @test */
    public function inactive_super_admin_loses_the_bypass(): void
    {
        $this->superAdmin->update(['is_active' => false]);

        $this->assertFalse(
            Gate::forUser($this->superAdmin->fresh())->allows('viewAny', AuditProgram::class)
        );
    }

    /** @test */
    public function super_admin_can_create_execution_on_assignment_they_are_not_assigned_to(): void
    {
        $response = $this->actingAs($this->superAdmin)->post('/audit/executions', [
            'audit_assignment_id' => $this->assignment->id,
            'instrument_version_id' => $this->version->id,
            'execution_code' => 'EXEC-SA-002',
            'title' => 'Eksekusi dibuat super admin',
        ]);

        $response->assertRedirect();
        $execution = \App\Models\AuditExecution::query()
            ->where('created_by', $this->superAdmin->id)
            ->where('title', 'Eksekusi dibuat super admin')
            ->firstOrFail();
        $this->assertMatchesRegularExpression('/^EXEC-\d{4}-\d{4}$/', $execution->execution_code);
        $this->assertNotSame('EXEC-SA-002', $execution->execution_code);
    }

    /** @test */
    public function auditor_cannot_create_execution_on_assignment_they_are_not_assigned_to(): void
    {
        $other = User::create([
            'organization_id' => $this->org->id,
            'name' => 'Auditor Lain',
            'email' => 'auditor-lain@test.local',
            'password' => bcrypt('password'),
            'auth_provider' => 'local',
            'is_active' => true,
            'default_clinic_id' => $this->clinic->id,
        ]);
        $other->clinics()->attach($this->clinic->id, ['is_default' => true]);
        $other->assignRole('auditor');

        $this->actingAs($other)->post('/audit/executions', [
            'audit_assignment_id' => $this->assignment->id,
            'instrument_version_id' => $this->version->id,
            'execution_code' => 'EXEC-SA-003',
            'title' => 'Eksekusi oleh auditor lain',
        ])->assertStatus(403);
    }

    /** @test */
    public function super_admin_can_correct_checklist_of_finalized_execution(): void
    {
        $this->execution->update(['status' => 'finalized', 'finalized_at' => now()]);

        $this->actingAs($this->superAdmin)
            ->post('/audit/executions/'.$this->execution->id.'/table', [
                'rows' => [
                    $this->criterion->id => [
                        'standard_criterion_id' => $this->criterion->id,
                        'response_value' => 'compliant',
                        'recommendation' => 'Koreksi super admin.',
                    ],
                ],
            ])
            ->assertStatus(302);

        $this->assertDatabaseHas('checklist_responses', [
            'audit_execution_id' => $this->execution->id,
            'standard_criterion_id' => $this->criterion->id,
            'response_value' => 'compliant',
        ]);
    }

    /** @test */
    public function auditor_cannot_correct_checklist_of_finalized_execution(): void
    {
        $this->execution->update(['status' => 'finalized', 'finalized_at' => now()]);

        $this->actingAs($this->auditor)
            ->post('/audit/executions/'.$this->execution->id.'/table', [
                'rows' => [
                    $this->criterion->id => [
                        'standard_criterion_id' => $this->criterion->id,
                        'response_value' => 'compliant',
                    ],
                ],
            ])
            ->assertStatus(403);

        $this->assertDatabaseMissing('checklist_responses', [
            'audit_execution_id' => $this->execution->id,
            'standard_criterion_id' => $this->criterion->id,
        ]);
    }

    /** @test */
    public function super_admin_can_approve_program_they_created_themselves(): void
    {
        $ownProgram = AuditProgram::create([
            'organization_id' => $this->org->id,
            'program_code' => 'PROG-SA-SELF-001',
            'name' => 'Program Buatan Super Admin',
            'status' => 'submitted_for_review',
            'submitted_at' => now(),
            'created_by' => $this->superAdmin->id,
            'updated_by' => $this->superAdmin->id,
        ]);

        $this->actingAs($this->superAdmin)
            ->post('/audit/programs/'.$ownProgram->id.'/approve')
            ->assertStatus(302);

        $this->assertDatabaseHas('audit_programs', [
            'id' => $ownProgram->id,
            'status' => 'approved',
        ]);
    }

    /** @test */
    public function super_admin_can_approve_program_straight_from_draft_status(): void
    {
        // Alur normal: draft → submitted_for_review → approved. Super admin
        // melewati prasyarat status tersebut.
        $this->program->update(['status' => 'draft', 'submitted_at' => null]);

        $this->actingAs($this->superAdmin)
            ->post('/audit/programs/'.$this->program->id.'/approve')
            ->assertStatus(302);

        $this->assertDatabaseHas('audit_programs', [
            'id' => $this->program->id,
            'status' => 'approved',
        ]);
    }

    /** @test */
    public function super_admin_program_index_lists_programs_from_every_organization(): void
    {
        AuditProgram::create([
            'organization_id' => $this->otherOrg->id,
            'program_code' => 'PROG-LAIN-INDEX',
            'name' => 'Program Organisasi Lain (indeks)',
            'status' => 'draft',
            'created_by' => $this->auditor->id,
            'updated_by' => $this->auditor->id,
        ]);

        $this->actingAs($this->superAdmin)
            ->get('/audit/programs')
            ->assertStatus(200)
            ->assertSee('PROG-LAIN-INDEX');
    }

    /** @test */
    public function super_admin_can_update_and_delete_standard_from_another_organization(): void
    {
        $foreignStandard = AuditStandard::create([
            'organization_id' => $this->otherOrg->id,
            'code' => 'TKK-LAIN',
            'title' => 'Standar Organisasi Lain',
            'status' => 'draft',
        ]);

        $this->actingAs($this->superAdmin)
            ->put('/audit/standards/'.$foreignStandard->id, [
                'code' => 'TKK-LAIN',
                'title' => 'Standar Organisasi Lain (diubah super admin)',
            ])
            ->assertStatus(302);

        $this->assertDatabaseHas('audit_standards', [
            'id' => $foreignStandard->id,
            'title' => 'Standar Organisasi Lain (diubah super admin)',
        ]);

        // Tanpa versi instrumen, standar boleh dihapus.
        $this->actingAs($this->superAdmin)
            ->delete('/audit/standards/'.$foreignStandard->id)
            ->assertStatus(302);

        $this->assertSoftDeleted('audit_standards', ['id' => $foreignStandard->id]);
    }

    /** @test */
    public function auditor_cannot_update_standard_from_another_organization(): void
    {
        $foreignStandard = AuditStandard::create([
            'organization_id' => $this->otherOrg->id,
            'code' => 'TKK-LAIN-2',
            'title' => 'Standar Organisasi Lain',
            'status' => 'draft',
        ]);

        $this->actingAs($this->auditor)
            ->put('/audit/standards/'.$foreignStandard->id, [
                'code' => 'TKK-LAIN-2',
                'title' => 'Diubah auditor',
            ])
            ->assertStatus(403);

        $this->assertDatabaseHas('audit_standards', [
            'id' => $foreignStandard->id,
            'title' => 'Standar Organisasi Lain',
        ]);
    }

    /** @test */
    public function super_admin_can_edit_finding_on_locked_status(): void
    {
        // Temuan pada status in_capa terkunci untuk peran lain.
        $this->assertSame('in_capa', $this->finding->status);

        $this->actingAs($this->superAdmin)
            ->get('/findings/'.$this->finding->id.'/edit')
            ->assertStatus(200);
    }

    /** @test */
    public function auditor_cannot_edit_finding_on_locked_status(): void
    {
        $this->actingAs($this->auditor)
            ->get('/findings/'.$this->finding->id.'/edit')
            ->assertStatus(403);
    }
}
