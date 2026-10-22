<?php

namespace Tests\Feature\Audit;

use App\Models\Clinic;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmployeeMasterTest extends TestCase
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
            'email' => 'admin-pegawai@test.local',
            'password' => bcrypt('password'),
            'auth_provider' => 'local',
            'is_active' => true,
        ]);
        $this->user->clinics()->attach($this->clinic->id, ['is_default' => true]);

        foreach (['employees.view', 'employees.manage', 'users.view', 'users.manage', 'clinics.view', 'clinics.manage'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $role->syncPermissions(Permission::where('guard_name', 'web')->pluck('name')->all());
        $this->user->assignRole('super-admin');
    }

    /** @test */
    public function employee_can_be_created_from_master(): void
    {
        $response = $this->actingAs($this->user)->post('/employees', [
            'employee_number' => 'NIP-9001',
            'full_name' => 'Rina Kurnia, S.Kep.',
            'position' => 'Perawat',
            'email' => 'rina@test.local',
            'clinic_id' => $this->clinic->id,
            'is_active' => 1,
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('employees', [
            'employee_number' => 'NIP-9001',
            'full_name' => 'Rina Kurnia, S.Kep.',
            'clinic_id' => $this->clinic->id,
        ]);
    }

    /** @test */
    public function employee_number_must_be_unique_per_organization(): void
    {
        Employee::create([
            'organization_id' => $this->org->id,
            'clinic_id' => $this->clinic->id,
            'employee_number' => 'NIP-9002',
            'full_name' => 'Pegawai Lama',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->post('/employees', [
            'employee_number' => 'NIP-9002',
            'full_name' => 'Pegawai Baru',
            'clinic_id' => $this->clinic->id,
        ]);

        $response->assertSessionHasErrors('employee_number');
    }

    /** @test */
    public function employee_index_is_listed(): void
    {
        Employee::create([
            'organization_id' => $this->org->id,
            'clinic_id' => $this->clinic->id,
            'employee_number' => 'NIP-9003',
            'full_name' => 'Tono',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get('/employees');

        $response->assertStatus(200);
        $response->assertSee('NIP-9003');
        $response->assertSee('Tono');
    }

    /** @test */
    public function user_account_can_be_linked_to_employee(): void
    {
        $employee = Employee::create([
            'organization_id' => $this->org->id,
            'clinic_id' => $this->clinic->id,
            'employee_number' => 'NIP-9004',
            'full_name' => 'Sinta Dewi',
            'email' => 'sinta@test.local',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->post('/users', [
            'employee_id' => $employee->id,
            'name' => 'Sinta Dewi',
            'username' => 'sinta',
            'email' => 'sinta@test.local',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'super-admin',
            'default_clinic_id' => $this->clinic->id,
            'clinic_ids' => [$this->clinic->id],
            'is_active' => 1,
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('users', [
            'email' => 'sinta@test.local',
            'employee_id' => $employee->id,
        ]);
    }

    /** @test */
    public function user_form_lists_employees(): void
    {
        Employee::create([
            'organization_id' => $this->org->id,
            'clinic_id' => $this->clinic->id,
            'employee_number' => 'NIP-9005',
            'full_name' => 'Wahyu Firmansyah',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get('/users/create');

        $response->assertStatus(200);
        $response->assertSee('NIP-9005');
        $response->assertSee('Wahyu Firmansyah');
    }

    /** @test */
    public function employee_with_user_account_cannot_be_deleted(): void
    {
        $employee = Employee::create([
            'organization_id' => $this->org->id,
            'clinic_id' => $this->clinic->id,
            'employee_number' => 'NIP-9006',
            'full_name' => 'Terpaut',
            'is_active' => true,
        ]);

        User::create([
            'organization_id' => $this->org->id,
            'employee_id' => $employee->id,
            'name' => 'Terpaut',
            'email' => 'terpaut@test.local',
            'password' => bcrypt('password'),
            'auth_provider' => 'local',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->delete('/employees/'.$employee->id);

        $response->assertStatus(302);
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('employees', ['id' => $employee->id]);
    }
}
