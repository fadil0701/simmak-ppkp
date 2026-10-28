<?php

namespace Tests\Feature\Audit;

use App\Models\AuditExecution;
use App\Models\AuditStandard;
use App\Models\InstrumentVersion;
use App\Models\Organization;
use App\Models\StandardCriterion;
use App\Models\StandardSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InstrumentVersionEditTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected AuditStandard $standard;

    protected InstrumentVersion $version;

    protected StandardSection $section;

    protected StandardCriterion $criterion;

    protected function setUp(): void
    {
        parent::setUp();

        $org = Organization::create([
            'code' => 'PPKP-DKI',
            'name' => 'PPKP Provinsi DKI Jakarta',
            'org_type' => 'central',
            'is_active' => true,
        ]);

        $this->user = User::create([
            'organization_id' => $org->id,
            'name' => 'Pengelola Instrumen',
            'email' => 'instrumen-edit@test.local',
            'password' => bcrypt('password'),
            'auth_provider' => 'local',
            'is_active' => true,
        ]);

        foreach (['audit.standard.view', 'audit.standard.manage'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $role = Role::firstOrCreate(['name' => 'admin-mutu', 'guard_name' => 'web']);
        $role->syncPermissions(Permission::where('guard_name', 'web')->pluck('name')->all());
        $this->user->assignRole('admin-mutu');

        $this->standard = AuditStandard::create([
            'organization_id' => $org->id,
            'code' => 'TKK',
            'title' => 'Tata Kelola Klinik',
            'status' => 'active',
        ]);

        $this->version = InstrumentVersion::create([
            'audit_standard_id' => $this->standard->id,
            'version_label' => 'v1',
            'status' => 'active',
        ]);

        $this->section = StandardSection::create([
            'instrument_version_id' => $this->version->id,
            'code' => 'STANDAR 1.1.',
            'title' => 'Tata Kelola',
            'sort_order' => 1,
        ]);

        $this->criterion = StandardCriterion::create([
            'standard_section_id' => $this->section->id,
            'code' => 'EP 1',
            'title' => 'Tersedia visi misi',
            'requirement_text' => 'Ada penetapan visi misi',
            'evidence_guidance' => 'SK penetapan',
            'max_score' => 10,
            'sort_order' => 1,
            'active' => true,
        ]);

        AuditExecution::create([
            'organization_id' => $org->id,
            'instrument_version_id' => $this->version->id,
            'execution_code' => 'EXEC-LOCK-001',
            'title' => 'Eksekusi yang memakai versi ini',
            'status' => 'in_progress',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);
    }

    public function test_used_version_detail_is_not_shown_as_locked(): void
    {
        $this->assertTrue($this->version->isLocked());

        $response = $this->actingAs($this->user)->get(route('audit.standards.show', $this->standard));

        $response->assertOk();
        $response->assertDontSee('Dikunci');
        $response->assertDontSee('Versi dikunci');
        $response->assertSee('Edit seksi');
        $response->assertSee('Edit EP');
        $response->assertSee('+ Tambah Elemen Penilaian (EP)');
        $response->assertSee('+ Tambah Seksi');
    }

    public function test_used_version_can_receive_new_criterion(): void
    {
        $response = $this->actingAs($this->user)->post(
            route('audit.sections.criteria.store', $this->section),
            [
                'code' => 'EP 2',
                'title' => 'Tersedia struktur organisasi',
                'requirement_text' => 'Ada SK struktur',
                'max_score' => 10,
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('standard_criteria', [
            'standard_section_id' => $this->section->id,
            'code' => 'EP 2',
            'title' => 'Tersedia struktur organisasi',
        ]);
    }

    public function test_used_version_criterion_can_be_updated_in_place(): void
    {
        $response = $this->actingAs($this->user)->put(
            route('audit.criteria.update', $this->criterion),
            [
                'code' => 'EP 1',
                'title' => 'Tersedia visi, misi, dan tujuan klinik',
                'requirement_text' => 'Ada penetapan visi misi yang disahkan',
                'evidence_guidance' => "SK penetapan\nNotulen rapat",
                'assessment_guidance' => 'Lengkap bila ada SK',
                'max_score' => 10,
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('standard_criteria', [
            'id' => $this->criterion->id,
            'title' => 'Tersedia visi, misi, dan tujuan klinik',
            'requirement_text' => 'Ada penetapan visi misi yang disahkan',
        ]);
    }

    public function test_used_version_section_can_be_updated_in_place(): void
    {
        $response = $this->actingAs($this->user)->put(
            route('audit.sections.update', $this->section),
            [
                'code' => 'STANDAR 1.1.',
                'title' => 'Tata Kelola Klinik (perbaikan)',
                'description' => 'Redaksi diperbaiki tanpa versi baru',
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('standard_sections', [
            'id' => $this->section->id,
            'title' => 'Tata Kelola Klinik (perbaikan)',
        ]);
    }
}
