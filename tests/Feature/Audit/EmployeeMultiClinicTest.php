<?php

namespace Tests\Feature\Audit;

use App\Models\Clinic;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmployeeMultiClinicTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;

    protected Clinic $clinicA;

    protected Clinic $clinicB;

    protected Unit $unitA;

    protected Unit $unitB;

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

        $this->clinicA = Clinic::create([
            'organization_id' => $this->org->id,
            'code' => 'KP01',
            'name' => 'Klinik Pratama Jakarta Pusat',
            'clinic_type' => 'pratama',
            'is_main_clinic' => false,
            'is_auditable' => true,
            'city' => 'Jakarta Pusat',
            'is_active' => true,
        ]);

        $this->clinicB = Clinic::create([
            'organization_id' => $this->org->id,
            'code' => 'KP02',
            'name' => 'Klinik Pratama Jakarta Selatan',
            'clinic_type' => 'pratama',
            'is_main_clinic' => false,
            'is_auditable' => true,
            'city' => 'Jakarta Selatan',
            'is_active' => true,
        ]);

        $this->unitA = Unit::create([
            'organization_id' => $this->org->id,
            'clinic_id' => $this->clinicA->id,
            'code' => 'U-KIA',
            'name' => 'Unit KIA Pusat',
            'is_active' => true,
        ]);

        $this->unitB = Unit::create([
            'organization_id' => $this->org->id,
            'clinic_id' => $this->clinicB->id,
            'code' => 'U-LAB',
            'name' => 'Unit Laboratorium Selatan',
            'is_active' => true,
        ]);

        $this->user = User::create([
            'organization_id' => $this->org->id,
            'name' => 'Administrator',
            'email' => 'admin-multi-clinic@test.local',
            'password' => bcrypt('password'),
            'auth_provider' => 'local',
            'is_active' => true,
        ]);
        $this->user->clinics()->attach($this->clinicA->id, ['is_default' => true]);

        foreach (['employees.view', 'employees.manage'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $role->syncPermissions(Permission::where('guard_name', 'web')->pluck('name')->all());
        $this->user->assignRole('super-admin');
    }

    /** @test */
    public function employee_can_hold_multiple_clinics(): void
    {
        $response = $this->actingAs($this->user)->post('/employees', [
            'employee_number' => 'NIP-7001',
            'full_name' => 'Rani Multi Klinik',
            'clinic_ids' => [$this->clinicA->id, $this->clinicB->id],
            'primary_clinic_id' => $this->clinicB->id,
            'is_active' => 1,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();

        $employee = Employee::where('employee_number', 'NIP-7001')->firstOrFail();

        $this->assertSame($this->clinicB->id, $employee->clinic_id);
        $this->assertEqualsCanonicalizing(
            [$this->clinicA->id, $this->clinicB->id],
            $employee->clinicScopeIds(),
        );

        $this->assertDatabaseHas('employee_clinic_assignments', [
            'employee_id' => $employee->id,
            'clinic_id' => $this->clinicB->id,
            'is_primary' => true,
        ]);
        $this->assertDatabaseHas('employee_clinic_assignments', [
            'employee_id' => $employee->id,
            'clinic_id' => $this->clinicA->id,
            'is_primary' => false,
        ]);
        $this->assertSame(1, $employee->clinicAssignments()->where('is_primary', true)->count());
    }

    /** @test */
    public function primary_clinic_is_added_even_when_not_listed_in_selection(): void
    {
        $response = $this->actingAs($this->user)->post('/employees', [
            'employee_number' => 'NIP-7002',
            'full_name' => 'Bagus Tanpa Ceklis',
            'clinic_ids' => [$this->clinicA->id],
            'primary_clinic_id' => $this->clinicB->id,
        ]);

        $response->assertSessionHasNoErrors();

        $employee = Employee::where('employee_number', 'NIP-7002')->firstOrFail();

        $this->assertEqualsCanonicalizing(
            [$this->clinicA->id, $this->clinicB->id],
            $employee->clinicScopeIds(),
        );
        $this->assertSame($this->clinicB->id, $employee->clinic_id);
    }

    /** @test */
    public function unit_must_belong_to_the_primary_clinic(): void
    {
        $this->actingAs($this->user)->post('/employees', [
            'employee_number' => 'NIP-7003',
            'full_name' => 'Citra Unit Salah',
            'clinic_ids' => [$this->clinicA->id],
            'primary_clinic_id' => $this->clinicA->id,
            'unit_id' => $this->unitB->id,
        ]);

        $employee = Employee::where('employee_number', 'NIP-7003')->firstOrFail();

        $this->assertNull($employee->unit_id);
        $this->assertSame($this->clinicA->id, $employee->clinic_id);
    }

    /** @test */
    public function unit_is_kept_when_it_belongs_to_the_primary_clinic(): void
    {
        $this->actingAs($this->user)->post('/employees', [
            'employee_number' => 'NIP-7004',
            'full_name' => 'Dewi Unit Sesuai',
            'clinic_ids' => [$this->clinicA->id],
            'primary_clinic_id' => $this->clinicA->id,
            'unit_id' => $this->unitA->id,
        ]);

        $employee = Employee::where('employee_number', 'NIP-7004')->firstOrFail();

        $this->assertSame($this->unitA->id, $employee->unit_id);
        $this->assertTrue(
            $employee->clinicAssignments()
                ->where('clinic_id', $this->clinicA->id)
                ->where('unit_id', $this->unitA->id)
                ->exists(),
        );
    }

    /** @test */
    public function updating_employee_replaces_clinic_assignments(): void
    {
        $employee = Employee::create([
            'organization_id' => $this->org->id,
            'employee_number' => 'NIP-7005',
            'full_name' => 'Eko Ganti Klinik',
            'is_active' => true,
        ]);
        $employee->syncClinicAssignments([$this->clinicA->id], $this->clinicA->id);

        $this->actingAs($this->user)->put('/employees/'.$employee->id, [
            'employee_number' => 'NIP-7005',
            'full_name' => 'Eko Ganti Klinik',
            'clinic_ids' => [$this->clinicB->id],
            'primary_clinic_id' => $this->clinicB->id,
            'is_active' => 1,
        ])->assertSessionHasNoErrors();

        $employee->refresh();

        $this->assertSame($this->clinicB->id, $employee->clinic_id);
        $this->assertSame([$this->clinicB->id], $employee->clinicScopeIds());
        $this->assertDatabaseMissing('employee_clinic_assignments', [
            'employee_id' => $employee->id,
            'clinic_id' => $this->clinicA->id,
        ]);
    }

    /** @test */
    public function employee_index_filters_by_secondary_clinic(): void
    {
        $employee = Employee::create([
            'organization_id' => $this->org->id,
            'employee_number' => 'NIP-7006',
            'full_name' => 'Fajar Dua Klinik',
            'is_active' => true,
        ]);
        $employee->syncClinicAssignments(
            [$this->clinicA->id, $this->clinicB->id],
            $this->clinicA->id,
        );

        $response = $this->actingAs($this->user)->get('/employees?clinic_id='.$this->clinicB->id);

        $response->assertStatus(200);
        $response->assertSee('NIP-7006');
        $response->assertSee('Fajar Dua Klinik');
    }

    /** @test */
    public function employee_form_lists_every_clinic_held(): void
    {
        $employee = Employee::create([
            'organization_id' => $this->org->id,
            'employee_number' => 'NIP-7007',
            'full_name' => 'Gita Formulir',
            'is_active' => true,
        ]);
        $employee->syncClinicAssignments(
            [$this->clinicA->id, $this->clinicB->id],
            $this->clinicA->id,
        );

        $response = $this->actingAs($this->user)->get('/employees/'.$employee->id.'/edit');

        $response->assertStatus(200);
        $response->assertSee('name="clinic_ids[]"', false);
        $response->assertSee('name="primary_clinic_id"', false);
        $response->assertSee('Klinik Pratama Jakarta Pusat');
        $response->assertSee('Klinik Pratama Jakarta Selatan');
    }

    /** @test */
    public function clinic_scope_falls_back_to_legacy_primary_clinic(): void
    {
        $employee = Employee::create([
            'organization_id' => $this->org->id,
            'clinic_id' => $this->clinicA->id,
            'employee_number' => 'NIP-7008',
            'full_name' => 'Hadi Data Lama',
            'is_active' => true,
        ]);

        $this->assertSame([$this->clinicA->id], $employee->clinicScopeIds());
        $this->assertSame($this->clinicA->id, $employee->primaryClinicId());
    }
}
