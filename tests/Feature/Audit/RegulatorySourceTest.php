<?php

namespace Tests\Feature\Audit;

use App\Models\AuditStandard;
use App\Models\Clinic;
use App\Models\Organization;
use App\Models\RegulatorySource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegulatorySourceTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;

    protected Clinic $clinic;

    protected User $user;

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

        $this->user = User::create([
            'organization_id' => $this->org->id,
            'name' => 'Administrator Standar',
            'email' => 'admin-regulasi@test.local',
            'password' => bcrypt('password'),
            'auth_provider' => 'local',
            'is_active' => true,
        ]);
        $this->user->clinics()->attach($this->clinic->id, ['is_default' => true]);

        foreach (['audit.standard.view', 'audit.standard.manage', 'audit.standard.review'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $role->syncPermissions(Permission::where('guard_name', 'web')->pluck('name')->all());
        $this->user->assignRole('super-admin');
    }

    /** @test */
    public function register_lists_sources(): void
    {
        RegulatorySource::create([
            'document_number' => 'KMK/HK.01.07/TEST/2022',
            'title' => 'Standar Akreditasi Klinik Uji',
            'issuer' => 'Kementerian Kesehatan RI',
            'verification_status' => 'UNVERIFIED',
        ]);

        $response = $this->actingAs($this->user)->get('/audit/regulatory-sources');

        $response->assertStatus(200);
        $response->assertSee('KMK/HK.01.07/TEST/2022');
        $response->assertSee('Belum Diverifikasi');
    }

    /** @test */
    public function document_number_must_be_unique(): void
    {
        RegulatorySource::create([
            'document_number' => 'KMK/DOUBLE/2022',
            'title' => 'Dokumen Lama',
        ]);

        $response = $this->actingAs($this->user)->post('/audit/regulatory-sources', [
            'document_number' => 'KMK/DOUBLE/2022',
            'title' => 'Dokumen Baru',
        ]);

        $response->assertSessionHasErrors('document_number');
        $this->assertSame(1, RegulatorySource::where('document_number', 'KMK/DOUBLE/2022')->count());
    }

    /** @test */
    public function update_changes_metadata_without_touching_verification(): void
    {
        $source = RegulatorySource::create([
            'document_number' => 'KMK/EDIT/2022',
            'title' => 'Judul Awal',
            'verification_status' => 'UNVERIFIED',
        ]);

        $response = $this->actingAs($this->user)->put('/audit/regulatory-sources/'.$source->id, [
            'document_number' => 'KMK/EDIT/2022',
            'title' => 'Judul Setelah Revisi',
            'issuer' => 'Kemenkes',
            'verification_status' => 'VERIFIED_IN_FORCE',
        ]);

        $response->assertRedirect(route('audit.regulatory.index'));

        $source->refresh();
        $this->assertSame('Judul Setelah Revisi', $source->title);
        $this->assertSame('Kemenkes', $source->issuer);
        $this->assertSame('UNVERIFIED', $source->verification_status);
    }

    /** @test */
    public function verification_requires_notes_for_a_claim_status(): void
    {
        $source = RegulatorySource::create([
            'document_number' => 'KMK/VERIFY/2022',
            'title' => 'Dokumen Verifikasi',
            'verification_status' => 'UNVERIFIED',
        ]);

        $response = $this->actingAs($this->user)->post('/audit/regulatory-sources/'.$source->id.'/verify', [
            'verification_status' => 'VERIFIED_IN_FORCE',
            'verification_notes' => '   ',
        ]);

        $response->assertSessionHasErrors('verification_notes');
        $this->assertSame('UNVERIFIED', $source->fresh()->verification_status);
    }

    /** @test */
    public function verification_records_reviewer_and_timestamp(): void
    {
        $source = RegulatorySource::create([
            'document_number' => 'KMK/VERIFIED/2022',
            'title' => 'Dokumen Terverifikasi',
            'verification_status' => 'UNVERIFIED',
        ]);

        $response = $this->actingAs($this->user)->post('/audit/regulatory-sources/'.$source->id.'/verify', [
            'verification_status' => 'VERIFIED_IN_FORCE',
            'verification_notes' => 'Diperiksa via JDIH Kemenkes.',
        ]);

        $response->assertRedirect();

        $source->refresh();
        $this->assertSame('VERIFIED_IN_FORCE', $source->verification_status);
        $this->assertSame($this->user->id, $source->verified_by);
        $this->assertNotNull($source->verified_at);
    }

    /** @test */
    public function returning_to_unverified_clears_the_verification_trail(): void
    {
        $source = RegulatorySource::create([
            'document_number' => 'KMK/REVOKE/2022',
            'title' => 'Dokumen Dicabut',
            'verification_status' => 'VERIFIED_IN_FORCE',
            'verified_at' => now(),
            'verified_by' => $this->user->id,
            'verification_notes' => 'Verifikasi lama.',
        ]);

        $this->actingAs($this->user)->post('/audit/regulatory-sources/'.$source->id.'/verify', [
            'verification_status' => 'UNVERIFIED',
        ])->assertRedirect();

        $source->refresh();
        $this->assertSame('UNVERIFIED', $source->verification_status);
        $this->assertNull($source->verified_by);
        $this->assertNull($source->verified_at);
    }

    /** @test */
    public function source_is_created_as_unverified_even_if_payload_claims_otherwise(): void
    {
        $response = $this->actingAs($this->user)->post('/audit/regulatory-sources', [
            'document_number' => 'PERMENKES/TEST/34/2022',
            'title' => 'Akreditasi Fasilitas Pelayanan Kesehatan',
            'issuer' => 'Kementerian Kesehatan RI',
            'verification_status' => 'VERIFIED_IN_FORCE',
            'verified_by' => $this->user->id,
        ]);

        $response->assertRedirect(route('audit.regulatory.index'));

        $source = RegulatorySource::where('document_number', 'PERMENKES/TEST/34/2022')->firstOrFail();

        $this->assertSame('UNVERIFIED', $source->verification_status);
        $this->assertNull($source->verified_by);
        $this->assertNull($source->verified_at);
    }

    /** @test */
    public function source_in_use_by_a_standard_cannot_be_deleted(): void
    {
        $source = RegulatorySource::create([
            'document_number' => 'KMK/TERPAKAI/2022',
            'title' => 'Dokumen Terpakai',
        ]);

        AuditStandard::create([
            'organization_id' => $this->org->id,
            'source_document_id' => $source->id,
            'code' => 'STD-01',
            'title' => 'Standar Uji',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->user)->delete('/audit/regulatory-sources/'.$source->id);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('regulatory_sources', ['id' => $source->id, 'deleted_at' => null]);
    }

    /** @test */
    public function unused_source_can_be_deleted(): void
    {
        $source = RegulatorySource::create([
            'document_number' => 'KMK/BEBAS/2022',
            'title' => 'Dokumen Tanpa Standar',
        ]);

        $this->actingAs($this->user)
            ->delete('/audit/regulatory-sources/'.$source->id)
            ->assertRedirect(route('audit.regulatory.index'));

        $this->assertSoftDeleted('regulatory_sources', ['id' => $source->id]);
    }

    /** @test */
    public function create_form_renders_without_a_verification_panel(): void
    {
        $response = $this->actingAs($this->user)->get('/audit/regulatory-sources/create');

        $response->assertStatus(200);
        $response->assertSee('Tambah Sumber Regulasi');
        $response->assertSee('name="document_number"', false);
        $response->assertDontSee('Verifikasi Dokumen');
    }

    /** @test */
    public function edit_form_renders_the_verification_panel(): void
    {
        $source = RegulatorySource::create([
            'document_number' => 'KMK/PANEL/2022',
            'title' => 'Dokumen Panel Verifikasi',
            'verification_status' => 'unverified',
        ]);

        $response = $this->actingAs($this->user)->get('/audit/regulatory-sources/'.$source->id.'/edit');

        $response->assertStatus(200);
        $response->assertSee('Edit Sumber Regulasi');
        $response->assertSee('Verifikasi Dokumen');
        $response->assertSee(route('audit.regulatory.verify', $source));
    }

    /** @test */
    public function register_requires_standard_view_permission(): void
    {
        $outsider = User::create([
            'organization_id' => $this->org->id,
            'name' => 'Pegawai Tanpa Izin',
            'email' => 'tanpa-izin@test.local',
            'password' => bcrypt('password'),
            'auth_provider' => 'local',
            'is_active' => true,
        ]);
        $outsider->clinics()->attach($this->clinic->id, ['is_default' => true]);

        $this->actingAs($outsider)->get('/audit/regulatory-sources')->assertStatus(403);
    }
}
