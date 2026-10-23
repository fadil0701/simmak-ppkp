<?php

namespace Tests\Feature\Audit;

use App\Models\AuditFinding;
use App\Models\AuditTrail;
use App\Models\CapaAction;
use App\Models\CapaCase;
use App\Models\CapaVerification;
use App\Models\Clinic;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
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
            'capa.view', 'capa.manage',
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
            'audit.action.view', 'audit.action.update', 'audit.capa.verify',
            'capa.view', 'capa.manage',
        ]);

        $this->kepalaKlinik = Role::firstOrCreate(['name' => 'kepala-klinik', 'guard_name' => 'web']);
        $this->kepalaKlinik->syncPermissions([
            'audit.finding.view', 'audit.finding.close', 'audit.finding.reopen',
            'audit.capa.verify', 'capa.view', 'capa.manage', 'clinics.view',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function makeUser(string $email, string $role): User
    {
        $user = User::create([
            'organization_id' => $this->org->id,
            'name' => $email,
            'email' => $email,
            'password' => bcrypt('password'),
            'auth_provider' => 'local',
            'is_active' => true,
            'default_clinic_id' => $this->clinic->id,
        ]);
        $user->clinics()->attach($this->clinic->id, ['is_default' => true]);
        $user->assignRole($role);

        return $user;
    }

    protected function makeFinding(User $creator, string $code, string $status = 'pending_verification'): AuditFinding
    {
        return AuditFinding::create([
            'organization_id' => $this->org->id,
            'clinic_id' => $this->clinic->id,
            'finding_code' => $code,
            'title' => 'Temuan '.$code,
            'severity' => 'minor',
            'status' => $status,
            'identified_at' => now(),
            'created_by' => $creator->id,
            'updated_by' => $creator->id,
        ]);
    }

    protected function makeCapa(AuditFinding $finding, User $owner): CapaCase
    {
        return CapaCase::create([
            'organization_id' => $this->org->id,
            'clinic_id' => $this->clinic->id,
            'case_number' => 'CAPA-'.$finding->finding_code,
            'source_type' => 'audit_finding',
            'source_id' => $finding->id,
            'priority' => 'medium',
            'problem_statement' => 'Test',
            'status' => 'verification',
            'owner_id' => $owner->id,
            'target_close_date' => now()->addDays(30)->toDateString(),
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);
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
        $auditor = $this->makeUser('creator-close@test.local', 'auditor');
        $closer = $this->makeUser('closer@test.local', 'kepala-klinik');
        $finding = $this->makeFinding($auditor, 'TMN-CLOSE-001');
        $capa = $this->makeCapa($finding, $auditor);

        CapaVerification::create([
            'organization_id' => $this->org->id,
            'capa_case_id' => $capa->id,
            'verification_date' => now()->toDateString(),
            'result' => 'effective',
            'notes' => 'Efektif',
            'is_effective' => true,
            'verified_by' => $closer->id,
        ]);

        $response = $this->actingAs($closer)->post('/findings/'.$finding->id.'/close', ['close_reason' => 'Selesai']);
        $response->assertStatus(302);
        $this->assertDatabaseHas('audit_findings', [
            'id' => $finding->id,
            'status' => 'closed',
        ]);
    }

    /** @test */
    public function finding_cannot_be_closed_without_effective_verification(): void
    {
        $auditor = $this->makeUser('creator-open@test.local', 'auditor');
        $closer = $this->makeUser('closer-denied@test.local', 'kepala-klinik');
        $finding = $this->makeFinding($auditor, 'TMN-NOVERIFY-001');
        $this->makeCapa($finding, $auditor);

        $this->actingAs($closer)
            ->post('/findings/'.$finding->id.'/close', ['close_reason' => 'Selesai'])
            ->assertForbidden();

        $this->assertDatabaseHas('audit_findings', [
            'id' => $finding->id,
            'status' => 'pending_verification',
        ]);
    }

    /** @test */
    public function pic_cannot_verify_own_capa(): void
    {
        $pic = $this->makeUser('pic@test.local', 'auditor');
        $pic->givePermissionTo('audit.capa.verify');
        $finding = $this->makeFinding($this->makeUser('auditor-finding@test.local', 'auditor'), 'TMN-SOD-001', 'in_capa');
        $capa = $this->makeCapa($finding, $pic);

        CapaAction::create([
            'capa_case_id' => $capa->id,
            'action_type' => 'corrective',
            'description' => 'Perbaiki prosedur',
            'assigned_to' => $pic->id,
            'status' => 'evidence_submitted',
            'evidence_notes' => 'Sudah dikerjakan',
            'evidence_submitted_at' => now(),
        ]);

        $this->actingAs($pic)
            ->post('/capa/'.$capa->id.'/verification', [
                'verification_date' => now()->toDateString(),
                'result' => 'effective',
                'notes' => 'Saya nyatakan efektif',
            ])
            ->assertForbidden();
    }

    /** @test */
    public function ineffective_verification_returns_capa_to_action(): void
    {
        $pic = $this->makeUser('pic-follow@test.local', 'auditor');
        $verifier = $this->makeUser('verifier@test.local', 'auditor');
        $finding = $this->makeFinding($this->makeUser('auditor-ne@test.local', 'auditor'), 'TMN-NE-001', 'pending_verification');
        $capa = $this->makeCapa($finding, $pic);

        CapaAction::create([
            'capa_case_id' => $capa->id,
            'action_type' => 'corrective',
            'description' => 'Perbaiki prosedur',
            'assigned_to' => $pic->id,
            'status' => 'evidence_submitted',
            'evidence_notes' => 'Sudah dikerjakan',
            'evidence_submitted_at' => now(),
        ]);

        $this->actingAs($verifier)
            ->post('/capa/'.$capa->id.'/verification', [
                'verification_date' => now()->toDateString(),
                'result' => 'not_effective',
                'notes' => 'Belum merubah praktik',
                'new_target_close_date' => now()->addDays(14)->toDateString(),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('capa_cases', [
            'id' => $capa->id,
            'status' => 'action',
        ]);
        $this->assertDatabaseHas('audit_findings', [
            'id' => $finding->id,
            'status' => 'in_capa',
        ]);
    }

    /** @test */
    public function reopen_records_before_and_after_status(): void
    {
        $auditor = $this->makeUser('creator-reopen@test.local', 'auditor');
        $officer = $this->makeUser('reopener@test.local', 'kepala-klinik');
        $finding = $this->makeFinding($auditor, 'TMN-REOPEN-001', 'closed');
        $finding->update(['closed_at' => now()]);
        $this->makeCapa($finding, $auditor);

        $this->actingAs($officer)
            ->post('/findings/'.$finding->id.'/reopen', ['reopen_reason' => 'Temuan muncul lagi'])
            ->assertRedirect();

        $this->assertDatabaseHas('audit_findings', [
            'id' => $finding->id,
            'status' => 'in_capa',
        ]);
        $this->assertTrue(
            AuditTrail::query()
                ->where('event', 'audit.finding.reopened')
                ->where('auditable_id', $finding->id)
                ->exists()
        );
    }

    /**
     * 08_CAPA.md §3: Auditi/Kepala Klinik hanya menindaklanjuti dan mengunggah bukti;
     * validasi efektivitas adalah kewenangan auditor yang ditugaskan.
     *
     * @test
     */
    public function kepala_klinik_cannot_verify_capa(): void
    {
        $pic = $this->makeUser('pic-clinic@test.local', 'auditor');
        $kepalaKlinik = $this->makeUser('kepala-verify@test.local', 'kepala-klinik');
        $finding = $this->makeFinding(
            $this->makeUser('auditor-kk@test.local', 'auditor'),
            'TMN-KK-001',
            'pending_verification'
        );
        $capa = $this->makeCapa($finding, $pic);

        CapaAction::create([
            'capa_case_id' => $capa->id,
            'action_type' => 'corrective',
            'description' => 'Perbaiki prosedur',
            'assigned_to' => $pic->id,
            'status' => 'evidence_submitted',
            'evidence_notes' => 'Sudah dikerjakan',
            'evidence_submitted_at' => now(),
        ]);

        $this->actingAs($kepalaKlinik)
            ->post('/capa/'.$capa->id.'/verification', [
                'verification_date' => now()->toDateString(),
                'result' => 'effective',
                'notes' => 'Validasi sendiri',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('capa_verifications', [
            'capa_case_id' => $capa->id,
        ]);
    }
}
