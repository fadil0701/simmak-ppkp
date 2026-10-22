<?php

namespace Tests\Feature\Audit;

use App\Models\AuditStandard;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InstrumentImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected AuditStandard $standard;

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
            'email' => 'instrumen@test.local',
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
            'status' => 'draft',
        ]);
    }

    /** @test */
    public function csv_import_creates_sections_and_criteria(): void
    {
        $csv = "Kode Standar,Kode EP,Elemen Penilaian,Kelderengkapan Bukti,Skor Maksimal\n"
            ."STANDAR 1.1.,EP 1,Tersedia visi misi dan tujuan,1. SK penetapan visi misi,10\n"
            ."STANDAR 1.1.,EP 2,Tersedia struktur organisasi,1. SK struktur organisasi,10\n";

        $path = tempnam(sys_get_temp_dir(), 'imp').'.csv';
        file_put_contents($path, $csv);

        $response = $this->actingAs($this->user)->post('/audit/standards/'.$this->standard->id.'/import/preview', [
            'file' => new UploadedFile($path, 'ep.csv', 'text/csv', null, true),
        ]);

        $response->assertStatus(200);
        $response->assertSee('Pratinjau');

        $response = $this->actingAs($this->user)->post('/audit/standards/'.$this->standard->id.'/import/store');

        $response->assertStatus(302);

        $this->assertDatabaseHas('standard_sections', ['code' => 'STANDAR 1.1.']);
        $this->assertDatabaseHas('standard_criteria', [
            'code' => 'EP 1',
            'title' => 'Tersedia visi misi dan tujuan',
            'max_score' => 10,
        ]);
    }

    /** @test */
    public function duplicate_rows_are_reported_and_not_saved(): void
    {
        $csv = "Kode Standar,Kode EP,Elemen Penilaian,Kelderengkapan Bukti,Skor Maksimal\n"
            ."STANDAR 1.1.,EP 1,Ep satu,1. Bukti,10\n"
            ."STANDAR 1.1.,EP 1,Ep satu duplikat,1. Bukti,10\n";

        $path = tempnam(sys_get_temp_dir(), 'imp').'.csv';
        file_put_contents($path, $csv);

        $response = $this->actingAs($this->user)->post('/audit/standards/'.$this->standard->id.'/import/preview', [
            'file' => new UploadedFile($path, 'ep.csv', 'text/csv', null, true),
        ]);

        $response->assertStatus(200);
        $response->assertSee('Duplikat terdeteksi');

        $response = $this->actingAs($this->user)->post('/audit/standards/'.$this->standard->id.'/import/store');

        $response->assertStatus(302);
        $response->assertSessionHas('error');

        $this->assertDatabaseCount('standard_criteria', 0);
    }
}
