<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\FiscalYear;
use App\Models\Organization;
use App\Support\SimmakRoles;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::firstOrCreate(
            ['code' => 'PPKP-DKI'],
            [
                'name' => 'UPT Pusat Pelayanan Kesehatan Pegawai DKI Jakarta',
                'org_type' => 'central',
                'address' => 'Jakarta',
                'phone' => '021-3823065',
                'email' => 'puspelkes@jakarta.go.id',
                'is_active' => true,
            ]
        );

        $clinics = [
            ['code' => 'MP', 'name' => 'Manajemen', 'clinic_type' => 'pusat', 'is_main_clinic' => false, 'is_auditable' => false, 'city' => 'Jakarta Pusat'],
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
            return Clinic::updateOrCreate(
                ['code' => $data['code'], 'organization_id' => $organization->id],
                array_merge($data, [
                    'organization_id' => $organization->id,
                    'accreditation_status' => $data['accreditation_status'] ?? 'in_progress',
                    'is_active' => true,
                ])
            );
        });

        // Tahun anggaran sebagai master (dipakai program audit, mutu, risiko).
        foreach ([2024, 2025, 2026, 2027] as $year) {
            FiscalYear::firstOrCreate(
                ['organization_id' => $organization->id, 'code' => 'FY'.$year],
                [
                    'name' => 'Tahun Anggaran '.$year,
                    'start_date' => $year.'-01-01',
                    'end_date' => $year.'-12-31',
                    'is_current' => $year === (int) date('Y'),
                    'is_active' => true,
                ]
            );
        }

        SimmakRoles::sync();

        $this->call(RoleUsersSeeder::class);
        $this->call(ClinicPermissionSeeder::class);
        $this->call(AuditPermissionSeeder::class);

        $this->call(DocumentSeeder::class);
        $this->call(AuditCapaSeeder::class);
        $this->call(RiskSeeder::class);
        $this->call(AccreditationSeeder::class);
        $this->call(ExtendedModuleSeeder::class);
        $this->call(AuditInstrumentSeeder::class);
        $this->call(PositionSeeder::class);
        $this->call(EmployeeSeeder::class);
        $this->call(AuditDemoSeeder::class);
    }
}
