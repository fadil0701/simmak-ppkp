<?php

namespace Tests\Feature\Audit;

use App\Models\Clinic;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use App\Services\UserClinicScopeSynchronizer;
use App\Services\UserScopeSynchronizer;
use App\Support\SimmakRoles;
use App\Support\SimmakScopes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserMultiRoleAndScopeTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;

    protected Clinic $clinicA;

    protected Clinic $clinicB;

    protected User $admin;

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

        SimmakRoles::sync();

        $this->admin = User::create([
            'organization_id' => $this->org->id,
            'name' => 'Administrator Sistem',
            'username' => 'admin-scope',
            'email' => 'admin-scope@test.local',
            'password' => bcrypt('password'),
            'auth_provider' => 'local',
            'is_active' => true,
        ]);
        $this->admin->assignRole(SimmakRoles::SUPER_ADMIN);
        $this->admin->clinics()->attach($this->clinicA->id, ['is_default' => true]);
    }

    /** @test */
    public function user_can_hold_multiple_roles(): void
    {
        $this->actingAs($this->admin)->post('/users', [
            'name' => 'Rangkap Peran',
            'username' => 'rangkap',
            'email' => 'rangkap@test.local',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role_ids' => [SimmakRoles::KEPALA_KLINIK, SimmakRoles::ADMIN_KLINIK],
            'clinic_ids' => [$this->clinicA->id],
            'is_active' => 1,
        ])->assertSessionHasNoErrors();

        $user = User::where('username', 'rangkap')->firstOrFail();

        $this->assertEqualsCanonicalizing(
            [SimmakRoles::KEPALA_KLINIK, SimmakRoles::ADMIN_KLINIK],
            $user->getRoleNames()->all(),
        );
    }

    /** @test */
    public function user_form_requires_at_least_one_role(): void
    {
        $response = $this->actingAs($this->admin)->post('/users', [
            'name' => 'Tanpa Peran',
            'username' => 'tanpa-peran',
            'email' => 'tanpa-peran@test.local',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'is_active' => 1,
        ]);

        $response->assertSessionHasErrors('role_ids');
        $this->assertDatabaseMissing('users', ['username' => 'tanpa-peran']);
    }

    /** @test */
    public function updating_user_keeps_every_selected_role(): void
    {
        $user = User::create([
            'organization_id' => $this->org->id,
            'name' => 'Dua Peran',
            'username' => 'dua-peran',
            'email' => 'dua-peran@test.local',
            'password' => bcrypt('password'),
            'auth_provider' => 'local',
            'is_active' => true,
        ]);
        $user->syncRoles([SimmakRoles::KEPALA_KLINIK]);

        $this->actingAs($this->admin)->put('/users/'.$user->id, [
            'name' => 'Dua Peran',
            'username' => 'dua-peran',
            'email' => 'dua-peran@test.local',
            'role_ids' => [SimmakRoles::KEPALA_KLINIK, SimmakRoles::KASATPEL_UKP],
            'is_active' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertEqualsCanonicalizing(
            [SimmakRoles::KEPALA_KLINIK, SimmakRoles::KASATPEL_UKP],
            $user->fresh()->getRoleNames()->all(),
        );
    }

    /** @test */
    public function scope_grants_permission_beyond_the_role(): void
    {
        $this->actingAs($this->admin)->post('/users', [
            'name' => 'Pelaksana Audit',
            'username' => 'pelaksana',
            'email' => 'pelaksana@test.local',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role_ids' => [SimmakRoles::KEPALA_KLINIK],
            'scope_names' => [SimmakScopes::AUDITOR],
            'clinic_ids' => [$this->clinicA->id],
            'is_active' => 1,
        ])->assertSessionHasNoErrors();

        $user = User::where('username', 'pelaksana')->firstOrFail();

        $this->assertSame([SimmakScopes::AUDITOR], $user->scopeNames());
        $this->assertTrue($user->can('audit.execution.execute'));
        $this->assertFalse($user->can('audit.program.approve'));
    }

    /** @test */
    public function admin_sistem_with_auditor_scope_cannot_approve_program(): void
    {
        $user = User::create([
            'organization_id' => $this->org->id,
            'name' => 'Admin Plus Scope',
            'username' => 'admin-scope-auditor',
            'email' => 'admin-scope-auditor@test.local',
            'password' => bcrypt('password'),
            'auth_provider' => 'local',
            'is_active' => true,
        ]);
        $user->syncRoles([SimmakRoles::ADMIN_SISTEM]);

        $this->actingAs($this->admin)->put('/users/'.$user->id, [
            'name' => 'Admin Plus Scope',
            'username' => 'admin-scope-auditor',
            'email' => 'admin-scope-auditor@test.local',
            'role_ids' => [SimmakRoles::ADMIN_SISTEM],
            'scope_names' => [SimmakScopes::AUDITOR],
            'is_active' => 1,
        ])->assertSessionHasNoErrors();

        $user = $user->fresh();

        $this->assertTrue($user->can('audit.execution.execute'));
        $this->assertFalse($user->can('audit.program.approve'));
        $this->assertFalse($user->can('audit.finding.close'));
    }

    /** @test */
    public function scope_is_required_before_gated_route_opens(): void
    {
        $blocked = User::create([
            'organization_id' => $this->org->id,
            'name' => 'Tanpa Scope',
            'username' => 'tanpa-scope',
            'email' => 'tanpa-scope@test.local',
            'password' => bcrypt('password'),
            'auth_provider' => 'local',
            'is_active' => true,
        ]);
        $blocked->syncRoles([SimmakRoles::KEPALA_KLINIK]);
        $blocked->clinics()->attach($this->clinicA->id, ['is_default' => true]);

        $this->actingAs($blocked)->get('/findings/create')->assertStatus(403);

        $this->actingAs($this->admin)->put('/users/'.$blocked->id, [
            'name' => 'Tanpa Scope',
            'username' => 'tanpa-scope',
            'email' => 'tanpa-scope@test.local',
            'role_ids' => [SimmakRoles::KEPALA_KLINIK],
            'scope_names' => [SimmakScopes::AUDITOR],
            'is_active' => 1,
        ])->assertSessionHasNoErrors();

        $blocked = $blocked->fresh();

        $this->assertTrue($blocked->can('audit.finding.create'));
        $this->actingAs($blocked)->get('/findings/create')->assertStatus(200);
    }

    /** @test */
    public function clinic_scope_follows_the_employee_master(): void
    {
        $employee = Employee::create([
            'organization_id' => $this->org->id,
            'employee_number' => 'NIP-8001',
            'full_name' => 'Pegawai Dua Klinik',
            'email' => 'pegawai-dua@test.local',
            'is_active' => true,
        ]);
        $employee->syncClinicAssignments([$this->clinicA->id, $this->clinicB->id], $this->clinicB->id);

        $this->actingAs($this->admin)->post('/users', [
            'employee_id' => $employee->id,
            'name' => 'Pegawai Dua Klinik',
            'username' => 'pegawai-dua',
            'email' => 'pegawai-dua@test.local',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role_ids' => [SimmakRoles::ADMIN_KLINIK],
            'is_active' => 1,
        ])->assertSessionHasNoErrors();

        $user = User::where('username', 'pegawai-dua')->firstOrFail();

        $this->assertEqualsCanonicalizing(
            [$this->clinicA->id, $this->clinicB->id],
            $user->clinics()->pluck('clinics.id')->all(),
        );
        $this->assertSame($this->clinicB->id, $user->default_clinic_id);
    }

    /** @test */
    public function user_without_employee_can_still_use_manual_clinics(): void
    {
        $this->actingAs($this->admin)->post('/users', [
            'name' => 'Akun Teknis',
            'username' => 'akun-teknis',
            'email' => 'akun-teknis@test.local',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role_ids' => [SimmakRoles::ADMIN_SISTEM],
            'clinic_ids' => [$this->clinicA->id, $this->clinicB->id],
            'default_clinic_id' => $this->clinicB->id,
            'is_active' => 1,
        ])->assertSessionHasNoErrors();

        $user = User::where('username', 'akun-teknis')->firstOrFail();

        $this->assertEqualsCanonicalizing(
            [$this->clinicA->id, $this->clinicB->id],
            $user->clinics()->pluck('clinics.id')->all(),
        );
        $this->assertSame($this->clinicB->id, $user->default_clinic_id);
    }

    /** @test */
    public function employee_clinic_change_propagates_to_linked_user(): void
    {
        $employee = Employee::create([
            'organization_id' => $this->org->id,
            'employee_number' => 'NIP-8002',
            'full_name' => 'Pegawai Pindah Klinik',
            'is_active' => true,
        ]);
        $employee->syncClinicAssignments([$this->clinicA->id], $this->clinicA->id);

        $user = User::create([
            'organization_id' => $this->org->id,
            'employee_id' => $employee->id,
            'name' => 'Pegawai Pindah Klinik',
            'username' => 'pegawai-pindah',
            'email' => 'pegawai-pindah@test.local',
            'password' => bcrypt('password'),
            'auth_provider' => 'local',
            'is_active' => true,
        ]);
        $user->syncRoles([SimmakRoles::ADMIN_KLINIK]);
        app(UserClinicScopeSynchronizer::class)->syncForEmployee($employee);

        $this->assertSame([$this->clinicA->id], $user->clinics()->pluck('clinics.id')->all());

        $this->actingAs($this->admin)->put('/employees/'.$employee->id, [
            'employee_number' => 'NIP-8002',
            'full_name' => 'Pegawai Pindah Klinik',
            'clinic_ids' => [$this->clinicB->id],
            'primary_clinic_id' => $this->clinicB->id,
            'is_active' => 1,
        ])->assertSessionHasNoErrors();

        $user = $user->fresh();

        $this->assertSame([$this->clinicB->id], $user->clinics()->pluck('clinics.id')->all());
        $this->assertSame($this->clinicB->id, $user->default_clinic_id);
    }

    /** @test */
    public function user_forms_render_roles_scopes_and_clinic_source_notes(): void
    {
        $employee = Employee::create([
            'organization_id' => $this->org->id,
            'employee_number' => 'NIP-8100',
            'full_name' => 'Pegawai Formulir',
            'is_active' => true,
        ]);
        $employee->syncClinicAssignments([$this->clinicA->id], $this->clinicA->id);

        $create = $this->actingAs($this->admin)->get('/users/create');
        $create->assertStatus(200);
        $create->assertSee('name="role_ids[]"', false);
        $create->assertSee('name="scope_names[]"', false);
        $create->assertSee('id="clinic-source-note"', false);
        $create->assertSee('id="clinic-manual-note"', false);

        foreach (SimmakRoles::assignableNames() as $roleName) {
            $create->assertSee('value="'.$roleName.'"', false);
        }

        foreach (SimmakScopes::assignableNames() as $scopeName) {
            $create->assertSee('value="'.$scopeName.'"', false);
        }

        $user = User::create([
            'organization_id' => $this->org->id,
            'employee_id' => $employee->id,
            'default_clinic_id' => $this->clinicA->id,
            'name' => 'Pegawai Formulir',
            'username' => 'pegawai-formulir',
            'email' => 'pegawai-formulir@test.local',
            'password' => bcrypt('password'),
            'auth_provider' => 'local',
            'is_active' => true,
        ]);
        $user->syncRoles([SimmakRoles::KEPALA_KLINIK, SimmakRoles::ADMIN_KLINIK]);
        app(UserScopeSynchronizer::class)->sync($user, [SimmakScopes::AUDITOR, SimmakScopes::MONITORING], $this->admin);

        $edit = $this->actingAs($this->admin)->get('/users/'.$user->id.'/edit');
        $edit->assertStatus(200);
        $edit->assertSee('name="role_ids[]"', false);
        $edit->assertSee('name="scope_names[]"', false);

        $editHtml = $edit->getContent();

        foreach ([SimmakRoles::KEPALA_KLINIK, SimmakRoles::ADMIN_KLINIK] as $roleName) {
            $this->assertMatchesRegularExpression(
                '/value="'.preg_quote($roleName, '/').'"\s+checked/',
                $editHtml,
                'Peran '.$roleName.' seharusnya tercentang pada form edit.',
            );
        }

        foreach ([SimmakScopes::AUDITOR, SimmakScopes::MONITORING] as $scopeName) {
            $this->assertMatchesRegularExpression(
                '/value="'.preg_quote($scopeName, '/').'"\s+checked/',
                $editHtml,
                'Scope '.$scopeName.' seharusnya tercentang pada form edit.',
            );
        }

        $show = $this->actingAs($this->admin)->get('/users/'.$user->id);
        $show->assertStatus(200);
        $show->assertSee('Peran &amp; Scope Sistem', false);
        $show->assertSee(SimmakScopes::labels()[SimmakScopes::AUDITOR]);
        $show->assertSee(SimmakScopes::labels()[SimmakScopes::MONITORING]);
    }

    /** @test */
    public function inactive_employee_empties_the_account_clinic_scope(): void
    {
        $employee = Employee::create([
            'organization_id' => $this->org->id,
            'employee_number' => 'NIP-8003',
            'full_name' => 'Pegawai Nonaktif',
            'is_active' => true,
        ]);
        $employee->syncClinicAssignments([$this->clinicA->id], $this->clinicA->id);

        $user = User::create([
            'organization_id' => $this->org->id,
            'employee_id' => $employee->id,
            'name' => 'Pegawai Nonaktif',
            'username' => 'pegawai-nonaktif',
            'email' => 'pegawai-nonaktif@test.local',
            'password' => bcrypt('password'),
            'auth_provider' => 'local',
            'is_active' => true,
        ]);
        $user->syncRoles([SimmakRoles::ADMIN_KLINIK]);
        app(UserClinicScopeSynchronizer::class)->syncForEmployee($employee);

        $this->actingAs($this->admin)->put('/employees/'.$employee->id, [
            'employee_number' => 'NIP-8003',
            'full_name' => 'Pegawai Nonaktif',
            'clinic_ids' => [$this->clinicA->id],
            'primary_clinic_id' => $this->clinicA->id,
            'is_active' => 0,
        ])->assertSessionHasNoErrors();

        $this->assertSame([], $user->fresh()->clinics()->pluck('clinics.id')->all());
    }
}
