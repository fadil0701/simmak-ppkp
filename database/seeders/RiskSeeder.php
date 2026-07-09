<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\FiscalYear;
use App\Models\Organization;
use App\Models\RiskCategory;
use App\Models\RiskItem;
use App\Models\RiskRegister;
use App\Models\User;
use App\Services\RiskService;
use Illuminate\Database\Seeder;

class RiskSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::first();
        $admin = User::where('email', 'admin@simmak.ppkp.local')->first();
        $clinic = Clinic::where('organization_id', $organization?->id)->where('clinic_type', 'utama')->first()
            ?? Clinic::where('organization_id', $organization?->id)->first();

        if (! $organization || ! $admin) {
            return;
        }

        $riskService = app(RiskService::class);

        $fiscalYear = FiscalYear::firstOrCreate(
            ['organization_id' => $organization->id, 'code' => 'FY2025'],
            [
                'name' => 'Tahun Anggaran 2025',
                'start_date' => '2025-01-01',
                'end_date' => '2025-12-31',
                'is_current' => true,
                'is_active' => true,
            ]
        );

        $categories = [
            ['code' => 'KLN', 'name' => 'Klinis & Keselamatan Pasien'],
            ['code' => 'OPS', 'name' => 'Operasional & Layanan'],
            ['code' => 'FIN', 'name' => 'Keuangan & Aset'],
            ['code' => 'HRM', 'name' => 'SDM & Kompetensi'],
            ['code' => 'CMP', 'name' => 'Kepatuhan & Regulasi'],
        ];

        $categoryModels = collect($categories)->map(fn (array $cat) => RiskCategory::firstOrCreate(
            ['organization_id' => $organization->id, 'code' => $cat['code']],
            ['name' => $cat['name'], 'is_active' => true],
        ));

        $register = RiskRegister::firstOrCreate(
            ['organization_id' => $organization->id, 'register_code' => 'RR-2025-ORG'],
            [
                'clinic_id' => $clinic?->id,
                'fiscal_year_id' => $fiscalYear->id,
                'name' => 'Register Risiko Organisasi 2025',
                'status' => 'active',
                'owner_id' => $admin->id,
                'created_by' => $admin->id,
            ]
        );

        $sampleRisks = [
            ['title' => 'Keterlambatan identifikasi pasien kritis', 'category' => 0, 'l' => 4, 'i' => 5, 'rl' => 3, 'ri' => 4, 'status' => 'mitigated'],
            ['title' => 'Kekurangan stok obat esensial', 'category' => 1, 'l' => 3, 'i' => 4, 'rl' => 2, 'ri' => 3, 'status' => 'monitored'],
            ['title' => 'Ketidakpatuhan SOP sterilisasi alat', 'category' => 0, 'l' => 3, 'i' => 5, 'rl' => 2, 'ri' => 4, 'status' => 'identified'],
            ['title' => 'Kebocoran data rekam medis elektronik', 'category' => 4, 'l' => 2, 'i' => 5, 'rl' => 2, 'ri' => 3, 'status' => 'assessed'],
            ['title' => 'Turnover tenaga kesehatan tinggi', 'category' => 3, 'l' => 4, 'i' => 3, 'rl' => 3, 'ri' => 2, 'status' => 'monitored'],
            ['title' => 'Kegagalan sistem informasi klinik', 'category' => 1, 'l' => 2, 'i' => 4, 'rl' => 2, 'ri' => 3, 'status' => 'identified'],
            ['title' => 'Keterlambatan pelaporan keuangan', 'category' => 2, 'l' => 3, 'i' => 3, 'rl' => 2, 'ri' => 2, 'status' => 'closed'],
            ['title' => 'Insiden keselamatan kerja di laboratorium', 'category' => 0, 'l' => 2, 'i' => 4, 'rl' => 1, 'ri' => 3, 'status' => 'mitigated'],
        ];

        foreach ($sampleRisks as $idx => $data) {
            $riskCode = sprintf('RSK-2025-%04d', $idx + 1);
            $inherentScore = $riskService->calculateScore($data['l'], $data['i']);
            $residualScore = $riskService->calculateScore($data['rl'], $data['ri']);

            RiskItem::firstOrCreate(
                ['organization_id' => $organization->id, 'risk_code' => $riskCode],
                [
                    'risk_register_id' => $register->id,
                    'risk_category_id' => $categoryModels[$data['category']]->id,
                    'clinic_id' => $clinic?->id,
                    'title' => $data['title'],
                    'risk_description' => 'Risiko teridentifikasi dalam assessment mutu organisasi PPKP DKI Jakarta.',
                    'risk_source' => 'Audit internal & assessment mandiri',
                    'inherent_likelihood' => $data['l'],
                    'inherent_impact' => $data['i'],
                    'inherent_score' => $inherentScore,
                    'residual_likelihood' => $data['rl'],
                    'residual_impact' => $data['ri'],
                    'residual_score' => $residualScore,
                    'risk_level' => $riskService->riskLevel($inherentScore),
                    'status' => $data['status'],
                    'owner_id' => $admin->id,
                    'review_date' => now()->addMonths(rand(1, 6))->toDateString(),
                    'created_by' => $admin->id,
                ]
            );
        }
    }
}
