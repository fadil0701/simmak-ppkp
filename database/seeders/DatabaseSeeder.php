<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\Organization;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::firstOrCreate(
            ['code' => 'PPKP-DKI'],
            [
                'name' => 'PPKP Provinsi DKI Jakarta',
                'org_type' => 'central',
                'address' => 'Jakarta',
                'phone' => '021-0000000',
                'email' => 'info@ppkp.jakarta.go.id',
                'is_active' => true,
            ]
        );

        $clinics = [
            ['code' => 'MP', 'name' => 'Manajemen Pusat', 'clinic_type' => 'pusat', 'is_main_clinic' => false, 'is_auditable' => false, 'city' => 'Jakarta Pusat'],
            ['code' => 'KU', 'name' => 'Klinik Utama Balaikota', 'clinic_type' => 'utama', 'is_main_clinic' => true, 'is_auditable' => true, 'city' => 'Jakarta Pusat', 'accreditation_type' => 'FKTL'],
            ['code' => 'KP01', 'name' => 'Klinik Pratama Satpelkes Balaikota', 'clinic_type' => 'pratama', 'is_main_clinic' => false, 'is_auditable' => true, 'city' => 'Jakarta Pusat', 'accreditation_type' => 'FKTP'],
            ['code' => 'KP02', 'name' => 'Klinik Pratama Satpelkes DPRD DKI Jakarta', 'clinic_type' => 'pratama', 'is_main_clinic' => false, 'is_auditable' => true, 'city' => 'Jakarta Pusat', 'accreditation_type' => 'FKTP'],
            ['code' => 'KP03', 'name' => 'Klinik Pratama Satpelkes Dinas Kehutanan dan Taman Kota', 'clinic_type' => 'pratama', 'is_main_clinic' => false, 'is_auditable' => true, 'city' => 'Jakarta Pusat', 'accreditation_type' => 'FKTP'],
            ['code' => 'KP04', 'name' => 'Klinik Pratama Satpelkes Dinas Kesehatan', 'clinic_type' => 'pratama', 'is_main_clinic' => false, 'is_auditable' => true, 'city' => 'Jakarta Pusat', 'accreditation_type' => 'FKTP'],
            ['code' => 'KP05', 'name' => 'Klinik Pratama Satpelkes Dinas Pariwisata dan Ekonomi Kreatif', 'clinic_type' => 'pratama', 'is_main_clinic' => false, 'is_auditable' => true, 'city' => 'Jakarta Pusat', 'accreditation_type' => 'FKTP'],
            ['code' => 'KP06', 'name' => 'Klinik Pratama Satpelkes Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu', 'clinic_type' => 'pratama', 'is_main_clinic' => false, 'is_auditable' => true, 'city' => 'Jakarta Pusat', 'accreditation_type' => 'FKTP'],
            ['code' => 'KP07', 'name' => 'Klinik Pratama Satpelkes Dinas Sosial', 'clinic_type' => 'pratama', 'is_main_clinic' => false, 'is_auditable' => true, 'city' => 'Jakarta Pusat', 'accreditation_type' => 'FKTP'],
            ['code' => 'KP08', 'name' => 'Klinik Pratama Satpelkes Dinas Teknis Jatibaru', 'clinic_type' => 'pratama', 'is_main_clinic' => false, 'is_auditable' => true, 'city' => 'Jakarta Pusat', 'accreditation_type' => 'FKTP'],
            ['code' => 'KP09', 'name' => 'Klinik Pratama Satpelkes Taman Margasatwa Ragunan', 'clinic_type' => 'pratama', 'is_main_clinic' => false, 'is_auditable' => true, 'city' => 'Jakarta Selatan', 'accreditation_type' => 'FKTP'],
            ['code' => 'KP10', 'name' => 'Klinik Pratama Satpelkes Walikota Jakarta Barat', 'clinic_type' => 'pratama', 'is_main_clinic' => false, 'is_auditable' => true, 'city' => 'Jakarta Barat', 'accreditation_type' => 'FKTP'],
            ['code' => 'KP11', 'name' => 'Klinik Pratama Satpelkes Walikota Jakarta Pusat', 'clinic_type' => 'pratama', 'is_main_clinic' => false, 'is_auditable' => true, 'city' => 'Jakarta Pusat', 'accreditation_type' => 'FKTP'],
            ['code' => 'KP12', 'name' => 'Klinik Pratama Satpelkes Walikota Jakarta Selatan', 'clinic_type' => 'pratama', 'is_main_clinic' => false, 'is_auditable' => true, 'city' => 'Jakarta Selatan', 'accreditation_type' => 'FKTP'],
            ['code' => 'KP13', 'name' => 'Klinik Pratama Satpelkes Walikota Jakarta Timur', 'clinic_type' => 'pratama', 'is_main_clinic' => false, 'is_auditable' => true, 'city' => 'Jakarta Timur', 'accreditation_type' => 'FKTP'],
            ['code' => 'KP14', 'name' => 'Klinik Pratama Satpelkes Walikota Jakarta Utara', 'clinic_type' => 'pratama', 'is_main_clinic' => false, 'is_auditable' => true, 'city' => 'Jakarta Utara', 'accreditation_type' => 'FKTP'],
        ];

        $clinicModels = collect($clinics)->map(function (array $data) use ($organization) {
            return Clinic::firstOrCreate(
                ['code' => $data['code'], 'organization_id' => $organization->id],
                array_merge($data, [
                    'organization_id' => $organization->id,
                    'accreditation_status' => 'in_progress',
                    'is_active' => true,
                ])
            );
        });

        $permissions = [
            'dashboard.view',
            'dashboard.executive',
            'dashboard.clinic',
            'dms.view', 'dms.manage',
            'audit.view', 'audit.manage',
            'audit.dashboard.view',
            'audit.report.publish',
            'audit.report.export',
            'capa.view', 'capa.manage',
            'risk.view', 'risk.manage',
            'accreditation.view', 'accreditation.manage',
            'quality.view', 'quality.manage',
            'users.view', 'users.manage',
            'settings.view', 'settings.manage',
            'clinics.view', 'clinics.manage',
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
            'tim-akreditasi' => [
                'dashboard.view', 'dashboard.clinic',
                'dms.view',
                'accreditation.view', 'accreditation.manage',
                'capa.view', 'quality.view',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($rolePermissions);
        }

        $this->call(RoleUsersSeeder::class);
        $this->call(ClinicPermissionSeeder::class);
        $this->call(AuditPermissionSeeder::class);

        $this->call(DocumentSeeder::class);
        $this->call(AuditCapaSeeder::class);
        $this->call(RiskSeeder::class);
        $this->call(AccreditationSeeder::class);
        $this->call(ExtendedModuleSeeder::class);
    }
}
