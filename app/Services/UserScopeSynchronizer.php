<?php

namespace App\Services;

use App\Models\User;
use App\Support\SimmakScopes;
use Illuminate\Support\Collection;

/**
 * Menyelaraskan Scope Sistem akun dengan izin langsung (direct permission).
 *
 * Izin efektif = izin peran ∪ izin scope. Karena scope diberikan sebagai izin
 * langsung pada akun, `syncPermissions()` di sini hanya menyentuh izin scope —
 * izin milik peran (role_has_permissions) tidak berubah.
 */
class UserScopeSynchronizer
{
    /**
     * @param  array<int, string>  $scopes
     */
    public function sync(User $user, array $scopes, ?User $actor = null): void
    {
        $scopes = array_values(array_unique(array_filter(
            $scopes,
            fn (string $scope) => in_array($scope, SimmakScopes::assignableNames(), true),
        )));

        $existing = $user->scopeAssignments()->pluck('scope')->all();

        $removed = array_values(array_diff($existing, $scopes));
        if ($removed !== []) {
            $user->scopeAssignments()->whereIn('scope', $removed)->delete();
        }

        foreach (array_values(array_diff($scopes, $existing)) as $scope) {
            $user->scopeAssignments()->create([
                'scope' => $scope,
                'granted_by' => $actor?->id,
                'granted_at' => now(),
            ]);
        }

        $user->unsetRelation('scopeAssignments');

        $this->applyPermissions($user);
    }

    /**
     * Terapkan ulang izin langsung akun berdasarkan scope yang tercatat.
     */
    public function applyPermissions(User $user): void
    {
        $user->syncPermissions($this->permissionNames($user->scopeNames()));
    }

    /**
     * Selaraskan izin seluruh akun (dipakai command simmak:sync-user-scopes).
     */
    public function syncAll(): int
    {
        $count = 0;

        User::query()->chunkById(200, function (Collection $users) use (&$count): void {
            foreach ($users as $user) {
                $this->applyPermissions($user);
                $count++;
            }
        });

        return $count;
    }

    /**
     * @param  array<int, string>  $scopes
     * @return array<int, string>
     */
    public function permissionNames(array $scopes): array
    {
        $permissions = [];

        foreach ($scopes as $scope) {
            $permissions = array_merge($permissions, SimmakScopes::permissionsFor($scope));
        }

        return array_values(array_unique($permissions));
    }
}
