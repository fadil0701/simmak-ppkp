<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Position;
use App\Models\Unit;
use Illuminate\Database\Seeder;

/**
 * Data demonstrasi master pegawai & unit kerja.
 *
 * Hanya untuk lingkungan non-produksi. Tidak memuat data pasien atau data pribadi nyata.
 */
class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::where('code', 'PPKP-DKI')->first();

        if (! $organization) {
            return;
        }

        $clinics = Clinic::where('organization_id', $organization->id)->orderBy('code')->get();

        if ($clinics->isEmpty()) {
            return;
        }

        $unitNames = [
            'Instalasi Gawat Darurat',
            'Instalasi Rawat Jalan',
            'Instalasi Kefamawanan',
            'Instalasi Penunjang Medis',
            'Administrasi dan Keuangan',
        ];

        $employees = [
            ['NIP-0001', 'Aisyah Rahmawati, dr.', 'JBT-FUNG-01'],
            ['NIP-0002', 'Bagus Nugroho, dr.', 'JBT-FUNG-02'],
            ['NIP-0003', 'Citra Mutiah Sari, drg.', 'JBT-FUNG-03'],
            ['NIP-0004', 'Dewi Lestari, S.Kep., Ns.', 'JBT-FUNG-04'],
            ['NIP-0005', 'Eko Prasetyo, S.Kep.', 'JBT-FUNG-04'],
            ['NIP-0006', 'Fitri Handayani, S.Keb.', 'JBT-FUNG-05'],
            ['NIP-0007', 'Gunawan Saputra, S.Farm.', 'JBT-FUNG-06'],
            ['NIP-0008', 'Hesti Wulandari, S.Si.', 'JBT-FUNG-07'],
            ['NIP-0009', 'Indah Permata, S.E.', 'JBT-TEK-02'],
            ['NIP-0010', 'Joko Susilo, dr.', 'JBT-FUNG-02'],
        ];

        foreach ($employees as $index => $data) {
            $clinic = $clinics[$index % $clinics->count()];

            $unit = Unit::firstOrCreate(
                ['clinic_id' => $clinic->id, 'code' => 'U'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)],
                [
                    'organization_id' => $organization->id,
                    'name' => $unitNames[$index % count($unitNames)],
                    'unit_type' => 'pelayanan',
                    'is_active' => true,
                ]
            );

            $position = Position::where('organization_id', $organization->id)
                ->where('code', $data[2])
                ->first();

            Employee::firstOrCreate(
                ['organization_id' => $organization->id, 'employee_number' => $data[0]],
                [
                    'clinic_id' => $clinic->id,
                    'unit_id' => $unit->id,
                    'position_id' => $position?->id,
                    'full_name' => $data[1],
                    'position' => $position?->name,
                    'email' => str($data[1])->before(',')->slug('.')->append('@simmak.ppkp.local')->toString(),
                    'phone' => '021-55500'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                    'is_active' => true,
                ]
            );
        }
    }
}
