<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Setel ulang kata sandi akun pengguna yang tidak dapat masuk.
 *
 * Contoh:
 *   php artisan simmak:reset-password mutu@simmak.ppkp.local
 *   php artisan simmak:reset-password pusdatinppkp@gmail.com --password="SandiKuat123"
 */
class ResetPasswordCommand extends Command
{
    protected $signature = 'simmak:reset-password
        {email : Email atau username akun}
        {--password= : Kata sandi baru (bila kosong, dibuat acak)}';

    protected $description = 'Setel ulang kata sandi akun pengguna';

    public function handle(): int
    {
        $identifier = (string) $this->argument('email');

        $user = User::withTrashed()
            ->where('email', $identifier)
            ->orWhere('username', $identifier)
            ->first();

        if (! $user) {
            $this->error("Akun '{$identifier}' tidak ditemukan.");

            return self::FAILURE;
        }

        if ($user->trashed()) {
            $user->restore();
            $this->warn("Akun '{$identifier}' sebelumnya nonaktif dan dipulihkan.");
        }

        $password = $this->option('password') ?: Str::password(12);
        $generated = ! $this->option('password');

        $user->update([
            'password' => Hash::make($password),
            'is_active' => true,
        ]);

        $this->info("Kata sandi akun '{$user->email}' berhasil disetel ulang.");

        if ($generated) {
            $this->line("  Sandi baru: {$password}");
            $this->comment('Simpan segera dan ganti setelah masuk.');
        }

        return self::SUCCESS;
    }
}
