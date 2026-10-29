<?php

namespace Tests\Feature\Audit;

use App\Models\ActionEvidence;
use App\Models\AuditFinding;
use App\Models\AuditReport;
use App\Models\CapaAction;
use App\Models\CapaCase;
use App\Models\Clinic;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Laporan Audit dan Bukti Arsip harus terpisah per tindak lanjut:
 * dokumen milik satu tindakan tidak boleh tampil sebagai milik tindakan lain,
 * sementara data lama (tanpa tautan tindak lanjut) tetap dapat diakses.
 */
class FollowUpDocumentSeparationTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;

    private Clinic $clinic;

    private User $user;

    private User $otherPic;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

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

        $permissions = [
            'capa.view', 'capa.manage',
            'audit.action.view', 'audit.action.update', 'audit.action.submit',
            'audit.finding.view', 'audit.finding.create', 'audit.finding.update',
            'audit.evidence.view', 'audit.evidence.upload', 'audit.evidence.download',
            'audit.report.view', 'audit.report.manage',
            'clinics.view',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $role = Role::firstOrCreate(['name' => 'capa-operator', 'guard_name' => 'web']);
        $role->syncPermissions($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->user = $this->makeUser('pic@test.local', $role->name);
        $this->otherPic = $this->makeUser('pic2@test.local', $role->name);
    }

    private function makeUser(string $email, string $role): User
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

    /**
     * @return array{0: CapaCase, 1: CapaAction, 2: CapaAction}
     */
    private function makeCaseWithTwoActions(): array
    {
        $finding = AuditFinding::create([
            'organization_id' => $this->org->id,
            'clinic_id' => $this->clinic->id,
            'finding_code' => 'TMN-FOLLOWUP-001',
            'title' => 'Temuan untuk pemisahan dokumen',
            'severity' => 'minor',
            'status' => 'in_capa',
            'identified_at' => now(),
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $capa = CapaCase::create([
            'organization_id' => $this->org->id,
            'clinic_id' => $this->clinic->id,
            'case_number' => 'TL-2026-0001',
            'source_type' => 'audit_finding',
            'source_id' => $finding->id,
            'priority' => 'medium',
            'problem_statement' => 'Tindak lanjut pemisahan dokumen',
            'status' => 'action',
            'owner_id' => $this->user->id,
            'target_close_date' => now()->addDays(30)->toDateString(),
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $actionA = CapaAction::create([
            'capa_case_id' => $capa->id,
            'action_type' => 'corrective',
            'description' => 'Tindakan A — perbaikan prosedur',
            'assigned_to' => $this->user->id,
            'status' => 'open',
        ]);

        $actionB = CapaAction::create([
            'capa_case_id' => $capa->id,
            'action_type' => 'preventive',
            'description' => 'Tindakan B — pencegahan berulang',
            'assigned_to' => $this->otherPic->id,
            'status' => 'open',
        ]);

        return [$capa, $actionA, $actionB];
    }

    private function fakePdf(string $name): UploadedFile
    {
        // Berkas PDF minimal: validasi magic bytes pada AuditEvidenceService
        // memerlukan isi berkas yang benar-benar dikenali sebagai application/pdf.
        $content = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n";

        return UploadedFile::fake()->createWithContent($name, $content);
    }

    /** @test */
    public function evidence_is_attached_only_to_its_own_follow_up(): void
    {
        [$capa, $actionA, $actionB] = $this->makeCaseWithTwoActions();

        $this->actingAs($this->user)
            ->post(route('capa.attach-evidence', $actionA), [
                'file' => $this->fakePdf('bukti-tindakan-a.pdf'),
                'description' => 'Bukti untuk tindakan A',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('action_evidence', ['corrective_action_id' => $actionA->id]);
        $this->assertDatabaseMissing('action_evidence', ['corrective_action_id' => $actionB->id]);

        $response = $this->actingAs($this->user)->get(route('capa.show', $capa));
        $response->assertOk();
        $response->assertSee('Laporan Tindak Lanjut');
        $response->assertSee('Bukti Arsip');
        $response->assertSee('bukti-tindakan-a.pdf');

        $capaView = $response->viewData('capa');
        $this->assertCount(1, $capaView->actions->firstWhere('id', $actionA->id)->evidenceLinks);
        $this->assertCount(0, $capaView->actions->firstWhere('id', $actionB->id)->evidenceLinks);
    }

    /** @test */
    public function follow_up_report_stays_with_its_action(): void
    {
        [$capa, $actionA, $actionB] = $this->makeCaseWithTwoActions();

        $this->actingAs($this->user)
            ->post(route('capa.store-report', $actionA), [
                'title' => 'Laporan Tindakan A',
                'report_type' => 'capa',
                'file' => $this->fakePdf('laporan-tindakan-a.pdf'),
            ])
            ->assertRedirect();

        $report = AuditReport::query()->where('title', 'Laporan Tindakan A')->firstOrFail();

        $this->assertSame($actionA->id, $report->capa_action_id);
        $this->assertSame($capa->id, $report->action->capaCase->id);
        $this->assertSame($capa->source_id, $report->audit_finding_id);
        $this->assertTrue(Storage::disk('local')->exists($report->file_path));
        $this->assertDatabaseMissing('audit_reports', ['capa_action_id' => $actionB->id]);

        $this->actingAs($this->user)
            ->get(route('capa.download-report', $report))
            ->assertOk();

        $page = $this->actingAs($this->user)->get(route('capa.show', $capa));
        $page->assertOk();
        $page->assertSee('Laporan Tindakan A');

        $capaView = $page->viewData('capa');
        $this->assertCount(1, $capaView->actions->firstWhere('id', $actionA->id)->reports);
        $this->assertCount(0, $capaView->actions->firstWhere('id', $actionB->id)->reports);

        // Laporan audit tetap tampil di menu Laporan Audit dengan konteks tindak lanjut.
        $this->actingAs($this->user)
            ->get(route('audit.reports.index'))
            ->assertOk()
            ->assertSee('Laporan Tindakan A')
            ->assertSee($capa->case_number);

        $this->actingAs($this->user)
            ->get(route('audit.reports.show', $report))
            ->assertOk()
            ->assertSee('Laporan ini milik tindak lanjut');
    }

    /** @test */
    public function detaching_evidence_keeps_the_archived_file(): void
    {
        [$capa, $actionA] = $this->makeCaseWithTwoActions();

        $this->actingAs($this->user)
            ->post(route('capa.attach-evidence', $actionA), [
                'file' => $this->fakePdf('bukti-dilepas.pdf'),
            ])
            ->assertRedirect();

        $link = ActionEvidence::query()->where('corrective_action_id', $actionA->id)->firstOrFail();
        $evidence = $link->evidence;

        $this->actingAs($this->user)
            ->delete(route('capa.detach-evidence', [$actionA, $link]))
            ->assertRedirect();

        $this->assertDatabaseMissing('action_evidence', ['id' => $link->id]);
        $this->assertDatabaseHas('evidence', ['id' => $evidence->id, 'deleted_at' => null]);
        $this->assertTrue(Storage::disk('local')->exists($evidence->storage_path));
    }

    /** @test */
    public function finding_detail_groups_documents_per_follow_up(): void
    {
        [$capa, $actionA] = $this->makeCaseWithTwoActions();

        $this->actingAs($this->user)
            ->post(route('capa.attach-evidence', $actionA), [
                'file' => $this->fakePdf('bukti-khusus-a.pdf'),
            ])
            ->assertRedirect();

        $page = $this->actingAs($this->user)->get(route('findings.show', $capa->source_id));

        $page->assertOk();
        $page->assertSee('Dokumen per Tindak Lanjut');
        $page->assertSee('Tindakan A');
        $page->assertSee('bukti-khusus-a.pdf');
    }

    /** @test */
    public function archive_shows_context_and_keeps_untethered_evidence(): void
    {
        $this->actingAs($this->user)
            ->post(route('audit.evidence.store'), [
                'file' => $this->fakePdf('bukti-lama.pdf'),
                'clinic_id' => $this->clinic->id,
                'description' => 'Bukti lama tanpa tindak lanjut',
            ])
            ->assertRedirect();

        $page = $this->actingAs($this->user)->get(route('audit.evidence.index'));

        $page->assertOk();
        $page->assertSee('bukti-lama.pdf');
        $page->assertSee('Tidak tertaut');
    }
}
