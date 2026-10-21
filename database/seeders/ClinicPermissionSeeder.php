<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ClinicPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = ['clinics.view', 'clinics.manage'];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Izin klinik diberikan ke role yang sudah mengelola master data / konfigurasi.
        foreach (['super-admin', 'admin-mutu'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                $role->syncPermissions(array_unique(array_merge(
                    $role->permissions->pluck('name')->all(),
                    $permissions
                )));
            }
        }

        Role::where('guard_name', 'web')->get()->each(fn (Role $role) => $role->forgetCachedPermissions());
    }
}
