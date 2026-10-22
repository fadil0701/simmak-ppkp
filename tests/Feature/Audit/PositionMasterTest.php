<?php

namespace Tests\Feature\Audit;

use App\Models\Clinic;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PositionMasterTest extends TestCase
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
            'name' => 'Administrator',
            'email' => 'admin-jabatan@test.local',
            'password' => bcrypt('password'),
            'auth_provider' => 'local',
            'is_active' => true,
        ]);
        $this->user->clinics()->attach($this->clinic->id, ['is_default' => true]);

        foreach (['positions.view', 'positions.manage', 'employees.view', 'employees.manage'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $role->syncPermissions(Permission::where('guard_name', 'web')->pluck('name')->all());
        $this->user->assignRole('super-admin');
    }

    /** @test */
    public function position_can_be_created(): void
    {
        $response = $this->actingAs($this->user)->post('/positions', [
            'code' => 'JBT-TEST-01',
            'name' => 'Auditor Internal Uji',
            'category' => 'teknis',
            'is_active' => 1,
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('positions', [
            'organization_id' => $this->org->id,
            'code' => 'JBT-TEST-01',
            'name' => 'Auditor Internal Uji',
            'category' => 'teknis',
        ]);
    }

    /** @test */
    public function position_code_must_be_unique_per_organization(): void
    {
        Position::create([
            'organization_id' => $this->org->id,
            'code' => 'JBT-TEST-02',
            'name' => 'Jabatan Lama',
            'category' => 'fungsional',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->post('/positions', [
            'code' => 'JBT-TEST-02',
            'name' => 'Jabatan Baru',
            'category' => 'fungsional',
        ]);

        $response->assertSessionHasErrors('code');
    }

    /** @test */
    public function employee_form_uses_position_master(): void
    {
        $position = Position::create([
            'organization_id' => $this->org->id,
            'code' => 'JBT-TEST-03',
            'name' => 'Perawat Profesional',
            'category' => 'fungsional',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get('/employees/create');

        $response->assertStatus(200);
        $response->assertSee('Perawat Profesional');
    }

    /** @test */
    public function employee_can_be_linked_to_position_master(): void
    {
        $position = Position::create([
            'organization_id' => $this->org->id,
            'code' => 'JBT-TEST-04',
            'name' => 'Bidan Likes',
            'category' => 'fungsional',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->post('/employees', [
            'employee_number' => 'NIP-9100',
            'full_name' => 'Sri Wahyuni, S.Keb.',
            'position_id' => $position->id,
            'clinic_id' => $this->clinic->id,
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('employees', [
            'employee_number' => 'NIP-9100',
            'position_id' => $position->id,
            'position' => 'Bidan Likes',
        ]);
    }

    /** @test */
    public function position_in_use_cannot_be_deleted(): void
    {
        $position = Position::create([
            'organization_id' => $this->org->id,
            'code' => 'JBT-TEST-05',
            'name' => 'Jabatan Terpakai',
            'category' => 'fungsional',
            'is_active' => true,
        ]);

        Employee::create([
            'organization_id' => $this->org->id,
            'clinic_id' => $this->clinic->id,
            'position_id' => $position->id,
            'employee_number' => 'NIP-9101',
            'full_name' => 'Pegawai Terpakai',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->delete('/positions/'.$position->id);

        $response->assertStatus(302);
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('positions', ['id' => $position->id]);
    }

    /** @test */
    public function position_index_lists_data(): void
    {
        Position::create([
            'organization_id' => $this->org->id,
            'code' => 'JBT-TEST-06',
            'name' => 'Verifikator CAPA',
            'category' => 'struktural',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get('/positions');

        $response->assertStatus(200);
        $response->assertSee('JBT-TEST-06');
        $response->assertSee('Verifikator CAPA');
    }
}
