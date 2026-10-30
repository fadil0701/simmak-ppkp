<?php

namespace Tests\Feature\Audit;

use App\Models\AuditAssignment;
use App\Models\AuditFinding;
use App\Models\AuditProgram;
use App\Models\AuditTeamMember;
use App\Models\CapaCase;
use App\Models\Clinic;
use App\Models\ClinicalAudit;
use App\Models\ClinicalAuditItem;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Modul Audit Klinis: CRUD, elemen penilaian, dan integrasi ke modul Temuan
 * Audit + Tindak Lanjut bersama (tanpa alur terpisah).
 */
class ClinicalAuditTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;

    private Clinic $clinic;

    private Clinic $otherClinic;

    private User $user;

    private AuditAssignment $assignment;

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

        $this->otherClinic = Clinic::create([
            'organization_id' => $this->org->id,
            'code' => 'KP02',
            'name' => 'Klinik Pratama Jakarta Barat',
            'clinic_type' => 'pratama',
            'is_main_clinic' => false,
            'city' => 'Jakarta Barat',
            'is_active' => true,
        ]);

        $permissions = [
            'audit.clinical.view', 'audit.clinical.manage',
            'audit.view', 'audit.manage',
            'audit.finding.view', 'audit.finding.create', 'audit.finding.update',
            'audit.action.view', 'audit.action.update', 'audit.capa.verify',
            'capa.view', 'capa.manage',
            'audit.evidence.view', 'audit.evidence.upload', 'audit.evidence.download',
            'audit.report.view',
            'clinics.view',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $role = Role::firstOrCreate(['name' => 'clinical-auditor', 'guard_name' => 'web']);
        $role->syncPermissions($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->user = $this->makeUser('auditor.klinis@test.local', $role->name, $this->clinic);
        $this->assignment = $this->makePublishedAssignment($this->user, $this->clinic);
    }

    private function makeUser(string $email, string $role, Clinic $clinic): User
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

    private function makePublishedAssignment(User $auditor, Clinic $clinic, string $code = 'TUGAS-AKL-001'): AuditAssignment
    {
        $program = AuditProgram::create([
            'organization_id' => $this->org->id,
            'program_code' => 'PRG-'.$code,
            'name' => 'Program '.$code,
            'status' => 'approved',
            'created_by' => $auditor->id,
            'updated_by' => $auditor->id,
        ]);

        $assignment = AuditAssignment::create([
            'organization_id' => $this->org->id,
            'audit_program_id' => $program->id,
            'clinic_id' => $clinic->id,
            'assignment_code' => $code,
            'title' => 'Penugasan '.$code,
            'lead_auditor_id' => $auditor->id,
            'scheduled_start' => now()->toDateString(),
            'scheduled_end' => now()->addDays(3)->toDateString(),
            'status' => 'approved',
            'conflict_check_status' => 'resolved',
            'created_by' => $auditor->id,
            'updated_by' => $auditor->id,
        ]);

        AuditTeamMember::create([
            'organization_id' => $this->org->id,
            'audit_assignment_id' => $assignment->id,
            'user_id' => $auditor->id,
            'team_role' => 'lead_auditor',
            'independence_declared' => true,
            'conflict_declared' => false,
            'active' => true,
            'created_by' => $auditor->id,
            'updated_by' => $auditor->id,
        ]);

        return $assignment;
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Audit Klinis Pelayanan Farmasi',
            'audit_assignment_id' => $this->assignment->id,
            'clinic_id' => $this->otherClinic->id,
            'audit_date' => now()->toDateString(),
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->toDateString(),
            'auditor_id' => '00000000-0000-0000-0000-000000000099',
            'pic_user_id' => $this->user->id,
            'standard_reference' => 'SPO Pelayanan Farmasi No. 12/2025',
            'scope' => 'Pelayanan resep dan penyerahan obat',
            'criteria' => 'Kesesuaian prosedur pelayanan farmasi',
            'indicators' => 'Kepatuhan penelaahan resep',
            'objective_evidence' => 'Rekam pelayanan farmasi bulan ini',
            'assessment_result' => 'Sebagian besar sesuai',
            'status' => 'draft',
        ], $overrides);
    }

    /** @test */
    public function audit_klinis_can_be_created_listed_and_viewed(): void
    {
        $this->actingAs($this->user)
            ->get(route('audit.clinical.create'))
            ->assertOk()
            ->assertSee('Form Audit Klinis')
            ->assertSee('Jadwal Penugasan Audit')
            ->assertSee('TUGAS-AKL-001')
            ->assertSee('Rekam Medis')
            ->assertSee('Uraian Ketidaksesuaian')
            ->assertSee('Analisis Ketidaksesuaian / Masalah')
            ->assertDontSee('Penanggung Jawab (PIC)')
            ->assertDontSee('Hasil Penilaian')
            ->assertDontSee('Periode Mulai')
            ->assertDontSee('Ruang Lingkup')
            ->assertDontSee('Rujukan Kriteria');

        $this->actingAs($this->user)
            ->post(route('audit.clinical.store'), $this->payload())
            ->assertRedirect();

        $audit = ClinicalAudit::query()->firstOrFail();

        $this->assertSame($this->org->id, $audit->organization_id);
        $this->assertSame($this->clinic->id, $audit->clinic_id);
        $this->assertSame($this->assignment->id, $audit->audit_assignment_id);
        $this->assertSame($this->user->id, $audit->auditor_id);
        $this->assertMatchesRegularExpression('/^AKL-\d{4}-\d{4}$/', $audit->audit_number);

        $this->actingAs($this->user)
            ->get(route('audit.clinical.index'))
            ->assertOk()
            ->assertSee($audit->audit_number)
            ->assertSee('Audit Klinis TUGAS-AKL-001');

        $this->actingAs($this->user)
            ->get(route('audit.clinical.show', $audit))
            ->assertOk()
            ->assertSee($audit->audit_number)
            ->assertSee('Temuan Audit Klinis')
            ->assertSee('Buat Temuan')
            ->assertSee('Jadwal Penugasan')
            ->assertSee('Rekam Medis')
            ->assertSee('Uraian Ketidaksesuaian')
            ->assertDontSee('Elemen Penilaian')
            ->assertDontSee('Tambah Elemen')
            ->assertDontSee('Unggah Dokumentasi')
            ->assertDontSee('Penanggung Jawab')
            ->assertDontSee('Nilai Kesesuaian')
            ->assertDontSee('Standar Acuan');
    }

    /** @test */
    public function audit_klinis_can_be_updated_and_status_changed(): void
    {
        $this->actingAs($this->user)->post(route('audit.clinical.store'), $this->payload());
        $audit = ClinicalAudit::query()->firstOrFail();

        $originalTitle = $audit->title;

        $this->actingAs($this->user)
            ->put(route('audit.clinical.update', $audit), $this->payload([
                'title' => 'Judul palsu dari request',
                'conclusion' => 'Tidak boleh tersimpan dari form ini',
                'medical_record_ref' => 'RM-UPD-01',
                'audit_number' => $audit->audit_number,
            ]))
            ->assertRedirect(route('audit.clinical.show', $audit));

        $this->assertSame($originalTitle, $audit->fresh()->title);
        $this->assertNull($audit->fresh()->conclusion);
        $this->assertSame('RM-UPD-01', $audit->fresh()->medical_record_ref);

        $this->actingAs($this->user)
            ->post(route('audit.clinical.status', $audit), ['status' => 'completed'])
            ->assertRedirect();

        $this->assertSame('completed', $audit->fresh()->status);

        // Halaman edit tetap dapat dirender selama audit belum ditutup.
        $audit->update(['status' => 'in_progress']);

        $this->actingAs($this->user)
            ->get(route('audit.clinical.edit', $audit))
            ->assertOk()
            ->assertSee('Form Audit Klinis');
    }

    /** @test */
    public function elements_compute_the_conformity_score(): void
    {
        $this->actingAs($this->user)->post(route('audit.clinical.store'), $this->payload());
        $audit = ClinicalAudit::query()->firstOrFail();

        $this->actingAs($this->user)
            ->post(route('audit.clinical.items.store', $audit), [
                'code' => 'EP-1',
                'element' => 'Penelaahan resep dilakukan apoteker',
                'indicator' => 'Kelengkapan telaah resep',
                'result' => 'partial',
                'score' => 5,
            ])
            ->assertRedirect();

        $this->assertSame(1, ClinicalAuditItem::query()->where('clinical_audit_id', $audit->id)->count());
        $this->assertSame(50, $audit->fresh()->conformity_score);

        $item = ClinicalAuditItem::query()->firstOrFail();

        $this->actingAs($this->user)
            ->delete(route('audit.clinical.items.destroy', [$audit, $item]))
            ->assertRedirect();

        $this->assertSame(0, ClinicalAuditItem::query()->count());
        $this->assertNull($audit->fresh()->conformity_score);
    }

    /** @test */
    public function clinical_finding_enters_the_shared_findings_module_and_follow_up_flow(): void
    {
        $this->actingAs($this->user)->post(route('audit.clinical.store'), $this->payload());
        $audit = ClinicalAudit::query()->firstOrFail();

        // Form temuan menampilkan konteks audit klinis.
        $this->actingAs($this->user)
            ->get(route('findings.create', ['clinical_audit_id' => $audit->id]))
            ->assertOk()
            ->assertSee('Temuan dari Audit Klinis')
            ->assertSee($audit->audit_number);

        $this->actingAs($this->user)
            ->post(route('findings.store'), [
                'clinical_audit_id' => $audit->id,
                'clinic_id' => $this->clinic->id,
                'finding_code' => 'TMN-HACK-0001',
                'title' => 'Penelaahan resep tidak terdokumentasi',
                'severity' => 'major',
                'finding_type' => 'non_conformity',
                'description' => 'Telaah resep tidak tercatat pada rekam pelayanan.',
                'owner_user_id' => $this->user->id,
                'status' => 'confirmed',
            ])
            ->assertRedirect();

        $finding = AuditFinding::query()->where('clinical_audit_id', $audit->id)->firstOrFail();
        $this->assertMatchesRegularExpression('/^TMN-\d{4}-\d{4}$/', $finding->finding_code);
        $this->assertNotSame('TMN-HACK-0001', $finding->finding_code);
        $this->assertSame($audit->id, $finding->clinical_audit_id);
        $this->assertNull($finding->audit_execution_id);
        $this->assertSame($this->clinic->id, $finding->clinic_id);

        // Masuk ke daftar Temuan Audit bersama.
        $this->actingAs($this->user)
            ->get(route('findings.index'))
            ->assertOk()
            ->assertSee($finding->finding_code);

        // Tindak lanjut dibuat lewat modul CAPA yang sudah ada.
        $this->actingAs($this->user)
            ->post(route('findings.create-capa', $finding))
            ->assertRedirect();

        $capa = CapaCase::query()->where('source_id', $finding->id)->firstOrFail();
        $this->assertSame('audit_finding', $capa->source_type);
        $this->assertSame($this->clinic->id, $capa->clinic_id);

        $this->actingAs($this->user)
            ->get(route('capa.show', $capa))
            ->assertOk()
            ->assertSee($finding->finding_code);

        // Audit klinis dengan temuan tidak boleh dihapus (jejak audit dijaga).
        $this->actingAs($this->user)
            ->delete(route('audit.clinical.destroy', $audit))
            ->assertRedirect();

        $this->assertDatabaseHas('clinical_audits', ['id' => $audit->id, 'deleted_at' => null]);
    }

    /** @test */
    public function unpublished_or_foreign_assignment_cannot_be_used_and_fields_cannot_be_spoofed(): void
    {
        $draft = $this->makePublishedAssignment($this->user, $this->clinic, 'TUGAS-AKL-DRF');
        $draft->update(['status' => 'proposed']);

        $this->actingAs($this->user)
            ->post(route('audit.clinical.store'), $this->payload([
                'audit_assignment_id' => $draft->id,
            ]))
            ->assertSessionHasErrors('audit_assignment_id');

        $outsider = $this->makeUser('auditor.lain@test.local', 'clinical-auditor', $this->otherClinic);
        $theirs = $this->makePublishedAssignment($outsider, $this->otherClinic, 'TUGAS-AKL-OTH');

        $this->actingAs($this->user)
            ->post(route('audit.clinical.store'), $this->payload([
                'audit_assignment_id' => $theirs->id,
            ]))
            ->assertSessionHasErrors('audit_assignment_id');

        $this->actingAs($this->user)
            ->post(route('audit.clinical.store'), $this->payload([
                'audit_number' => 'AKL-HACK-0001',
                'clinic_id' => $this->otherClinic->id,
            ]))
            ->assertRedirect();

        $audit = ClinicalAudit::query()->firstOrFail();
        $this->assertNotSame('AKL-HACK-0001', $audit->audit_number);
        $this->assertSame($this->clinic->id, $audit->clinic_id);
        $this->assertSame($this->user->id, $audit->auditor_id);
    }

    /** @test */
    public function medical_record_and_nonconformity_are_saved_and_finding_is_not_duplicated(): void
    {
        $this->actingAs($this->user)
            ->post(route('audit.clinical.store'), $this->payload([
                'medical_record_ref' => 'RM-2026-001',
                'has_nonconformity' => '1',
                'nonconformity_notes' => 'Telaah resep tidak tercatat.',
                'problem_analysis' => 'Tidak ada checklist telaah di loket.',
            ]))
            ->assertRedirect();

        $audit = ClinicalAudit::query()->firstOrFail();
        $this->assertSame('RM-2026-001', $audit->medical_record_ref);
        $this->assertSame('Telaah resep tidak tercatat.', $audit->nonconformity_notes);
        $this->assertSame('Tidak ada checklist telaah di loket.', $audit->problem_analysis);

        $this->actingAs($this->user)
            ->get(route('audit.clinical.show', $audit))
            ->assertOk()
            ->assertSee('RM-2026-001')
            ->assertSee('Telaah resep tidak tercatat.')
            ->assertSee('Tidak ada checklist telaah di loket.');

        $this->assertSame(1, AuditFinding::query()->where('clinical_audit_id', $audit->id)->count());
        $finding = AuditFinding::query()->where('clinical_audit_id', $audit->id)->firstOrFail();
        $this->assertSame('TMN-'.$audit->audit_number, $finding->finding_code);
        $this->assertNotNull($finding->capaCase);

        $this->actingAs($this->user)
            ->put(route('audit.clinical.update', $audit), $this->payload([
                'title' => 'Audit Klinis Pelayanan Farmasi (revisi)',
                'has_nonconformity' => '1',
                'nonconformity_notes' => 'Telaah resep masih kosong pada 3 sampel.',
                'problem_analysis' => 'SPO belum disosialisasikan.',
                'medical_record_ref' => 'RM-2026-001',
            ]))
            ->assertRedirect();

        $this->assertSame(1, AuditFinding::query()->where('clinical_audit_id', $audit->id)->count());
        $this->assertStringContainsString('3 sampel', $finding->fresh()->description);
    }

    /** @test */
    public function audit_klinis_can_be_deleted_when_no_findings_and_no_documents(): void
    {
        $this->actingAs($this->user)->post(route('audit.clinical.store'), $this->payload());
        $audit = ClinicalAudit::query()->firstOrFail();

        $this->actingAs($this->user)
            ->delete(route('audit.clinical.destroy', $audit))
            ->assertRedirect(route('audit.clinical.index'));

        $this->assertSoftDeleted('clinical_audits', ['id' => $audit->id]);
    }

    /** @test */
    public function unit_scope_limits_audit_klinis_access(): void
    {
        $this->actingAs($this->user)->post(route('audit.clinical.store'), $this->payload());
        $audit = ClinicalAudit::query()->firstOrFail();

        $outsider = $this->makeUser('klinik.lain@test.local', 'clinical-auditor', $this->otherClinic);

        $this->actingAs($outsider)
            ->get(route('audit.clinical.show', $audit))
            ->assertForbidden();

        $this->actingAs($outsider)
            ->get(route('audit.clinical.index'))
            ->assertOk()
            ->assertDontSee($audit->audit_number);
    }
}
