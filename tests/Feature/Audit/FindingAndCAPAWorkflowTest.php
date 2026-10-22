<?php

namespace Tests\Feature\Audit;

use App\Models\AuditFinding;
use App\Models\CapaCase;
use App\Models\CapaVerification;
use App\Models\Clinic;
use App\Models\Organization;
use App\Models\StandardSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FindingAndCAPAWorkflowTest extends TestCase
{
    use RefreshDatabase;

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
            'name' => 'Test Auditor',
            'email' => 'auditor@test.local',
            'password' => bcrypt('password'),
            'auth_provider' => 'local',
            'is_active' => true,
            'default_clinic_id' => $this->clinic->id,
        ]);
        $this->user->clinics()->attach($this->clinic->id, ['is_default' => true]);

        $permissions = [
            'audit.finding.view', 'audit.finding.create', 'audit.finding.update',
            'audit.finding.review', 'audit.finding.close', 'audit.finding.reopen',
            'audit.action.view', 'audit.action.update', 'audit.capa.verify',
            'clinics.view',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $this->admin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->admin->syncPermissions(Permission::where('guard_name', 'web')->pluck('name'));

        $this->auditor = Role::firstOrCreate(['name' => 'auditor', 'guard_name' => 'web']);
        $this->auditor->syncPermissions([
            'audit.finding.view', 'audit.finding.create', 'audit.finding.update',
            'audit.action.view', 'audit.action.update',
        ]);

        $this->kepalaKlinik = Role::firstOrCreate(['name' => 'kepala-klinik', 'guard_name' => 'web']);
        $this->kepalaKlinik->syncPermissions([
            'audit.finding.view', 'clinics.view',
        ]);

        $kepala = Role::firstOrCreate(['name' => 'kepala-klinik', 'guard_name' => 'web']);
        $kepala->syncPermissions(['audit.finding.view']);
    }

    protected function createStandardHierarchy(): array
    {
        $section = StandardSection::create([
            'instrument_version_id => null',
        ]);

        // This is simplified; in real usage, instrument_version_id is required
        return [];
    }

    /** @test */
    public function auditor_can_create_finding(): void
    {
        $this->user->assignRole('auditor');

        $finding = AuditFinding::create([
            'organization_id' => $this->org->id,
            'clinic_id' => $this->clinic->id,
            'finding_code' => 'TMN-2026-001',
            'title' => 'Temuan Contoh',
            'severity' => 'major',
            'finding_type' => 'non_compliance',
            'condition' => 'Kondisi aktual tidak sesuai standar',
            'requirement' => 'Kriteria yang seharusnya dipenuhi',
            'impact' => 'Dampak pada pelayanan',
            'recommendation' => 'Rekomendasi perbaikan',
            'status' => 'draft',
            'identified_at' => now(),
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $this->assertDatabaseHas('audit_findings', [
            'finding_code' => 'TMN-2026-001',
            'severity' => 'major',
            'status' => 'draft',
        ]);
    }

    /** @test */
    public function finding_code_must_be_unique_per_organization_per_year(): void
    {
        $this->user->assignRole('super-admin');

        AuditFinding::create([
            'organization_id' => $this->org->id,
            'clinic_id' => $this->clinic->id,
            'finding_code' => 'TMN-DUP-001',
            'title' => 'First Finding',
            'severity' => 'minor',
            'status' => 'draft',
            'identified_at' => now(),
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->post('/findings', [
            'organization_id' => $this->org->id,
            'clinic_id' => $this->clinic->id,
            'finding_code' => 'TMN-DUP-001',
            'title' => 'Duplicate Finding',
            'severity' => 'minor',
            'status' => 'draft',
        ]);

        $response->assertSessionHasErrors('finding_code');
    }

    /** @test */
    public function kepala_klinik_cannot_access_finding_from_other_clinic(): void
    {
        $this->user->assignRole('kepala-klinik');

        $otherClinic = Clinic::create([
            'organization_id' => $this->org->id,
            'code' => 'KP99',
            'name' => 'Out of Scope Clinic',
            'clinic_type' => 'pratama',
            'is_main_clinic' => false,
            'city' => 'Jakarta Selatan',
            'is_active' => true,
        ]);

        $finding = AuditFinding::create([
            'organization_id' => $this->org->id,
            'clinic_id' => $otherClinic->id,
            'finding_code' => 'TMN-OTHER-001',
            'title' => 'Other Clinic Finding',
            'severity' => 'minor',
            'status' => 'draft',
            'identified_at' => now(),
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get('/findings/'.$finding->id);
        $response->assertStatus(403);
    }

    /** @test */
    public function finding_can_be_closed_after_verification(): void
    {
        $this->user->assignRole('super-admin');

        $finding = AuditFinding::create([
            'organization_id' => $this->org->id,
            'clinic_id' => $this->clinic->id,
            'finding_code' => 'TMN-CLOSE-001',
            'title' => 'To Close Finding',
            'severity' => 'minor',
            'status' => 'pending_verification',
            'identified_at' => now(),
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $capa = CapaCase::create([
            'organization_id' => $this->org->id,
            'clinic_id' => $this->clinic->id,
            'case_number' => 'CAPA-2026-0001',
            'source_type' => 'audit_finding',
            'source_id' => $finding->id,
            'priority' => 'medium',
            'problem_statement' => 'Test',
            'status' => 'open',
            'owner_id' => $this->user->id,
            'target_close_date' => now()->addDays(30)->toDateString(),
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        CapaVerification::create([
            'organization_id' => $this->org->id,
            'capa_case_id' => $capa->id,
            'verification_date' => now()->toDateString(),
            'result' => 'effective',
            'notes' => 'Efektif',
            'is_effective' => true,
            'verified_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->post('/findings/'.$finding->id.'/close', ['close_reason' => 'Selesai']);
        $response->assertStatus(302);
        $this->assertDatabaseHas('audit_findings', [
            'id' => $finding->id,
            'status' => 'closed',
        ]);
    }
}
