<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Position;
use Illuminate\Database\Seeder;

/**
 * Master jabatan (pangkat) pegawai PPKP — kelengkapan dokumen saja.
 * Tidak dipetakan ke peran Spatie / hak akses sistem.
 */
class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::where('code', 'PPKP-DKI')->first();

        if (! $organization) {
            return;
        }

        $positions = [
            ['JBT-STRUK-01', 'Kepala Klinik', 'struktural'],
            ['JBT-STRUK-02', 'Penanggung Jawab Mutu Klinik', 'struktural'],
            ['JBT-STRUK-03', 'Koordinator Audit Internal', 'struktural'],
            ['JBT-STRUK-04', 'Ketua Tim Audit Internal', 'struktural'],
            ['JBT-STRUK-05', 'Verifikator CAPA', 'struktural'],
            ['JBT-STRUK-06', 'Penanggung Jawab Program Audit', 'struktural'],
            ['JBT-FUNG-01', 'Dokter Penanggung Jawab', 'fungsional'],
            ['JBT-FUNG-02', 'Dokter Umum', 'fungsional'],
            ['JBT-FUNG-03', 'Dokter Gigi', 'fungsional'],
            ['JBT-FUNG-04', 'Perawat', 'fungsional'],
            ['JBT-FUNG-05', 'Bidan', 'fungsional'],
            ['JBT-FUNG-06', 'Farmasis', 'fungsional'],
            ['JBT-FUNG-07', 'Analis Kesehatan', 'fungsional'],
            ['JBT-FUNG-08', 'Pusat Promosi Kesehatan', 'fungsional'],
            ['JBT-TEK-01', 'Auditor Internal', 'teknis'],
            ['JBT-TEK-02', 'Administrator', 'teknis'],
            ['JBT-TEK-03', 'Staf Sekretariat', 'teknis'],
        ];

        foreach ($positions as [$code, $name, $category]) {
            Position::firstOrCreate(
                ['organization_id' => $organization->id, 'code' => $code],
                [
                    'name' => $name,
                    'category' => $category,
                    'is_active' => true,
                ]
            );
        }
    }
}
