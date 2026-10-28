<?php

namespace Tests\Unit\Audit;

use App\Exports\InstrumentImportTemplate;
use App\Models\AuditStandard;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditInstrumentImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class InstrumentImportTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_template_can_be_downloaded(): void
    {
        $user = $this->createManager();

        $response = $this->actingAs($user)->get(route('audit.standards.import.template'));

        $response->assertStatus(200);
    }

    public function test_template_file_round_trips_through_parser(): void
    {
        // Generate template asli lalu parse ulang — memastikan format template
        // selalu kompatibel dengan parser (anti-drift).
        $disk = 'local';
        Storage::fake($disk);
        $path = 'imports/template-test.xlsx';

        Excel::store(new InstrumentImportTemplate, $path, $disk);

        $file = new UploadedFile(
            Storage::disk($disk)->path($path),
            'template-test.xlsx',
            null,
            null,
            true
        );

        $service = new AuditInstrumentImportService;
        $parsed = $service->parse($file);

        $this->assertSame([], $parsed['errors']);
        $this->assertCount(3, $parsed['rows']);

        $first = $parsed['rows'][0];
        $this->assertSame('STANDAR 1.1', $first['kode_standar']);
        $this->assertSame('EP 1', $first['kode_ep']);
        $this->assertSame('10', $first['skor_maks']);
        $this->assertArrayHasKey('panduan_penilaian', $first);
    }

    public function test_parser_accepts_alias_headers(): void
    {
        $csv = "Standar,EP,Elemen,Bukti,Skor\n"
            ."STANDAR 1.1,EP 1,Uraian EP,Ada kebijakan,10\n";

        $file = UploadedFile::fake()->createWithContent('import.csv', $csv);

        $service = new AuditInstrumentImportService;
        $parsed = $service->parse($file);

        $this->assertSame([], $parsed['errors']);
        $this->assertCount(1, $parsed['rows']);
        $this->assertSame('STANDAR 1.1', $parsed['rows'][0]['kode_standar']);
        $this->assertSame('EP 1', $parsed['rows'][0]['kode_ep']);
    }

    public function test_parser_rejects_unrecognised_headers_with_helpful_error(): void
    {
        $csv = "Kolom A,Kolom B\n1,2\n";

        $file = UploadedFile::fake()->createWithContent('bad.csv', $csv);

        $service = new AuditInstrumentImportService;
        $parsed = $service->parse($file);

        $this->assertNotSame([], $parsed['errors']);
        $this->assertStringContainsString('Unduh Template', $parsed['errors'][0]);
    }

    public function test_validate_flags_non_numeric_score(): void
    {
        $user = $this->createManager();
        $org = Organization::first() ?? Organization::create([
            'code' => 'TEST-IMP', 'name' => 'Test Import Org', 'org_type' => 'central', 'is_active' => true,
        ]);

        $standard = AuditStandard::create([
            'organization_id' => $org->id,
            'code' => 'IMP-01',
            'title' => 'Standar Uji Impor',
        ]);

        $service = new AuditInstrumentImportService;
        $result = $service->validate([
            [
                'kode_standar' => 'S1', 'kode_ep' => 'EP 1',
                'elemen_penilaian' => 'Uraian', 'kelengkapan_bukti' => '',
                'panduan_penilaian' => '', 'skor_maks' => 'banyak',
            ],
        ], $standard, null);

        $this->assertCount(1, $result['invalid']);
        $this->assertStringContainsString('angka', $result['invalid'][0]['pesan']);
    }

    public function test_standard_detail_page_renders_criteria_form(): void
    {
        $user = $this->createManager();
        $org = Organization::first();

        $standard = AuditStandard::create([
            'organization_id' => $org->id,
            'code' => 'SHOW-01',
            'title' => 'Standar Tampilan Detail',
        ]);

        $version = \App\Models\InstrumentVersion::create([
            'audit_standard_id' => $standard->id,
            'version_label' => 'v1',
            'status' => 'draft',
        ]);

        $section = \App\Models\StandardSection::create([
            'instrument_version_id' => $version->id,
            'code' => 'S-1',
            'title' => 'Seksi Satu',
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($user)->get(route('audit.standards.show', $standard));

        $response->assertStatus(200);
        $response->assertSee('Tambah Elemen Penilaian');
        $response->assertSee('Unduh');
        $response->assertSee('Template Impor');
        $this->assertNotNull($section->id);
    }

    public function test_import_page_shows_template_and_column_guide(): void
    {
        $user = $this->createManager();
        $org = Organization::first();

        $standard = AuditStandard::create([
            'organization_id' => $org->id,
            'code' => 'IMP-GUIDE',
            'title' => 'Standar Panduan Impor',
        ]);

        $response = $this->actingAs($user)->get(route('audit.standards.import', $standard));

        $response->assertStatus(200);
        $response->assertSee('Unduh Template Excel');
        $response->assertSee('Panduan Penilaian');
        $response->assertSee('Skor Maksimal');
    }

    public function test_standard_status_can_be_changed_via_quick_action(): void
    {
        $user = $this->createManager();
        $org = Organization::first();

        $standard = AuditStandard::create([
            'organization_id' => $org->id,
            'code' => 'STAT-01',
            'title' => 'Standar Uji Status',
            'status' => 'draft',
        ]);

        // Standar tanpa versi instrumen tidak boleh diaktifkan.
        $blocked = $this->actingAs($user)->patch(route('audit.standards.status', $standard), ['status' => 'active']);
        $blocked->assertSessionHasErrors('status');
        $this->assertSame('draft', $standard->fresh()->status);

        // Setelah punya versi instrumen, status bisa diaktifkan.
        \App\Models\InstrumentVersion::create([
            'audit_standard_id' => $standard->id,
            'version_label' => 'v1',
            'status' => 'draft',
        ]);

        $ok = $this->actingAs($user)->patch(route('audit.standards.status', $standard), ['status' => 'active']);
        $ok->assertSessionHasNoErrors();
        $this->assertSame('active', $standard->fresh()->status);

        // Tabel index menampilkan label bahasa Indonesia, bukan nilai mentah.
        $index = $this->actingAs($user)->get(route('audit.standards.index'));
        $index->assertStatus(200);
        $index->assertSee('Aktif');
    }

    public function test_standard_status_updates_through_edit_form(): void
    {
        $user = $this->createManager();
        $org = Organization::first();

        $standard = AuditStandard::create([
            'organization_id' => $org->id,
            'code' => 'STAT-02',
            'title' => 'Standar Uji Form Status',
            'status' => 'draft',
        ]);

        \App\Models\InstrumentVersion::create([
            'audit_standard_id' => $standard->id,
            'version_label' => 'v1',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)->put(route('audit.standards.update', $standard), [
            'code' => 'STAT-02',
            'title' => 'Standar Uji Form Status',
            'status' => 'inactive',
        ]);

        $response->assertRedirect(route('audit.standards.show', $standard));
        $this->assertSame('inactive', $standard->fresh()->status);
    }

    public function test_activating_instrument_version_activates_its_standard(): void
    {
        $user = $this->createManager();
        $org = Organization::first();

        $standard = AuditStandard::create([
            'organization_id' => $org->id,
            'code' => 'STAT-03',
            'title' => 'Standar Auto Aktif',
            'status' => 'draft',
        ]);

        $version = \App\Models\InstrumentVersion::create([
            'audit_standard_id' => $standard->id,
            'version_label' => 'v1',
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $user->id,
        ]);

        (new \App\Services\AuditStandardService)->activateVersion($version, $user);

        $this->assertSame('active', $version->fresh()->status);
        $this->assertSame('active', $standard->fresh()->status);
    }

    public function test_capa_number_uses_tindak_lanjut_prefix(): void
    {
        $user = $this->createManager();
        $org = Organization::first();

        // Pastikan penomoran baru memakai awalan TL- (bukan CAPA-).
        $number = (new \App\Services\AuditCapaService)->generateCapaNumber($user);

        $this->assertMatchesRegularExpression('/^TL-\d{4}-\d{4}$/', $number);
        $this->assertStringStartsWith('TL-', $number);
        $this->assertNotNull($org);
    }

    public function test_standard_forms_render_new_layout_and_status_field(): void
    {
        $user = $this->createManager();
        $org = Organization::first();

        $create = $this->actingAs($user)->get(route('audit.standards.create'));
        $create->assertStatus(200);
        $create->assertSee('Identitas Standar');
        $create->assertSee('Status Awal');
        $create->assertSee('name="status"', false);

        $standard = AuditStandard::create([
            'organization_id' => $org->id,
            'code' => 'FORM-01',
            'title' => 'Standar Uji Form Baru',
            'status' => 'draft',
        ]);

        $edit = $this->actingAs($user)->get(route('audit.standards.edit', $standard));
        $edit->assertStatus(200);
        $edit->assertSee('Edit Standar Audit');
        $edit->assertSee('Status saat ini');
        $edit->assertSee('name="status"', false);
    }

    private function createManager(): User
    {
        $org = Organization::first() ?? Organization::create([
            'code' => 'TEST-IMP-MGR', 'name' => 'Test Import Manager Org', 'org_type' => 'central', 'is_active' => true,
        ]);

        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $role->syncPermissions(\Spatie\Permission\Models\Permission::where('guard_name', 'web')->pluck('name')->all());

        $user = User::create([
            'organization_id' => $org->id,
            'name' => 'Import Manager',
            'email' => 'import-manager@test.local',
            'password' => bcrypt('password'),
            'auth_provider' => 'local',
            'is_active' => true,
        ]);

        $user->assignRole($role);

        return $user;
    }
}
