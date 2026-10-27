<?php

namespace Tests\Feature\Audit;

use App\Models\Clinic;
use App\Models\Evidence;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditEvidenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Regresi UnableToRetrieveMetadata: `download()` menerima path absolut hasil
 * `Storage::disk()->path()` lalu menyerahkannya ke `Storage::download()`,
 * sehingga Flysystem menempelkan root disk dua kali dan gagal membaca
 * file_size → 500 pada /audit/evidence/{id}/download.
 */
class EvidenceDownloadTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;

    private Clinic $clinic;

    private User $user;

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

        foreach (['audit.evidence.view', 'audit.evidence.upload', 'audit.evidence.download'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $role->syncPermissions(Permission::where('guard_name', 'web')->pluck('name'));
        $this->user->assignRole($role);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function makeEvidence(string $path, string $filename = 'bukti-audit.jpeg', bool $withFile = true): Evidence
    {
        if ($withFile) {
            Storage::disk('local')->put($path, UploadedFile::fake()->image('bukti.jpg')->getContent());
        }

        return Evidence::create([
            'organization_id' => $this->org->id,
            'clinic_id' => $this->clinic->id,
            'uploaded_by' => $this->user->id,
            'storage_disk' => 'local',
            'storage_path' => $path,
            'original_filename' => $filename,
            'mime_type' => 'image/jpeg',
            'size_bytes' => 200718,
            'checksum' => 'abc123',
            'access_classification' => 'internal',
            'uploaded_at' => now(),
        ]);
    }

    /** @test */
    public function download_streams_the_stored_file(): void
    {
        $evidence = $this->makeEvidence('audit_evidence/org/clinic/bukti.jpeg');

        $response = $this->actingAs($this->user)
            ->get(route('audit.evidence.download', $evidence));

        $response->assertOk();
        $response->assertDownload('bukti-audit.jpeg');
    }

    /** @test */
    public function service_returns_a_disk_relative_path_not_an_absolute_one(): void
    {
        $evidence = $this->makeEvidence('audit_evidence/org/clinic/relatif.jpeg', 'relatif.jpeg');

        $path = app(AuditEvidenceService::class)->download($evidence);

        $this->assertSame($evidence->storage_path, $path);
        $this->assertStringNotContainsString(
            Storage::disk('local')->path(''),
            $path,
            'Path absolut akan membuat Flysystem menempelkan root disk dua kali.',
        );
    }

    /** @test */
    public function download_redirects_with_a_message_when_the_file_is_missing(): void
    {
        $evidence = $this->makeEvidence('audit_evidence/org/clinic/hilang.jpeg', withFile: false);

        $response = $this->actingAs($this->user)
            ->get(route('audit.evidence.download', $evidence));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringContainsString('tidak tersedia di penyimpanan', session('error'));
    }

    /** @test */
    public function index_flags_evidences_without_a_file_on_disk(): void
    {
        $this->makeEvidence('audit_evidence/org/clinic/ada.jpeg', 'ada.jpeg');
        $this->makeEvidence('audit_evidence/org/clinic/hilang.jpeg', 'hilang.jpeg', withFile: false);

        $response = $this->actingAs($this->user)->get(route('audit.evidence.index'));

        $response->assertOk();
        $response->assertSee('Berkas hilang di penyimpanan');
        $response->assertSee('hilang.jpeg');
    }

    /** @test */
    public function show_page_warns_when_the_file_is_missing(): void
    {
        $evidence = $this->makeEvidence('audit_evidence/org/clinic/hilang.jpeg', 'hilang.jpeg', withFile: false);

        $response = $this->actingAs($this->user)->get(route('audit.evidence.show', $evidence));

        $response->assertOk();
        $response->assertSee('Berkas tidak ditemukan di penyimpanan');
    }

    /** @test */
    public function integrity_command_reports_missing_files(): void
    {
        $this->makeEvidence('audit_evidence/org/clinic/ada.jpeg', 'ada.jpeg');
        $this->makeEvidence('audit_evidence/org/clinic/hilang.jpeg', 'hilang.jpeg', withFile: false);

        $this->artisan('simmak:check-evidence-files')
            ->expectsOutputToContain('Berkas hilang')
            ->assertExitCode(0);

        $this->artisan('simmak:check-evidence-files --strict')
            ->assertExitCode(1);
    }

    /** @test */
    public function integrity_command_detects_checksum_mismatch(): void
    {
        $path = 'audit_evidence/org/clinic/ubah.jpeg';
        $evidence = $this->makeEvidence($path, 'ubah.jpeg');
        $evidence->update(['checksum' => hash('sha256', 'isi-asli')]);

        $this->artisan('simmak:check-evidence-files --checksum')
            ->expectsOutputToContain('Checksum tidak cocok')
            ->assertExitCode(0);
    }
}
