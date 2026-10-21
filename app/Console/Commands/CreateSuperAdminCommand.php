<?php

namespace App\Console\Commands;

use App\Models\Clinic;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Membuat / menyetel akun super admin (administrator sistem) dari kredensial
 * yang tersimpan di berkas .env — TIDAK dari seeder.
 *
 * Environment yang dibaca:
 *   SUPERADMIN_NAME, SUPERADMIN_USERNAME, SUPERADMIN_EMAIL, SUPERADMIN_PASSWORD
 *
 * Password juga dapat diberikan lewat opsi --password atau diminta secara
 * interaktif (tersembunyi) bila tidak ada di .env.
 */
class CreateSuperAdminCommand extends Command
{
    protected $signature = 'simmak:create-superadmin
        {--email= : Override SUPERADMIN_EMAIL}
        {--username= : Override SUPERADMIN_USERNAME}
        {--name= : Override SUPERADMIN_NAME}
        {--password= : Override SUPERADMIN_PASSWORD (tidak disarankan, terlihat di history)}';

    protected $description = 'Buat atau setel akun super admin (administrator sistem) dari kredensial di .env';

    public function handle(): int
    {
        $organization = Organization::query()->orderBy('created_at')->first();

        if (! $organization) {
            $this->error('Organisasi belum tersedia. Jalankan `php artisan db:seed` terlebih dahulu.');

            return self::FAILURE;
        }

        $email = $this->option('email') ?: config('simmak.superadmin_email');
        $username = $this->option('username') ?: config('simmak.superadmin_username');
        $name = $this->option('name') ?: config('simmak.superadmin_name');
        $password = $this->option('password') ?: config('simmak.superadmin_password');

        $generatedPassword = false;

        if (empty($password)) {
            if ($this->input->isInteractive()) {
                $password = $this->secret('Masukkan password super admin (kosongkan untuk membuat acak)');
            }

            if (empty($password)) {
                $password = Str::password(16);
                $generatedPassword = true;
            }
        }

        $validator = Validator::make(
            compact('name', 'username', 'email', 'password'),
            [
                'name' => ['required', 'string', 'max:255'],
                'username' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:320'],
                'password' => ['required', 'string', 'min:8'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $this->ensureSuperAdminRole();

        $user = User::withTrashed()->where('email', $email)->first();

        if ($user && $user->trashed()) {
            $user->restore();
        }

        $attributes = [
            'organization_id' => $organization->id,
            'name' => $name,
            'username' => $username,
            'password' => Hash::make($password),
            'auth_provider' => 'local',
            'is_active' => true,
            'email_verified_at' => now(),
        ];

        if ($user) {
            $user->fill($attributes);
            $user->save();
            $this->info("Akun super admin diperbarui: {$email}");
        } else {
            $user = User::create(['email' => $email] + $attributes);
            $this->info("Akun super admin dibuat: {$email}");
        }

        $user->syncRoles(['super-admin']);

        // Administrator sistem bersifat lintas klinik: TIDAK diikat ke satu
        // klinik mana pun. default_clinic_id dikosongkan agar konteks aktif
        // menampilkan "Semua Klinik" dan ia bebas memilih klinik bila perlu.
        $user->default_clinic_id = null;
        $user->save();

        // Bersihkan pivot scope klinik lama (bila sebelumnya pernah di-assign).
        $user->clinics()->detach();

        $clinicCount = Clinic::query()->count();

        Role::where('guard_name', 'web')->get()->each(fn (Role $role) => $role->forgetCachedPermissions());

        $this->newLine();
        $this->table(['Field', 'Nilai'], [
            ['Nama', $user->name],
            ['Username', $user->username],
            ['Email', $user->email],
            ['Role', 'super-admin'],
            ['Klinik', 'Semua Klinik ('.$clinicCount.' klinik, lintas organisasi)'],
        ]);

        if ($generatedPassword) {
            $this->warn('Password acak dibuat (SUPERADMIN_PASSWORD tidak diset):');
            $this->line("  {$password}");
            $this->comment('Set SUPERADMIN_PASSWORD di .env agar deterministik, lalu ulangi perintah.');
        }

        $this->info('Selesai. Akun super admin dapat mengakses seluruh sistem.');

        return self::SUCCESS;
    }

    private function ensureSuperAdminRole(): void
    {
        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        // Beri seluruh permission yang tersedia agar administrator sistem
        // tidak terblokir modul apa pun.
        $permissions = Permission::where('guard_name', 'web')->pluck('name')->all();

        if ($permissions) {
            $role->syncPermissions($permissions);
        }
    }
}
