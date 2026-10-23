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

class ChecklistTableTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;

    protected Clinic $clinic;

    protected User $user;

    protected AuditExecution $execution;

    protected StandardCriterion $criterion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Organization::create([
            'code' => 'PPKP-DKI',
            'name' => 'PPKP Provinsi DKI Jakarta',
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

        $this->user = User::create([
            'organization_id' => $this->org->id,
            'name' => 'Auditor Tabel',
            'email' => 'tabel@test.local',
            'password' => bcrypt('password'),
            'auth_provider' => 'local',
            'is_active' => true,
            'default_clinic_id' => $this->clinic->id,
        ]);
        $this->user->clinics()->attach($this->clinic->id, ['is_default' => true]);

        foreach ([
            'audit.execution.view', 'audit.execution.execute', 'audit.execution.submit',
            'audit.finding.view', 'audit.finding.create', 'audit.program.view',
        ] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $role = Role::firstOrCreate(['name' => 'auditor', 'guard_name' => 'web']);
        $role->syncPermissions(Permission::where('guard_name', 'web')->pluck('name')->all());
        $this->user->assignRole('auditor');

        $program = AuditProgram::create([
            'organization_id' => $this->org->id,
            'program_code' => 'PROG-TBL-001',
            'name' => 'Program Uji Tabel',
            'status' => 'approved',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $version = InstrumentVersion::create([
            'audit_standard_id' => AuditStandard::create([
                'organization_id' => $this->org->id,
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

        $this->criterion = StandardCriterion::create([
            'standard_section_id' => $section->id,
            'code' => 'EP 1',
            'title' => 'Tersedia visi, misi dan tujuan klinik',
            'requirement_text' => 'Tersedia visi, misi dan tujuan klinik yang ditetapkan',
            'evidence_guidance' => "1. Terdapat penetapan visi, misi dan tujuan\n2. Terdapat SK OwnersHIP",
            'max_score' => 10,
            'sort_order' => 1,
            'active' => true,
        ]);

        $assignment = AuditAssignment::create([
            'organization_id' => $this->org->id,
            'audit_program_id' => $program->id,
            'clinic_id' => $this->clinic->id,
            'assignment_code' => 'ASG-TBL-001',
            'title' => 'Penugasan Uji Tabel',
            'scheduled_start' => now()->toDateString(),
            'scheduled_end' => now()->addDays(5)->toDateString(),
            'status' => 'active',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        AuditTeamMember::create([
            'organization_id' => $this->org->id,
            'audit_assignment_id' => $assignment->id,
            'user_id' => $this->user->id,
            'team_role' => 'auditor',
            'active' => true,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $this->execution = AuditExecution::create([
            'organization_id' => $this->org->id,
            'audit_program_id' => $program->id,
            'audit_assignment_id' => $assignment->id,
            'clinic_id' => $this->clinic->id,
            'instrument_version_id' => $version->id,
            'execution_code' => 'EXEC-TBL-001',
            'title' => 'Eksekusi Uji Tabel',
            'status' => 'in_progress',
            'started_at' => now(),
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function checklist_page_renders_table_like_instrument(): void
    {
        $response = $this->actingAs($this->user)->get('/audit/executions/'.$this->execution->id.'/checklist');

        $response->assertStatus(200);
        $response->assertSee('Elemen Penilaian');
        $response->assertSee('Kelengkapan Bukti');
        $response->assertSee('Nilai Kesesuaian');
        $response->assertSee('Rekomendasi');
    }

    /** @test */
    public function score_and_facts_can_be_saved_from_table(): void
    {
        $response = $this->actingAs($this->user)->post('/audit/executions/'.$this->execution->id.'/table', [
            'rows' => [
                $this->criterion->id => [
                    'standard_criterion_id' => $this->criterion->id,
                    'response_value' => 'compliant',
                    'recommendation' => 'Pertahankan praktik yang ada.',
                ],
            ],
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('checklist_responses', [
            'audit_execution_id' => $this->execution->id,
            'standard_criterion_id' => $this->criterion->id,
            'response_value' => 'compliant',
            'recommendation' => 'Pertahankan praktik yang ada.',
        ]);

        $this->assertDatabaseMissing('audit_findings', [
            'audit_execution_id' => $this->execution->id,
            'standard_criterion_id' => $this->criterion->id,
        ]);
    }

    /** @test */
    public function finding_can_be_created_from_table_row(): void
    {
        $response = $this->actingAs($this->user)->post('/audit/executions/'.$this->execution->id.'/table', [
            'rows' => [
                $this->criterion->id => [
                    'standard_criterion_id' => $this->criterion->id,
                    'response_value' => 'non_compliant',
                    'recommendation' => 'Tetapkan SK penetapan.',
                ],
            ],
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('audit_findings', [
            'audit_execution_id' => $this->execution->id,
            'standard_criterion_id' => $this->criterion->id,
            'severity' => 'major',
            'status' => 'in_capa',
        ]);
        $this->assertDatabaseHas('capa_cases', [
            'clinic_id' => $this->clinic->id,
            'source_type' => 'audit_finding',
            'status' => 'action',
        ]);
    }

    /** @test */
    public function score_equals_max_marks_compliant(): void
    {
        $this->actingAs($this->user)->post('/audit/executions/'.$this->execution->id.'/table', [
            'rows' => [
                $this->criterion->id => [
                    'standard_criterion_id' => $this->criterion->id,
                    'response_value' => 'compliant',
                ],
            ],
        ]);

        $this->assertDatabaseHas('checklist_responses', [
            'standard_criterion_id' => $this->criterion->id,
            'response_value' => 'compliant',
        ]);
    }
}
