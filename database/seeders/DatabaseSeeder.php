<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::create([
            'code' => 'PPKP-DKI',
            'name' => 'PPKP Provinsi DKI Jakarta',
            'org_type' => 'central',
            'address' => 'Jakarta',
            'phone' => '021-0000000',
            'email' => 'info@ppkp.jakarta.go.id',
            'is_active' => true,
        ]);

        $clinics = [
            ['code' => 'MP', 'name' => 'Manajemen Pusat', 'clinic_type' => 'pusat', 'is_main_clinic' => false, 'city' => 'Jakarta Pusat'],
            ['code' => 'KU', 'name' => 'Klinik Utama PPKP', 'clinic_type' => 'utama', 'is_main_clinic' => true, 'city' => 'Jakarta', 'accreditation_type' => 'FKTL'],
            ['code' => 'KP01', 'name' => 'Klinik Pratama Jakarta Pusat', 'clinic_type' => 'pratama', 'is_main_clinic' => false, 'city' => 'Jakarta Pusat', 'accreditation_type' => 'FKTP'],
            ['code' => 'KP02', 'name' => 'Klinik Pratama Jakarta Utara', 'clinic_type' => 'pratama', 'is_main_clinic' => false, 'city' => 'Jakarta Utara', 'accreditation_type' => 'FKTP'],
            ['code' => 'KP03', 'name' => 'Klinik Pratama Jakarta Barat', 'clinic_type' => 'pratama', 'is_main_clinic' => false, 'city' => 'Jakarta Barat', 'accreditation_type' => 'FKTP'],
            ['code' => 'KP04', 'name' => 'Klinik Pratama Jakarta Selatan', 'clinic_type' => 'pratama', 'is_main_clinic' => false, 'city' => 'Jakarta Selatan', 'accreditation_type' => 'FKTP'],
            ['code' => 'KP05', 'name' => 'Klinik Pratama Jakarta Timur', 'clinic_type' => 'pratama', 'is_main_clinic' => false, 'city' => 'Jakarta Timur', 'accreditation_type' => 'FKTP'],
            ['code' => 'KP06', 'name' => 'Klinik Pratama Tebet', 'clinic_type' => 'pratama', 'is_main_clinic' => false, 'city' => 'Jakarta Selatan', 'accreditation_type' => 'FKTP'],
            ['code' => 'KP07', 'name' => 'Klinik Pratama Cempaka Putih', 'clinic_type' => 'pratama', 'is_main_clinic' => false, 'city' => 'Jakarta Pusat', 'accreditation_type' => 'FKTP'],
            ['code' => 'KP08', 'name' => 'Klinik Pratama Tanah Abang', 'clinic_type' => 'pratama', 'is_main_clinic' => false, 'city' => 'Jakarta Pusat', 'accreditation_type' => 'FKTP'],
            ['code' => 'KP09', 'name' => 'Klinik Pratama Palmerah', 'clinic_type' => 'pratama', 'is_main_clinic' => false, 'city' => 'Jakarta Barat', 'accreditation_type' => 'FKTP'],
            ['code' => 'KP10', 'name' => 'Klinik Pratama Kebayoran', 'clinic_type' => 'pratama', 'is_main_clinic' => false, 'city' => 'Jakarta Selatan', 'accreditation_type' => 'FKTP'],
            ['code' => 'KP11', 'name' => 'Klinik Pratama Cakung', 'clinic_type' => 'pratama', 'is_main_clinic' => false, 'city' => 'Jakarta Timur', 'accreditation_type' => 'FKTP'],
            ['code' => 'KP12', 'name' => 'Klinik Pratama Pademangan', 'clinic_type' => 'pratama', 'is_main_clinic' => false, 'city' => 'Jakarta Utara', 'accreditation_type' => 'FKTP'],
            ['code' => 'KP13', 'name' => 'Klinik Pratama Kelapa Gading', 'clinic_type' => 'pratama', 'is_main_clinic' => false, 'city' => 'Jakarta Utara', 'accreditation_type' => 'FKTP'],
            ['code' => 'KP14', 'name' => 'Klinik Pratama Cengkareng', 'clinic_type' => 'pratama', 'is_main_clinic' => false, 'city' => 'Jakarta Barat', 'accreditation_type' => 'FKTP'],
            ['code' => 'KP15', 'name' => 'Klinik Pratama Pasar Minggu', 'clinic_type' => 'pratama', 'is_main_clinic' => false, 'city' => 'Jakarta Selatan', 'accreditation_type' => 'FKTP'],
        ];

        $clinicModels = collect($clinics)->map(function (array $data) use ($organization) {
            return Clinic::create([
                ...$data,
                'organization_id' => $organization->id,
                'accreditation_status' => 'in_progress',
                'is_active' => true,
            ]);
        });

        $mainClinic = $clinicModels->firstWhere('code', 'KU');

        $permissions = [
            'dashboard.view',
            'dashboard.executive',
            'dashboard.clinic',
            'dms.view', 'dms.manage',
            'audit.view', 'audit.manage',
            'capa.view', 'capa.manage',
            'risk.view', 'risk.manage',
            'accreditation.view', 'accreditation.manage',
            'quality.view', 'quality.manage',
            'users.view', 'users.manage',
            'settings.view', 'settings.manage',
            'workflow.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $roles = [
            'super-admin' => $permissions,
            'admin-mutu' => array_filter($permissions, fn ($p) => ! str_starts_with($p, 'users.')),
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

        $admin = User::create([
            'organization_id' => $organization->id,
            'default_clinic_id' => $mainClinic?->id,
            'name' => 'Administrator SIMMAK',
            'username' => 'admin',
            'email' => 'admin@simmak.ppkp.local',
            'password' => Hash::make('password'),
            'auth_provider' => 'local',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $admin->assignRole('super-admin');
        $admin->clinics()->sync($clinicModels->pluck('id')->mapWithKeys(
            fn ($id) => [$id => ['is_default' => $id === $mainClinic?->id]]
        ));
    }
}
