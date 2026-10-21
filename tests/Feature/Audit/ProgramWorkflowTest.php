<?php

namespace Tests\Feature\Audit;

use App\Models\AuditProgram;
use App\Models\Clinic;
use App\Models\FiscalYear;
use App\Models\Organization;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProgramWorkflowTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Organization::create([
            'code' => 'PPKP-DKI',
            'name' => 'PPKP Provinsi DKI Jakarta',
            'org_type' => 'central',
            'is_active' => true,
        ]);

        $this->fiscalYear = FiscalYear::create([
            'organization_id' => $this->org->id,
            'code' => 'FY2026',
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
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

        $permissions = [
            'audit.program.view', 'audit.program.create', 'audit.program.update',
            'audit.program.submit', 'audit.program.approve',
            'clinics.view',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $this->admin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->admin->syncPermissions(Permission::where('guard_name', 'web')->pluck('name'));

        $this->pjMutu = Role::firstOrCreate(['name' => 'pj-mutu', 'guard_name' => 'web']);
        $this->pjMutu->syncPermissions([
            'audit.program.view', 'audit.program.create', 'audit.program.update', 'audit.program.submit',
        ]);
    }

    protected function createUser(string $role, array $clinicCodes = []): User
    {
        $user = User::create([
            'organization_id' => $this->org->id,
            'name' => 'Test User ' . $role,
            'email' => strtolower($role) . '@test.local',
            'password' => bcrypt('password'),
            'auth_provider' => 'local',
            'is_active' => true,
            'default_clinic_id' => $this->clinic->id,
        ]);

        $user->assignRole($role);

        foreach ($clinicCodes as $code) {
            $clinic = Clinic::where('organization_id', $this->org->id)->where('code', $code)->first();
            if ($clinic) {
                $user->clinics()->attach($clinic->id, ['is_default' => true]);
            }
        }

        return $user;
    }

    /** @test */
    public function admin_can_create_audit_program(): void
    {
        $user = $this->createUser('super-admin', ['KP01']);

        $response = $this->actingAs($user)->post('/audit/programs', [
            'organization_id' => $this->org->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'program_code' => 'PROG-2026-001',
            'name' => 'Audit Tahunan 2026',
            'objective' => 'Evaluasi mutu pelayanan klinik tahun 2026',
            'status' => 'draft',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('audit_programs', [
            'program_code' => 'PROG-2026-001',
            'name' => 'Audit Tahunan 2026',
            'organization_id' => $this->org->id,
        ]);
    }

    /** @test */
    public function program_code_must_be_unique_per_organization(): void
    {
        $user = $this->createUser('super-admin', ['KP01']);

        AuditProgram::create([
            'organization_id' => $this->org->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'program_code' => 'PROG-DUPLICATE',
            'name' => 'First Program',
            'status' => 'draft',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->post('/audit/programs', [
            'organization_id' => $this->org->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'program_code' => 'PROG-DUPLICATE',
            'name' => 'Second Program',
            'status' => 'draft',
        ]);

        $response->assertSessionHasErrors('program_code');
    }

    /** @test */
    public function user_cannot_access_program_from_other_organization(): void
    {
        $otherOrg = Organization::create([
            'code' => 'OTHER-ORG',
            'name' => 'Other Organization',
            'org_type' => 'central',
            'is_active' => true,
        ]);

        $otherClinic = Clinic::create([
            'organization_id' => $otherOrg->id,
            'code' => 'OK01',
            'name' => 'Other Clinic',
            'clinic_type' => 'pratama',
            'is_main_clinic' => false,
            'is_active' => true,
        ]);

        $user = $this->createUser('super-admin', ['KP01']);

        $program = AuditProgram::create([
            'organization_id' => $otherOrg->id,
            'fiscal_year_id' => null,
            'program_code' => 'OTHER-001',
            'name' => 'Other Program',
            'status' => 'draft',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/audit/programs/' . $program->id);
        $response->assertStatus(403);
    }

    /** @test */
    public function submit_and_approve_program_flow(): void
    {
        $submitter = $this->createUser('pj-mutu', ['KP01']);
        $approver = $this->createUser('super-admin', ['KP01']);

        $program = AuditProgram::create([
            'organization_id' => $this->org->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'program_code' => 'PROG-FLOW-001',
            'name' => 'Flow Test Program',
            'status' => 'draft',
            'created_by' => $submitter->id,
            'updated_by' => $submitter->id,
        ]);

        // Submit
        $response = $this->actingAs($submitter)->post('/audit/programs/' . $program->id . '/submit');
        $response->assertStatus(302);
        $this->assertDatabaseHas('audit_programs', [
            'id' => $program->id,
            'status' => 'submitted_for_review',
        ]);

        // Approver (different user) approves
        $response = $this->actingAs($approver)->post('/audit/programs/' . $program->id . '/approve');
        $response->assertStatus(302);
        $this->assertDatabaseHas('audit_programs', [
            'id' => $program->id,
            'status' => 'approved',
        ]);
    }

    /** @test */
    public function submitter_cannot_approve_own_program(): void
    {
        $user = $this->createUser('pj-mutu', ['KP01']);

        $program = AuditProgram::create([
            'organization_id' => $this->org->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'program_code' => 'PROG-SELF-001',
            'name' => 'Self Approve Test',
            'status' => 'submitted_for_review',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->post('/audit/programs/' . $program->id . '/approve');
        $response->assertStatus(403);
    }
}