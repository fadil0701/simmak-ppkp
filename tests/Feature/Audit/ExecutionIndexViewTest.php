<?php

namespace Tests\Feature\Audit;

use App\Models\AuditAssignment;
use App\Models\AuditExecution;
use App\Models\AuditProgram;
use App\Models\AuditStandard;
use App\Models\AuditTeamMember;
use App\Models\Clinic;
use App\Models\InstrumentVersion;
use App\Models\Organization;
use App\Models\StandardCriterion;
use App\Models\StandardSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Halaman daftar Pelaksanaan Audit (audit.executions.index):
 * kartu ringkasan status, progres pengisian, dan filter.
 */
class ExecutionIndexViewTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected AuditExecution $execution;

    protected function setUp(): void
    {
        parent::setUp();

        $org = Organization::create([
            'code' => 'PPKP-DKI',
            'name' => 'PPKP Provinsi DKI Jakarta',
            'org_type' => 'central',
            'is_active' => true,
        ]);

        $clinic = Clinic::create([
            'organization_id' => $org->id,
            'code' => 'KP01',
            'name' => 'Klinik Pratama Jakarta Pusat',
            'clinic_type' => 'pratama',
            'is_main_clinic' => false,
            'city' => 'Jakarta Pusat',
            'is_active' => true,
        ]);

        $this->user = User::create([
            'organization_id' => $org->id,
            'name' => 'Auditor Daftar',
            'email' => 'daftar@test.local',
            'password' => bcrypt('password'),
            'auth_provider' => 'local',
            'is_active' => true,
            'default_clinic_id' => $clinic->id,
        ]);
        $this->user->clinics()->attach($clinic->id, ['is_default' => true]);

        foreach ([
            'audit.execution.view', 'audit.execution.execute', 'audit.execution.submit',
            'audit.finding.view', 'audit.finding.create', 'audit.assignment.view',
        ] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $role = Role::firstOrCreate(['name' => 'auditor', 'guard_name' => 'web']);
        $role->syncPermissions(Permission::where('guard_name', 'web')->pluck('name')->all());
        $this->user->assignRole('auditor');

        $program = AuditProgram::create([
            'organization_id' => $org->id,
            'program_code' => 'PROG-IDX-001',
            'name' => 'Program Uji Daftar',
            'status' => 'approved',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $version = InstrumentVersion::create([
            'audit_standard_id' => AuditStandard::create([
                'organization_id' => $org->id,
                'code' => 'TKK',
                'title' => 'Tata Kelola Klinik',
                'status' => 'draft',
            ])->id,
            'version_label' => 'v1',
            'status' => 'active',
        ]);

        $section = StandardSection::create([
            'instrument_version_id' => $version->id,
            'code' => 'STANDAR 1.1.',
            'title' => 'STANDAR 1.1.',
            'sort_order' => 1,
        ]);

        StandardCriterion::create([
            'standard_section_id' => $section->id,
            'code' => 'EP 1',
            'title' => 'Tersedia visi, misi dan tujuan klinik',
            'evidence_guidance' => "1. Terdapat penetapan visi\n2. Terdapat SK kepemilikan",
            'max_score' => 10,
            'sort_order' => 1,
            'active' => true,
        ]);

        $assignment = AuditAssignment::create([
            'organization_id' => $org->id,
            'audit_program_id' => $program->id,
            'clinic_id' => $clinic->id,
            'assignment_code' => 'ASG-IDX-001',
            'title' => 'Penugasan Uji Daftar',
            'scheduled_start' => now()->toDateString(),
            'scheduled_end' => now()->addDays(5)->toDateString(),
            'status' => 'active',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        AuditTeamMember::create([
            'organization_id' => $org->id,
            'audit_assignment_id' => $assignment->id,
            'user_id' => $this->user->id,
            'team_role' => 'auditor',
            'active' => true,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $this->execution = AuditExecution::create([
            'organization_id' => $org->id,
            'audit_program_id' => $program->id,
            'audit_assignment_id' => $assignment->id,
            'clinic_id' => $clinic->id,
            'instrument_version_id' => $version->id,
            'execution_code' => 'EXEC-IDX-001',
            'title' => 'Eksekusi Uji Daftar',
            'status' => 'in_progress',
            'started_at' => now(),
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function execution_index_lists_assigned_executions_with_progress_and_status(): void
    {
        $response = $this->actingAs($this->user)->get('/audit/executions');

        $response->assertStatus(200);
        $response->assertSee('Pelaksanaan Audit');
        $response->assertSee('Daftar Eksekusi Audit');
        $response->assertSee('EXEC-IDX-001');
        $response->assertSee('Eksekusi Uji Daftar');
        $response->assertSee('Progres Pengisian');
        $response->assertSee('Berjalan');
        $response->assertSee('Buat Eksekusi Baru');
    }

    /** @test */
    public function execution_index_can_be_filtered_by_status(): void
    {
        $response = $this->actingAs($this->user)->get('/audit/executions?status=finalized');

        $response->assertStatus(200);
        $response->assertDontSee('EXEC-IDX-001');
        $response->assertSee('Tidak ada eksekusi pada filter ini');
    }

    /** @test */
    public function execution_index_can_be_searched_by_code(): void
    {
        $found = $this->actingAs($this->user)->get('/audit/executions?q=EXEC-IDX');
        $found->assertStatus(200);
        $found->assertSee('EXEC-IDX-001');

        $missing = $this->actingAs($this->user)->get('/audit/executions?q=TIDAK-ADA');
        $missing->assertStatus(200);
        $missing->assertDontSee('EXEC-IDX-001');
    }

    /** @test */
    public function checklist_page_renders_read_only_mode_for_finalized_execution(): void
    {
        $this->execution->update(['status' => 'finalized', 'finalized_at' => now()]);

        $response = $this->actingAs($this->user)->get('/audit/executions/'.$this->execution->id.'/checklist');

        $response->assertStatus(200);
        $response->assertSee('Nilai Kesesuaian');
        $response->assertSee('Hasil audit final');
        $response->assertDontSee('form-tabel-audit');
    }
}
