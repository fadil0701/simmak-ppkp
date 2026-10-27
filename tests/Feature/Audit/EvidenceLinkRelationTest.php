<?php

namespace Tests\Feature\Audit;

use App\Models\AuditFinding;
use App\Models\Clinic;
use App\Models\Evidence;
use App\Models\FindingEvidence;
use App\Models\Organization;
use App\Models\ResponseEvidence;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Regresi QueryException: relasi bukti pada temuan/CAPA memakai foreign key
 * yang tidak ada di tabel pivot (mis. `audit_finding_id` padahal kolomnya
 * `finding_id`), dan model pivot memakai BaseModel (soft delete) padahal
 * tabelnya tanpa `deleted_at`.
 */
class EvidenceLinkRelationTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;

    private Clinic $clinic;

    private User $user;

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

        foreach (['audit.finding.view', 'audit.evidence.view', 'audit.evidence.download'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $role->syncPermissions(Permission::where('guard_name', 'web')->pluck('name'));
        $this->user->assignRole($role);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function makeFinding(string $code): AuditFinding
    {
        return AuditFinding::create([
            'organization_id' => $this->org->id,
            'clinic_id' => $this->clinic->id,
            'finding_code' => $code,
            'title' => 'Temuan '.$code,
            'severity' => 'minor',
            'status' => 'draft',
            'identified_at' => now(),
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);
    }

    private function makeEvidence(string $filename): Evidence
    {
        return Evidence::create([
            'organization_id' => $this->org->id,
            'clinic_id' => $this->clinic->id,
            'uploaded_by' => $this->user->id,
            'storage_disk' => 'local',
            'storage_path' => 'evidence/'.$filename,
            'original_filename' => $filename,
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
            'access_classification' => 'internal',
        ]);
    }

    /** @test */
    public function finding_evidence_relation_uses_the_finding_id_column(): void
    {
        $finding = $this->makeFinding('TMN-REL-001');
        $evidence = $this->makeEvidence('bukti-1.pdf');

        FindingEvidence::create([
            'finding_id' => $finding->id,
            'evidence_id' => $evidence->id,
            'relationship_note' => 'Bukti kondisi aktual',
        ]);

        $this->assertSame(1, $finding->fresh()->evidenceLinks()->count());
        $this->assertSame($evidence->id, $finding->fresh()->evidenceLinks->first()->evidence->id);
        $this->assertSame(1, $evidence->fresh()->findingLinks()->count());
    }

    /** @test */
    public function finding_detail_page_renders_with_linked_evidence(): void
    {
        $finding = $this->makeFinding('TMN-REL-002');
        $evidence = $this->makeEvidence('bukti-2.pdf');

        FindingEvidence::create([
            'finding_id' => $finding->id,
            'evidence_id' => $evidence->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('findings.show', $finding))
            ->assertOk()
            ->assertSee('bukti-2.pdf');
    }

    /** @test */
    public function pivot_models_do_not_require_soft_delete_columns(): void
    {
        $finding = $this->makeFinding('TMN-REL-003');
        $evidence = $this->makeEvidence('bukti-3.pdf');

        $this->assertNotContains(
            SoftDeletes::class,
            class_uses_recursive(FindingEvidence::class),
            'Tabel finding_evidence tidak punya kolom deleted_at.',
        );
        $this->assertNotContains(
            SoftDeletes::class,
            class_uses_recursive(ResponseEvidence::class),
            'Tabel response_evidence tidak punya kolom deleted_at.',
        );

        $link = FindingEvidence::create([
            'finding_id' => $finding->id,
            'evidence_id' => $evidence->id,
        ]);

        $this->assertDatabaseHas('finding_evidence', ['id' => $link->id]);
        $this->assertNotNull(FindingEvidence::find($link->id));
    }
}
