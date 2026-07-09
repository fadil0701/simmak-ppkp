<?php

namespace Database\Seeders;

use App\Models\AuditChecklist;
use App\Models\AuditChecklistItem;
use App\Models\AuditReport;
use App\Models\AuditSchedule;
use App\Models\CapaCase;
use App\Models\CapaRootCauseAnalysis;
use App\Models\CapaVerification;
use App\Models\Clinic;
use App\Models\FiscalYear;
use App\Models\Organization;
use App\Models\OrganizationSetting;
use App\Models\QualityIndicator;
use App\Models\QualityMeasurement;
use App\Models\QualityProgram;
use App\Models\RiskItem;
use App\Models\RiskMatrix;
use App\Models\RiskMitigationPlan;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExtendedModuleSeeder extends Seeder
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

        RiskMatrix::firstOrCreate(
            ['organization_id' => $organization->id, 'name' => 'Matriks Risiko 5x5 Standar'],
            [
                'likelihood_labels' => [
                    1 => 'Sangat Jarang',
                    2 => 'Jarang',
                    3 => 'Kadang',
                    4 => 'Sering',
                    5 => 'Sangat Sering',
                ],
                'impact_labels' => [
                    1 => 'Sangat Rendah',
                    2 => 'Rendah',
                    3 => 'Sedang',
                    4 => 'Tinggi',
                    5 => 'Sangat Tinggi',
                ],
                'level_thresholds' => [
                    'low' => ['min' => 1, 'max' => 5],
                    'medium' => ['min' => 6, 'max' => 11],
                    'high' => ['min' => 12, 'max' => 19],
                    'extreme' => ['min' => 20, 'max' => 25],
                ],
                'is_default' => true,
                'is_active' => true,
            ]
        );

        $schedule = AuditSchedule::where('organization_id', $organization->id)->orderBy('created_at')->first();

        if ($schedule) {
            $checklist = AuditChecklist::firstOrCreate(
                [
                    'organization_id' => $organization->id,
                    'audit_schedule_id' => $schedule->id,
                    'title' => 'Checklist Audit Internal — Tata Kelola & Dokumen Mutu',
                ],
                ['status' => 'in_progress']
            );

            $checklistItems = [
                ['item_code' => 'CHK-01', 'item_text' => 'Kebijakan mutu dan sasaran mutu tersedia dan dipahami staf', 'sort_order' => 1, 'is_checked' => true],
                ['item_code' => 'CHK-02', 'item_text' => 'SOP pelayanan pasien terkini dan terdistribusi', 'sort_order' => 2, 'is_checked' => true],
                ['item_code' => 'CHK-03', 'item_text' => 'Identifikasi pasien dilakukan secara konsisten', 'sort_order' => 3, 'is_checked' => false],
                ['item_code' => 'CHK-04', 'item_text' => 'Pencatatan suhu rantai dingin vaksin lengkap', 'sort_order' => 4, 'is_checked' => false],
                ['item_code' => 'CHK-05', 'item_text' => 'Ketersediaan APD di area pelayanan sesuai kebutuhan', 'sort_order' => 5, 'is_checked' => true],
            ];

            foreach ($checklistItems as $item) {
                AuditChecklistItem::firstOrCreate(
                    ['checklist_id' => $checklist->id, 'item_code' => $item['item_code']],
                    [
                        'item_text' => $item['item_text'],
                        'is_checked' => $item['is_checked'],
                        'sort_order' => $item['sort_order'],
                    ]
                );
            }

            AuditReport::firstOrCreate(
                ['organization_id' => $organization->id, 'report_number' => 'RPT-2025-001'],
                [
                    'audit_schedule_id' => $schedule->id,
                    'executive_summary' => 'Audit internal menemukan kepatuhan umum yang baik terhadap standar dokumentasi mutu, dengan beberapa temuan pada identifikasi pasien dan pencatatan suhu vaksin.',
                    'findings_count' => $schedule->findings()->count(),
                    'status' => 'draft',
                    'report_date' => now()->toDateString(),
                    'prepared_by' => $admin->id,
                ]
            );
        }

        $capa = CapaCase::where('organization_id', $organization->id)->orderBy('created_at')->first();

        if ($capa) {
            CapaRootCauseAnalysis::firstOrCreate(
                ['capa_case_id' => $capa->id, 'method' => 'five_why'],
                [
                    'organization_id' => $organization->id,
                    'content' => [
                        'whys' => [
                            ['question' => 'Mengapa SOP tidak terbarui?', 'answer' => 'Review dokumen tidak dilakukan sesuai jadwal.'],
                            ['question' => 'Mengapa review tidak dilakukan?', 'answer' => 'Tidak ada reminder otomatis sebelum kedaluwarsa.'],
                            ['question' => 'Mengapa tidak ada reminder?', 'answer' => 'Sistem DMS belum dikonfigurasi untuk notifikasi review.'],
                            ['question' => 'Mengapa belum dikonfigurasi?', 'answer' => 'PIC dokumen mutu belum menerima pelatihan modul DMS.'],
                            ['question' => 'Mengapa belum dilatih?', 'answer' => 'Jadwal pelatihan DMS belum dimasukkan dalam rencana pengembangan SDM tahun ini.'],
                        ],
                        'root_cause' => 'Belum ada mekanisme terstruktur (reminder + pelatihan) untuk menjaga siklus review dokumen mutu.',
                    ],
                    'created_by' => $admin->id,
                ]
            );

            CapaVerification::firstOrCreate(
                [
                    'capa_case_id' => $capa->id,
                    'verification_date' => now()->addDays(14)->toDateString(),
                ],
                [
                    'organization_id' => $organization->id,
                    'result' => 'pending',
                    'notes' => 'Verifikasi efektivitas akan dilakukan setelah seluruh tindakan korektif selesai.',
                    'is_effective' => false,
                    'verified_by' => $admin->id,
                ]
            );
        }

        $riskItem = RiskItem::where('organization_id', $organization->id)->orderBy('risk_code')->first();

        if ($riskItem) {
            RiskMitigationPlan::firstOrCreate(
                [
                    'organization_id' => $organization->id,
                    'risk_item_id' => $riskItem->id,
                    'plan_description' => 'Implementasi protokol identifikasi pasien ganda (nama + tanggal lahir + nomor rekam medis) di seluruh titik pelayanan.',
                ],
                [
                    'status' => 'in_progress',
                    'target_date' => now()->addMonths(2)->toDateString(),
                    'owner_id' => $admin->id,
                ]
            );
        }

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

        $program = QualityProgram::firstOrCreate(
            ['organization_id' => $organization->id, 'program_code' => 'QP-2025-01'],
            [
                'clinic_id' => $clinic?->id,
                'fiscal_year_id' => $fiscalYear->id,
                'name' => 'Program Mutu Pelayanan Pasien 2025',
                'objective' => 'Meningkatkan kepuasan pasien, keselamatan pasien, dan ketepatan waktu pelayanan di seluruh klinik PPKP DKI Jakarta.',
                'status' => 'active',
            ]
        );

        $indicators = [
            ['indicator_code' => 'KPI-01', 'name' => 'Tingkat Kepuasan Pasien', 'unit' => '%', 'target_value' => 85.00, 'direction' => 'higher_better', 'base_value' => 78.0],
            ['indicator_code' => 'KPI-02', 'name' => 'Ketepatan Waktu Pelayanan', 'unit' => 'menit', 'target_value' => 30.00, 'direction' => 'lower_better', 'base_value' => 42.0],
            ['indicator_code' => 'KPI-03', 'name' => 'Insiden Keselamatan Pasien', 'unit' => 'kasus', 'target_value' => 0.00, 'direction' => 'lower_better', 'base_value' => 2.0],
        ];

        foreach ($indicators as $indicatorData) {
            $indicator = QualityIndicator::firstOrCreate(
                [
                    'organization_id' => $organization->id,
                    'quality_program_id' => $program->id,
                    'indicator_code' => $indicatorData['indicator_code'],
                ],
                [
                    'name' => $indicatorData['name'],
                    'unit' => $indicatorData['unit'],
                    'target_value' => $indicatorData['target_value'],
                    'direction' => $indicatorData['direction'],
                    'is_active' => true,
                ]
            );

            for ($monthsAgo = 5; $monthsAgo >= 0; $monthsAgo--) {
                $measurementDate = now()->subMonths($monthsAgo)->startOfMonth()->toDateString();
                $progress = (5 - $monthsAgo) / 5;
                $value = match ($indicatorData['direction']) {
                    'higher_better' => round($indicatorData['base_value'] + ($indicatorData['target_value'] - $indicatorData['base_value']) * $progress, 2),
                    default => round($indicatorData['base_value'] - ($indicatorData['base_value'] - $indicatorData['target_value']) * $progress, 2),
                };

                QualityMeasurement::firstOrCreate(
                    [
                        'quality_indicator_id' => $indicator->id,
                        'measurement_date' => $measurementDate,
                    ],
                    [
                        'organization_id' => $organization->id,
                        'clinic_id' => $clinic?->id,
                        'value' => $value,
                        'notes' => 'Pengukuran bulanan program mutu.',
                        'recorded_by' => $admin->id,
                    ]
                );
            }
        }

        OrganizationSetting::firstOrCreate(
            ['organization_id' => $organization->id, 'group' => 'general', 'key' => 'app_name'],
            ['value' => 'SIMMAK PPKP', 'value_type' => 'string']
        );

        OrganizationSetting::firstOrCreate(
            ['organization_id' => $organization->id, 'group' => 'general', 'key' => 'accreditation_target'],
            ['value' => '2025-12-31', 'value_type' => 'date']
        );
    }
}
