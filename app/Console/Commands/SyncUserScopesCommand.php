<?php

namespace App\Console\Commands;

use App\Services\UserScopeSynchronizer;
use App\Support\SimmakRoles;
use Illuminate\Console\Command;
use Spatie\Permission\PermissionRegistrar;

/**
 * Selaraskan ulang izin seluruh akun dengan Scope Sistem yang tercatat.
 *
 * Dipakai setelah bundel izin scope berubah (app/Support/SimmakScopes.php)
 * atau setelah migrasi/backfill scope.
 *
 * Contoh:
 *   php artisan simmak:sync-user-scopes
 */
class SyncUserScopesCommand extends Command
{
    protected $signature = 'simmak:sync-user-scopes';

    protected $description = 'Selaraskan izin akun dengan Scope Sistem (audit_internal, mutu, akreditasi, monitoring, auditor, auditi)';

    public function handle(UserScopeSynchronizer $scopes): int
    {
        SimmakRoles::ensurePermissions();

        $count = $scopes->syncAll();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info("Izin {$count} akun diselaraskan dengan Scope Sistem.");

        return self::SUCCESS;
    }
}
