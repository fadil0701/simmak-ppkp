<?php

namespace Tests\Feature\Audit;

use App\Models\AuditAssignment;
use App\Models\AuditProgram;
use App\Models\AuditStandard;
use App\Models\AuditTeamMember;
use App\Models\Clinic;
use App\Models\Employee;
use App\Models\InstrumentVersion;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Formulir penugasan bersama (create & edit): kode usulan, blokir prasyarat,
 * kode/program read-only saat menyunting, dan persistensi instrumen + catatan.
 */
class AssignmentFormViewTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;

    protected Clinic $clinic;

    protected User $viewer;

    protected User $lead;

    protected User $member;

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
            'is_auditable' => true,
            'city' => 'Jakarta Pusat',
            'is_active' => true,
        ]);

        $this->viewer = $this->makeUser('Peninjau Penugasan', 'peninjau@test.local', null);
        $this->lead = $this->makeUser('Ketua Tim Audit', 'ketua-tim@test.local', 'NIP-9101');
        $this->member = $this->makeUser('Anggota Tim Audit', 'anggota@test.local', 'NIP-9102');

        foreach (['audit.assignment.view', 'audit.assignment.manage'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $role = Role::firstOrCreate(['name' => 'pj-mutu-form', 'guard_name' => 'web']);
        $role->syncPermissions(['audit.assignment.view', 'audit.assignment.manage']);
        $this->viewer->assignRole('pj-mutu-form');

        foreach ([$this->viewer, $this->lead, $this->member] as $user) {
            $user->clinics()->attach($this->clinic->id, ['is_default' => true]);
        }
    }

    protected function makeUser(string $name, string $email, ?string $employeeNumber): User
    {
        $employee = null;

        if ($employeeNumber !== null) {
            $employee = Employee::create([
                'organization_id' => $this->org->id,
                'clinic_id' => $this->clinic->id,
                'employee_number' => $employeeNumber,
                'full_name' => $name,
                'position' => 'Auditor Internal',
                'is_active' => true,
            ]);
        }

        return User::create([
            'organization_id' => $this->org->id,
            'employee_id' => $employee?->id,
            'name' => $name,
            'username' => str_replace(['@', '.'], '-', $email),
            'email' => $email,
            'password' => bcrypt('password'),
            'auth_provider' => 'local',
            'is_active' => true,
        ]);
    }

    protected function makeProgram(string $code, string $status = 'approved'): AuditProgram
    {
        return AuditProgram::create([
            'organization_id' => $this->org->id,
            'program_code' => $code,
            'name' => 'Program Audit Internal '.$code,
            'status' => $status,
        ]);
    }

    protected function makeAssignment(AuditProgram $program, string $code, array $overrides = []): AuditAssignment
    {
        return AuditAssignment::create(array_merge([
            'organization_id' => $this->org->id,
            'audit_program_id' => $program->id,
            'clinic_id' => $this->clinic->id,
            'assignment_code' => $code,
            'title' => 'Audit '.$code,
            'lead_auditor_id' => $this->lead->id,
            'scheduled_start' => '2026-03-02',
            'scheduled_end' => '2026-03-05',
            'status' => 'draft',
            'conflict_check_status' => 'pending',
        ], $overrides));
    }

    protected function makeInstrumentVersion(string $title, string $label): InstrumentVersion
    {
        $standard = AuditStandard::create([
            'organization_id' => $this->org->id,
            'code' => 'STD-'.strtoupper(substr(md5($title), 0, 4)),
            'title' => $title,
            'status' => 'active',
        ]);

        return InstrumentVersion::create([
            'audit_standard_id' => $standard->id,
            'version_label' => $label,
            'status' => 'active',
        ]);
    }

    /** @test */
    public function create_form_suggests_the_next_assignment_code(): void
    {
        $program = $this->makeProgram('PRG-2026-01');
        $this->makeAssignment($program, 'TUGAS-'.now()->format('Y').'-007');

        $response = $this->actingAs($this->viewer)->get('/audit/assignments/create');

        $response->assertStatus(200);
        $response->assertDontSee('name="assignment_code"', false);
        $response->assertSee('value="TUGAS-'.now()->format('Y').'-008"', false);
    }

    /** @test */
    public function create_form_blocks_when_no_program_is_approved_yet(): void
    {
        $this->makeProgram('PRG-2026-02', 'draft');

        $response = $this->actingAs($this->viewer)->get('/audit/assignments/create');

        $response->assertStatus(200);
        $response->assertSee('Belum ada program audit berstatus disetujui');
        // Penugasan tidak bisa dibuat tanpa program yang disetujui.
        $response->assertSee('name="audit_program_id"', false);
        $response->assertDontSee('PRG-2026-02');
    }

    /** @test */
    public function edit_form_shows_code_and_program_as_read_only_meta(): void
    {
        $program = $this->makeProgram('PRG-2026-03');
        $assignment = $this->makeAssignment($program, 'TUGAS-2026-021');

        AuditTeamMember::create([
            'organization_id' => $this->org->id,
            'audit_assignment_id' => $assignment->id,
            'user_id' => $this->lead->id,
            'team_role' => 'lead_auditor',
            'independence_declared' => true,
            'active' => true,
        ]);
        AuditTeamMember::create([
            'organization_id' => $this->org->id,
            'audit_assignment_id' => $assignment->id,
            'user_id' => $this->member->id,
            'team_role' => 'auditor',
            'active' => true,
        ]);

        $response = $this->actingAs($this->viewer)->get('/audit/assignments/'.$assignment->id.'/edit');

        $response->assertStatus(200);
        $response->assertSee('TUGAS-2026-021');
        $response->assertSee('Program Audit Internal PRG-2026-03');
        // Kode dan program tidak lagi tampil sebagai input yang bisa disunting.
        $response->assertDontSee('name="assignment_code"', false);
        $response->assertDontSee('name="audit_program_id"', false);
        // Keanggotaan tim bersifat informatif di halaman ini.
        $response->assertSee('Anggota Tim Saat Ini');
        $response->assertSee('Anggota auditor');
        $response->assertSee('Anggota Tim Audit');
        $response->assertSee('Kelola tim di halaman detail');
        // Ketua tim tidak diduplikasi sebagai "anggota saat ini" & pemilih anggota tidak dirender.
        $response->assertDontSee('name="member_ids[]"', false);
    }

    /** @test */
    public function update_persists_instrument_and_scope_notes(): void
    {
        $program = $this->makeProgram('PRG-2026-04');
        $assignment = $this->makeAssignment($program, 'TUGAS-2026-031');
        $version = $this->makeInstrumentVersion('Standar Audit Mutu Internal', 'v2028');

        $response = $this->actingAs($this->viewer)->put('/audit/assignments/'.$assignment->id, [
            'clinic_id' => $this->clinic->id,
            'title' => 'Audit Ulang Rekam Medis',
            'scope_notes' => 'Fokus pada kepatuhan rekam medis rawat jalan.',
            'lead_auditor_id' => $this->lead->id,
            'scheduled_start' => '2026-04-01',
            'scheduled_end' => '2026-04-04',
            'instrument_version_id' => $version->id,
        ]);

        $response->assertRedirect('/audit/assignments/'.$assignment->id);

        $assignment->refresh();

        $this->assertSame($version->id, $assignment->instrument_version_id);
        $this->assertSame('Fokus pada kepatuhan rekam medis rawat jalan.', $assignment->scope_notes);
        $this->assertSame('Audit Ulang Rekam Medis', $assignment->title);
        $this->assertSame('2026-04-04', $assignment->scheduled_end->format('Y-m-d'));
    }
}
