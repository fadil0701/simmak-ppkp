<?php

namespace Tests\Feature\Audit;

use App\Models\Clinic;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Memverifikasi aturan scope manajemen:
 * - Manajemen Pusat (klinik `clinic_type = 'pusat'`) adalah pengawas sehingga
 *   dapat mengakses seluruh Klinik Utama & Klinik Pratama dalam organisasinya.
 * - Administrator sistem (`super-admin`) dapat mengakses semua organisasi & klinik.
 * - User klinik hanya melihat klinik yang di-assign (user_clinic_scopes).
 *
 * @see docs/SECURITY_GAP_ASSESSMENT.md (F-05a)
 * @see docs/BRD.md (2.1.ORG-01)
 */
class ClinicScopeTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['clinics.view', 'clinics.manage', 'audit.configuration.manage'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        foreach (['super-admin', 'pj-mutu', 'kepala-klinik'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        Role::where('name', 'pj-mutu')->first()->syncPermissions(['clinics.view']);

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $this->org = Organization::create([
            'code' => 'SCOPE-ORG',
            'name' => 'Scope Test Org',
            'org_type' => 'central',
            'is_active' => true,
        ]);

        $this->makeClinic('MP', 'pusat');
        $this->makeClinic('KU', 'utama');
        $this->makeClinic('KP01', 'pratama');
        $this->makeClinic('KP02', 'pratama');
    }

    private function makeClinic(string $code, string $type): Clinic
    {
        return Clinic::create([
            'organization_id' => $this->org->id,
            'code' => $code,
            'name' => 'Clinic '.$code,
            'clinic_type' => $type,
            'is_main_clinic' => $type === 'utama',
            'is_active' => true,
        ]);
    }

    private function makeUser(string $role, array $clinicCodes): User
    {
        $user = User::create([
            'organization_id' => $this->org->id,
            'name' => 'Scope User '.$role,
            'email' => $role.'-'.uniqid().'@test.local',
            'password' => bcrypt('password'),
            'auth_provider' => 'local',
            'is_active' => true,
        ]);

        $user->assignRole($role);

        $ids = Clinic::whereIn('code', $clinicCodes)->pluck('id');
        $user->clinics()->sync($ids->mapWithKeys(fn ($id) => [$id => ['is_default' => false]])->all());

        return $user->fresh();
    }

    /** @test */
    public function head_office_scope_can_access_all_clinics_in_organization(): void
    {
        // User yang hanya di-assign ke Manajemen Pusat
        $user = $this->makeUser('pj-mutu', ['MP']);

        $this->assertTrue($user->hasHeadOfficeScope());
        $this->assertTrue($user->hasCrossClinicAccess());
        $this->assertSame(4, $user->clinicScopeIds()->count());
        $this->assertSame(4, Clinic::forUserClinics($user)->count());

        // Dapat mengakses Klinik Utama & Pratama meski tidak di-assign eksplisit
        $this->assertTrue($user->hasClinicAccess(Clinic::where('code', 'KU')->first()->id));
        $this->assertTrue($user->hasClinicAccess(Clinic::where('code', 'KP02')->first()->id));
    }

    /** @test */
    public function super_admin_can_access_all_organizations_and_clinics(): void
    {
        $user = $this->makeUser('super-admin', []);

        $this->assertTrue($user->isSuperAdmin());

        // Organisasi lain beserta kliniknya
        $otherOrg = Organization::create([
            'code' => 'OTHER-ORG',
            'name' => 'Other Org',
            'org_type' => 'central',
            'is_active' => true,
        ]);
        $otherClinic = Clinic::create([
            'organization_id' => $otherOrg->id,
            'code' => 'OTHER-01',
            'name' => 'Other Clinic',
            'clinic_type' => 'pratama',
            'is_main_clinic' => false,
            'is_active' => true,
        ]);

        $this->assertTrue($user->belongsToOrganization($otherOrg->id));
        $this->assertTrue($user->hasClinicAccess($otherClinic->id));
        $this->assertSame(5, $user->clinicScopeIds()->count());
        $this->assertSame(5, Clinic::forUserClinics($user)->count());
    }

    /** @test */
    public function clinic_user_only_sees_assigned_clinics(): void
    {
        $user = $this->makeUser('kepala-klinik', ['KP01']);

        $this->assertFalse($user->hasHeadOfficeScope());
        $this->assertFalse($user->hasCrossClinicAccess());
        $this->assertSame(1, $user->clinicScopeIds()->count());
        $this->assertSame(1, Clinic::forUserClinics($user)->count());

        $this->assertTrue($user->hasClinicAccess(Clinic::where('code', 'KP01')->first()->id));
        $this->assertFalse($user->hasClinicAccess(Clinic::where('code', 'KU')->first()->id));
    }

    /** @test */
    public function clinic_user_cannot_reach_clinic_outside_scope_via_http(): void
    {
        $user = $this->makeUser('kepala-klinik', ['KP01']);

        $this->actingAs($user)
            ->get('/clinics')
            ->assertStatus(403);
    }
}
