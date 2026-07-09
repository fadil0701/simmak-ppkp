<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleUsersSeeder extends Seeder
{
    /**
     * Akun demo per role — password semua: password
     *
     * @see docs/UAT_CHECKLIST.md
     */
    public function run(): void
    {
        $organization = Organization::first();
        if (! $organization) {
            return;
        }

        $this->ensureRoles();

        $clinics = Clinic::where('organization_id', $organization->id)->get()->keyBy('code');

        $accounts = [
            [
                'role' => 'super-admin',
                'name' => 'Administrator SIMMAK',
                'username' => 'admin',
                'email' => 'admin@simmak.ppkp.local',
                'clinic_codes' => $clinics->keys()->all(),
                'default_clinic' => 'KU',
            ],
            [
                'role' => 'admin-mutu',
                'name' => 'Admin Mutu PPKP',
                'username' => 'adminmutu',
                'email' => 'mutu@simmak.ppkp.local',
                'clinic_codes' => $clinics->keys()->all(),
                'default_clinic' => 'KU',
            ],
            [
                'role' => 'kepala-klinik',
                'name' => 'Dr. Kepala Klinik Tebet',
                'username' => 'kepala',
                'email' => 'kepala@simmak.ppkp.local',
                'clinic_codes' => ['KP06'],
                'default_clinic' => 'KP06',
            ],
            [
                'role' => 'pj-mutu',
                'name' => 'PJ Mutu Klinik Tebet',
                'username' => 'pjmutu',
                'email' => 'pjmutu@simmak.ppkp.local',
                'clinic_codes' => ['KP06'],
                'default_clinic' => 'KP06',
            ],
            [
                'role' => 'tim-akreditasi',
                'name' => 'Tim Akreditasi FKTP Tebet',
                'username' => 'akreditasi',
                'email' => 'akreditasi@simmak.ppkp.local',
                'clinic_codes' => ['KP06'],
                'default_clinic' => 'KP06',
            ],
            [
                'role' => 'auditor',
                'name' => 'Auditor Internal PPKP',
                'username' => 'auditor',
                'email' => 'auditor@simmak.ppkp.local',
                'clinic_codes' => ['KU', 'KP06'],
                'default_clinic' => 'KU',
            ],
            [
                'role' => 'staff',
                'name' => 'Staff Klinik Tebet',
                'username' => 'staff',
                'email' => 'staff@simmak.ppkp.local',
                'clinic_codes' => ['KP06'],
                'default_clinic' => 'KP06',
            ],
        ];

        foreach ($accounts as $account) {
            $defaultClinic = $clinics->get($account['default_clinic']);

            $user = User::firstOrCreate(
                ['email' => $account['email']],
                [
                    'organization_id' => $organization->id,
                    'default_clinic_id' => $defaultClinic?->id,
                    'name' => $account['name'],
                    'username' => $account['username'],
                    'password' => Hash::make('password'),
                    'auth_provider' => 'local',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            if (! $user->wasRecentlyCreated) {
                $user->update([
                    'name' => $account['name'],
                    'username' => $account['username'],
                    'default_clinic_id' => $defaultClinic?->id,
                    'password' => Hash::make('password'),
                    'is_active' => true,
                ]);
            }

            $user->syncRoles([$account['role']]);

            $clinicSync = collect($account['clinic_codes'])
                ->map(fn (string $code) => $clinics->get($code)?->id)
                ->filter()
                ->mapWithKeys(fn (string $id) => [
                    $id => ['is_default' => $id === $defaultClinic?->id],
                ]);

            $user->clinics()->sync($clinicSync);
        }

        Role::where('guard_name', 'web')->get()->each(fn (Role $role) => $role->forgetCachedPermissions());
    }

    private function ensureRoles(): void
    {
        $roles = [
            'super-admin' => Permission::where('guard_name', 'web')->pluck('name')->all(),
            'admin-mutu' => Permission::where('guard_name', 'web')
                ->where('name', 'not like', 'users.%')
                ->pluck('name')
                ->all(),
            'kepala-klinik' => [
                'dashboard.view', 'dashboard.clinic',
                'dms.view', 'audit.view', 'capa.view', 'capa.manage',
                'risk.view', 'accreditation.view', 'quality.view',
            ],
            'pj-mutu' => [
                'dashboard.view', 'dashboard.clinic',
                'dms.view', 'dms.manage', 'audit.view', 'audit.manage',
                'capa.view', 'capa.manage', 'risk.view', 'risk.manage',
                'accreditation.view', 'accreditation.manage', 'quality.view', 'quality.manage',
            ],
            'tim-akreditasi' => [
                'dashboard.view', 'dashboard.clinic',
                'dms.view',
                'accreditation.view', 'accreditation.manage',
                'capa.view', 'quality.view',
            ],
            'auditor' => [
                'dashboard.view', 'audit.view', 'audit.manage', 'capa.view',
            ],
            'staff' => [
                'dashboard.view', 'dms.view', 'capa.view', 'accreditation.view',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($rolePermissions);
        }
    }
}
