<?php

namespace Tests\Unit\Audit;

use App\Models\Clinic;
use App\Models\Organization;
use App\Models\User;
use App\Support\SimmakRoles;
use Database\Seeders\RoleUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Mengunci kesesuaian katalog peran resmi (8 peran) dengan bundel izin dan
 * akun demo yang dibuat RoleUsersSeeder.
 */
class RoleCatalogCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Organization::create([
            'code' => 'PPKP-DKI',
            'name' => 'PPKP Provinsi DKI Jakarta',
            'org_type' => 'central',
            'is_active' => true,
        ]);

        Clinic::create([
            'organization_id' => $this->org->id,
            'code' => 'MP',
            'name' => 'Manajemen',
            'clinic_type' => 'pusat',
            'is_main_clinic' => false,
            'is_auditable' => false,
            'city' => 'Jakarta Pusat',
            'is_active' => true,
        ]);

        SimmakRoles::sync();
    }

    /** @test */
    public function katalog_resmi_berisi_tepat_delapan_peran(): void
    {
        $this->assertSame([
            'super-admin',
            'admin-sistem',
            'kepala-pusat',
            'kasubbag-tu',
            'kasatpel-ukp',
            'kasatpel-ukm',
            'kepala-klinik',
            'admin-klinik',
        ], SimmakRoles::assignableNames());
    }

    /** @test */
    public function setiap_peran_katalog_punya_bundel_izin_dan_terdaftar_di_database(): void
    {
        foreach (SimmakRoles::assignableNames() as $roleName) {
            $this->assertNotSame([], SimmakRoles::permissionsFor($roleName), "Peran {$roleName} tidak punya izin.");
            $this->assertNotNull(
                Role::where('name', $roleName)->where('guard_name', 'web')->first(),
                "Peran {$roleName} belum dibuat oleh SimmakRoles::sync().",
            );
        }
    }

    /** @test */
    public function kepala_pusat_dapat_menyetujui_program_audit_tetapi_tidak_mengajukannya(): void
    {
        $permissions = SimmakRoles::permissionsFor(SimmakRoles::KEPALA_PUSAT);

        $this->assertContains('audit.program.approve', $permissions);
        $this->assertNotContains('audit.program.submit', $permissions);
        $this->assertContains('audit.report.publish', $permissions);
    }

    /** @test */
    public function kasatpel_ukp_dan_ukm_tidak_boleh_menyetujui_program_atau_menutup_temuan(): void
    {
        foreach ([SimmakRoles::KASATPEL_UKP, SimmakRoles::KASATPEL_UKM] as $roleName) {
            $permissions = SimmakRoles::permissionsFor($roleName);

            $this->assertContains('audit.program.submit', $permissions);
            $this->assertNotContains('audit.program.approve', $permissions);
            $this->assertNotContains('audit.finding.close', $permissions);
        }
    }

    /** @test */
    public function admin_klinik_memegang_operasional_dokumen_tanpa_menutup_temuan(): void
    {
        $permissions = SimmakRoles::permissionsFor(SimmakRoles::ADMIN_KLINIK);

        $this->assertContains('dms.manage', $permissions);
        $this->assertContains('audit.evidence.upload', $permissions);
        $this->assertNotContains('audit.finding.close', $permissions);
        $this->assertNotContains('users.manage', $permissions);
    }

    /** @test */
    public function admin_sistem_tidak_memegang_izin_alur_audit(): void
    {
        $permissions = SimmakRoles::permissionsFor(SimmakRoles::ADMIN_SISTEM);

        $this->assertContains('users.manage', $permissions);
        $this->assertContains('audit.configuration.manage', $permissions);
        $this->assertNotContains('audit.program.approve', $permissions);
        $this->assertNotContains('audit.finding.close', $permissions);
        $this->assertNotContains('audit.execution.execute', $permissions);
    }

    /** @test */
    public function setiap_peran_katalog_memiliki_akun_demo_setelah_seeder_dijalankan(): void
    {
        $this->seed(RoleUsersSeeder::class);

        $users = User::with('roles')->where('email', 'like', '%@simmak.ppkp.local')->get();

        // 12 akun demo: 7 peran katalog (tanpa super-admin) + 5 peran legacy.
        // Super admin dibuat lewat `simmak:create-superadmin`.
        $this->assertCount(12, $users);

        foreach (array_diff(SimmakRoles::assignableNames(), [SimmakRoles::SUPER_ADMIN]) as $roleName) {
            $this->assertTrue(
                $users->contains(fn (User $user) => $user->hasRole($roleName)),
                "Tidak ada akun demo untuk peran {$roleName}.",
            );
        }
    }
}
