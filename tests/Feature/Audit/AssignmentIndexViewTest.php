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
 * Kolom indeks penugasan: Petugas Auditor, Lokasi Audit, Tanggal Audit,
 * Standar/Instrumen, aksi "Detail Tim", dan paginasi.
 */
class AssignmentIndexViewTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;

    protected Clinic $clinic;

    protected AuditProgram $program;

    protected User $viewer;

    protected User $lead;

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

        $this->program = AuditProgram::create([
            'organization_id' => $this->org->id,
            'program_code' => 'PRG-2026-01',
            'name' => 'Program Audit Internal 2026',
            'status' => 'approved',
        ]);

        $this->viewer = $this->makeUser('Peninjau Penugasan', 'peninjau@test.local', null);
        $this->lead = $this->makeUser('Ketua Tim Audit', 'ketua-tim@test.local', 'NIP-9101');

        foreach (['audit.assignment.view', 'audit.assignment.manage'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $role = Role::firstOrCreate(['name' => 'pj-mutu-view', 'guard_name' => 'web']);
        $role->syncPermissions(['audit.assignment.view', 'audit.assignment.manage']);
        $this->viewer->assignRole('pj-mutu-view');
        $this->viewer->clinics()->attach($this->clinic->id, ['is_default' => true]);
        $this->lead->clinics()->attach($this->clinic->id, ['is_default' => true]);
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

    protected function makeAssignment(string $code, array $overrides = []): AuditAssignment
    {
        return AuditAssignment::create(array_merge([
            'organization_id' => $this->org->id,
            'audit_program_id' => $this->program->id,
            'clinic_id' => $this->clinic->id,
            'assignment_code' => $code,
            'title' => 'Audit '.$code,
            'lead_auditor_id' => $this->lead->id,
            'scheduled_start' => '2026-03-02',
            'scheduled_end' => '2026-03-05',
            'status' => 'active',
            'conflict_check_status' => 'resolved',
        ], $overrides));
    }

    /** @test */
    public function index_shows_team_location_schedule_and_instrument_columns(): void
    {
        $assignment = $this->makeAssignment('TUGAS-2026-001');

        $member = $this->makeUser('Anggota Tim Audit', 'anggota@test.local', 'NIP-9102');
        $member->clinics()->attach($this->clinic->id, ['is_default' => false]);

        AuditTeamMember::create([
            'organization_id' => $this->org->id,
            'audit_assignment_id' => $assignment->id,
            'user_id' => $this->lead->id,
            'team_role' => 'lead_auditor',
            'active' => true,
        ]);
        AuditTeamMember::create([
            'organization_id' => $this->org->id,
            'audit_assignment_id' => $assignment->id,
            'user_id' => $member->id,
            'team_role' => 'auditor',
            'active' => true,
        ]);

        $response = $this->actingAs($this->viewer)->get('/audit/assignments');

        $response->assertStatus(200);
        $response->assertSee('Petugas Auditor');
        $response->assertSee('Lokasi Audit');
        $response->assertSee('Tanggal Audit');
        $response->assertSee('Standar / Instrumen');
        $response->assertSee('Detail Tim');
        $response->assertSee('Ketua Tim Audit');
        $response->assertSee('NIP-9101');
        $response->assertSee('+1 anggota');
        $response->assertSee('Jakarta Pusat');
        $response->assertSee('02 Mar 2026');
        $response->assertSee('Belum ditentukan');
    }

    /** @test */
    public function index_shows_the_planned_instrument_title(): void
    {
        $standard = AuditStandard::create([
            'organization_id' => $this->org->id,
            'code' => 'STD-01',
            'title' => 'Standar Akreditasi Puskesmas',
            'status' => 'active',
        ]);

        $version = InstrumentVersion::create([
            'audit_standard_id' => $standard->id,
            'version_label' => 'v2026',
            'status' => 'active',
        ]);

        $this->makeAssignment('TUGAS-2026-002', ['instrument_version_id' => $version->id]);

        $response = $this->actingAs($this->viewer)->get('/audit/assignments');

        $response->assertStatus(200);
        $response->assertSee('Standar Akreditasi Puskesmas (v2026)');
    }

    /** @test */
    public function index_is_paginated(): void
    {
        for ($i = 1; $i <= 16; $i++) {
            $this->makeAssignment('TUGAS-2026-1'.str_pad((string) $i, 2, '0', STR_PAD_LEFT));
        }

        $response = $this->actingAs($this->viewer)->get('/audit/assignments');

        $response->assertStatus(200);

        $assignments = $response->viewData('assignments');

        $this->assertSame(15, $assignments->perPage());
        $this->assertSame(16, $assignments->total());
        $this->assertSame(15, $assignments->count());
        $this->assertTrue($assignments->hasPages());
        $response->assertSee('page=2', false);
    }

    /** @test */
    public function index_marks_assignments_that_passed_their_schedule(): void
    {
        $this->makeAssignment('TUGAS-2026-003', [
            'scheduled_start' => '2025-01-06',
            'scheduled_end' => '2025-01-10',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->viewer)->get('/audit/assignments');

        $response->assertStatus(200);
        $response->assertSee('Melewati jadwal');
    }
}
