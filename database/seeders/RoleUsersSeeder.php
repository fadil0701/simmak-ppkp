<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleUsersSeeder extends Seeder
{
    /**
     * Akun demo per role.
     *
     * Kredensial TIDAK ditulis di codebase, melainkan diambil dari environment
     * di berkas .env:
     *   - SUPERADMIN_EMAIL / SUPERADMIN_USERNAME / SUPERADMIN_NAME / SUPERADMIN_PASSWORD
     *     untuk akun super admin (administrator sistem);
     *   - DEFAULT_USER_PASSWORD untuk seluruh akun user selain super admin.
     *
     * Bila password kosong, dibuat acak dan dicetak satu kali ke console hanya
     * saat seeder dijalankan. DEMO_ACCOUNT_PASSWORD lama tetap didukung sebagai
     * fallback untuk keduanya.
     *
     * @see docs/UAT_CHECKLIST.md
     */
    public function run(): void
    {
        $organization = Organization::first();
        if (! $organization) {
            return;
        }

        $userPassword = config('simmak.default_user_password');

        $generatedUserPassword = $userPassword === null || $userPassword === '';

        // Di produksi, akun demo tidak dibuat sama sekali kecuali password
        // di-set eksplisit melalui .env.
        if (app()->environment('production') && $generatedUserPassword) {
            $this->command?->warn('RoleUsersSeeder dilewati di produksi (DEFAULT_USER_PASSWORD tidak diset).');

            return;
        }

        $this->ensureRoles();

        $userPassword = $userPassword ?: Str::random(16);

        $clinics = Clinic::where('organization_id', $organization->id)->get()->keyBy('code');

        $accounts = [
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
            $accountPassword = $account['password'] ?? $userPassword;
            $defaultClinic = $clinics->get($account['default_clinic']);

            $user = User::firstOrCreate(
                ['email' => $account['email']],
                [
                    'organization_id' => $organization->id,
                    'default_clinic_id' => $defaultClinic?->id,
                    'name' => $account['name'],
                    'username' => $account['username'],
                    'password' => Hash::make($accountPassword),
                    'auth_provider' => 'local',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            if (! $user->wasRecentlyCreated) {
                $update = [
                    'name' => $account['name'],
                    'username' => $account['username'],
                    'default_clinic_id' => $defaultClinic?->id,
                    'is_active' => true,
                ];

                // Password akun lama hanya disetel ulang jika password default
                // di-set eksplisit — akun yang sudah diganti pengguna tidak dirusak.
                if (! $generatedUserPassword) {
                    $update['password'] = Hash::make($accountPassword);
                }

                $user->update($update);
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

        if ($generatedUserPassword) {
            // Hanya dicetak di console saat seeding — tidak pernah ditulis ke codebase/log.
            $this->command?->warn('Password akun user diacak (DEFAULT_USER_PASSWORD tidak diset):');
            $this->command?->warn('  '.$userPassword);
            $this->command?->info('Set DEFAULT_USER_PASSWORD di .env untuk password yang deterministik.');
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
