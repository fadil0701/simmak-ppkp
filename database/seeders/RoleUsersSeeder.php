<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\Organization;
use App\Models\User;
use App\Support\SimmakRoles;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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

        SimmakRoles::sync();
        $this->migrateLegacyRoles();

        $userPassword = $userPassword ?: Str::random(16);

        $clinics = Clinic::where('organization_id', $organization->id)->get()->keyBy('code');

        $allClinicCodes = $clinics->keys()->all();
        $managementCodes = ['MP'];

        $accounts = [
            [
                'role' => SimmakRoles::ADMIN_SISTEM,
                'name' => 'Admin Sistem PPKP',
                'username' => 'adminsistem',
                'email' => 'sistem@simmak.ppkp.local',
                'clinic_codes' => $managementCodes,
                'default_clinic' => 'MP',
            ],
            [
                'role' => SimmakRoles::KEPALA_PUSAT,
                'name' => 'Kepala Pusat PPKP',
                'username' => 'kepalapusat',
                'email' => 'kepalapusat@simmak.ppkp.local',
                'clinic_codes' => $managementCodes,
                'default_clinic' => 'MP',
            ],
            [
                'role' => SimmakRoles::KASUBBAG_TU,
                'name' => 'Kasubbag Tata Usaha',
                'username' => 'kasubbagtu',
                'email' => 'tu@simmak.ppkp.local',
                'clinic_codes' => $managementCodes,
                'default_clinic' => 'MP',
            ],
            [
                'role' => SimmakRoles::KETUA_TIM_MUTU,
                'name' => 'Ketua Tim Mutu PPKP',
                'username' => 'ketuamutu',
                'email' => 'mutu@simmak.ppkp.local',
                'clinic_codes' => $managementCodes,
                'default_clinic' => 'MP',
            ],
            [
                'role' => SimmakRoles::KETUA_TIM_AUDIT,
                'name' => 'Ketua Tim Audit Internal',
                'username' => 'ketuaaudit',
                'email' => 'ketuaaudit@simmak.ppkp.local',
                'clinic_codes' => $allClinicCodes,
                'default_clinic' => 'MP',
            ],
            [
                'role' => SimmakRoles::KETUA_TIM_AKREDITASI,
                'name' => 'Ketua Tim Akreditasi',
                'username' => 'akreditasi',
                'email' => 'akreditasi@simmak.ppkp.local',
                'clinic_codes' => $managementCodes,
                'default_clinic' => 'MP',
            ],
            [
                'role' => SimmakRoles::AUDITOR,
                'name' => 'Auditor Internal PPKP',
                'username' => 'auditor',
                'email' => 'auditor@simmak.ppkp.local',
                'clinic_codes' => ['KU', 'KP06'],
                'default_clinic' => 'KU',
            ],
            [
                'role' => SimmakRoles::KEPALA_KLINIK,
                'name' => 'Kepala Klinik Pratama',
                'username' => 'kepala',
                'email' => 'kepala@simmak.ppkp.local',
                'clinic_codes' => ['KP06'],
                'default_clinic' => 'KP06',
            ],
            [
                'role' => SimmakRoles::AUDITI,
                'name' => 'Auditi Klinik Pratama',
                'username' => 'auditi',
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

    private function migrateLegacyRoles(): void
    {
        $map = SimmakRoles::legacyMap();

        User::query()->with('roles')->each(function (User $user) use ($map) {
            $current = $user->getRoleNames();
            $next = $current->map(fn (string $role) => $map[$role] ?? $role)->unique()->values();

            if ($next->all() !== $current->all()) {
                $user->syncRoles($next->all());
            }
        });
    }
}
