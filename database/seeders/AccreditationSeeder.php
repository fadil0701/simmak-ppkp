<?php

namespace Database\Seeders;

use App\Models\AccreditationChapter;
use App\Models\AccreditationEvidence;
use App\Models\AccreditationGapItem;
use App\Models\AccreditationMockSurvey;
use App\Models\AccreditationProgram;
use App\Models\AccreditationSelfAssessment;
use App\Models\AccreditationSelfAssessmentItem;
use App\Models\AccreditationStandard;
use App\Models\AccreditationType;
use App\Models\Clinic;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

class AccreditationSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::first();
        $admin = User::where('email', 'admin@simmak.ppkp.local')->first();

        if (! $organization || ! $admin) {
            return;
        }

        $fktp = AccreditationType::firstOrCreate(
            ['code' => 'FKTP'],
            ['name' => 'Fasilitas Kesehatan Tingkat Pertama', 'description' => 'Standar akreditasi FKTP KARS', 'issuing_body' => 'KARS', 'is_active' => true]
        );

        $fktl = AccreditationType::firstOrCreate(
            ['code' => 'FKTL'],
            ['name' => 'Fasilitas Kesehatan Tingkat Lanjut', 'description' => 'Standar akreditasi FKTL KARS', 'issuing_body' => 'KARS', 'is_active' => true]
        );

        $fktpChapters = [
            ['num' => '1', 'title' => 'Kepemimpinan dan Tata Kelola', 'standards' => [
                ['code' => '1.1', 'title' => 'Kebijakan dan komitmen mutu'],
                ['code' => '1.2', 'title' => 'Struktur organisasi dan tanggung jawab'],
                ['code' => '1.3', 'title' => 'Perencanaan strategis'],
            ]],
            ['num' => '2', 'title' => 'Manajemen Risiko Keselamatan Pasien', 'standards' => [
                ['code' => '2.1', 'title' => 'Identifikasi dan analisis risiko'],
                ['code' => '2.2', 'title' => 'Pencegahan dan pengendalian risiko'],
            ]],
            ['num' => '3', 'title' => 'Peningkatan Mutu dan Keselamatan Pasien', 'standards' => [
                ['code' => '3.1', 'title' => 'Program peningkatan mutu'],
                ['code' => '3.2', 'title' => 'Pemantauan indikator mutu'],
            ]],
            ['num' => '4', 'title' => 'Manajemen Sumber Daya Manusia', 'standards' => [
                ['code' => '4.1', 'title' => 'Perencanaan dan kompetensi SDM'],
                ['code' => '4.2', 'title' => 'Pengembangan dan evaluasi SDM'],
            ]],
            ['num' => '5', 'title' => 'Manajemen Fasilitas dan Lingkungan', 'standards' => [
                ['code' => '5.1', 'title' => 'Keselamatan fasilitas'],
                ['code' => '5.2', 'title' => 'Pengelolaan lingkungan'],
            ]],
            ['num' => '6', 'title' => 'Standar Klinis', 'standards' => [
                ['code' => '6.1', 'title' => 'Proses klinis dan keselamatan pasien'],
                ['code' => '6.2', 'title' => 'Pelayanan pasien terintegrasi'],
            ]],
        ];

        $fktpStandards = $this->seedChapters($fktp, $fktpChapters);

        $fktlChapters = [
            ['num' => '1', 'title' => 'Tata Kelola Klinik', 'standards' => [
                ['code' => '1.1', 'title' => 'Kepemimpinan klinik'],
                ['code' => '1.2', 'title' => 'Manajemen mutu klinik'],
            ]],
            ['num' => '2', 'title' => 'Keselamatan Pasien', 'standards' => [
                ['code' => '2.1', 'title' => 'Program keselamatan pasien'],
            ]],
            ['num' => '3', 'title' => 'Pelayanan Klinis', 'standards' => [
                ['code' => '3.1', 'title' => 'Standar pelayanan klinis'],
            ]],
        ];

        $fktlStandards = $this->seedChapters($fktl, $fktlChapters);

        $clinicFktp = Clinic::where('code', 'KP06')->first()
            ?? Clinic::where('accreditation_type', 'FKTP')->first();
        $clinicFktl = Clinic::where('code', 'KU')->first();

        if ($clinicFktp) {
            $this->seedProgram($organization, $admin, $clinicFktp, $fktp, $fktpStandards, [
                'chapter_scores' => [90, 85, 80, 70, 85, 75],
                'overall' => 76,
                'mock' => 84,
                'gaps' => ['critical' => 3, 'major' => 5, 'minor' => 7],
                'evidence_total' => 20,
                'evidence_uploaded' => 16,
            ]);
        }

        if ($clinicFktl) {
            $this->seedProgram($organization, $admin, $clinicFktl, $fktl, $fktlStandards, [
                'chapter_scores' => [88, 82, 79],
                'overall' => 80,
                'mock' => 86,
                'gaps' => ['critical' => 2, 'major' => 4, 'minor' => 5],
                'evidence_total' => 15,
                'evidence_uploaded' => 12,
            ]);
        }
    }

    /**
     * @return array<string, AccreditationStandard>
     */
    private function seedChapters(AccreditationType $type, array $chapters): array
    {
        $standards = [];

        foreach ($chapters as $order => $chapterData) {
            $chapter = AccreditationChapter::firstOrCreate(
                ['accreditation_type_id' => $type->id, 'chapter_number' => $chapterData['num']],
                ['title' => $chapterData['title'], 'sort_order' => $order + 1, 'is_active' => true]
            );

            foreach ($chapterData['standards'] as $sOrder => $std) {
                $standard = AccreditationStandard::firstOrCreate(
                    ['chapter_id' => $chapter->id, 'standard_code' => $std['code']],
                    ['title' => $std['title'], 'sort_order' => $sOrder + 1, 'is_active' => true]
                );
                $standards[$std['code']] = $standard;
            }
        }

        return $standards;
    }

  /**
     * @param  array<string, AccreditationStandard>  $standards
     */
    private function seedProgram(
        Organization $organization,
        User $admin,
        Clinic $clinic,
        AccreditationType $type,
        array $standards,
        array $config,
    ): void {
        $program = AccreditationProgram::firstOrCreate(
            [
                'organization_id' => $organization->id,
                'clinic_id' => $clinic->id,
                'accreditation_type_id' => $type->id,
                'cycle' => '2024-2027',
            ],
            [
                'program_name' => 'Program Akreditasi '.$type->code.' '.$clinic->name,
                'start_date' => '2024-01-12',
                'target_survey_date' => '2025-11-30',
                'status' => 'mock',
                'created_by' => $admin->id,
            ]
        );

        $assessment = AccreditationSelfAssessment::firstOrCreate(
            [
                'organization_id' => $organization->id,
                'program_id' => $program->id,
                'clinic_id' => $clinic->id,
            ],
            [
                'assessment_date' => '2024-05-20',
                'assessor_id' => $admin->id,
                'overall_score' => $config['overall'],
                'status' => 'submitted',
                'summary' => 'Self assessment '.$type->code.' telah diselesaikan dan menunggu review.',
                'created_by' => $admin->id,
            ]
        );

        foreach ($standards as $code => $standard) {
            $chapterNum = (int) explode('.', $code)[0];
            $score = $chapterScores[$chapterNum - 1] ?? 75;
            $score = max(50, min(100, $score + (crc32($code) % 5) - 2));

            $compliance = match (true) {
                $score >= 85 => 'compliant',
                $score >= 70 => 'partial',
                default => 'non_compliant',
            };

            AccreditationSelfAssessmentItem::firstOrCreate(
                [
                    'self_assessment_id' => $assessment->id,
                    'standard_id' => $standard->id,
                ],
                [
                    'organization_id' => $organization->id,
                    'score' => $score + (crc32($code) % 5) - 2,
                    'compliance_status' => $compliance,
                    'notes' => 'Hasil penilaian mandiri standar '.$code,
                ]
            );
        }

        AccreditationMockSurvey::firstOrCreate(
            [
                'organization_id' => $organization->id,
                'program_id' => $program->id,
                'clinic_id' => $clinic->id,
                'survey_date' => '2024-05-15',
            ],
            [
                'score' => $config['mock'],
                'surveyor_name' => 'Tim Surveyor Internal PPKP',
                'status' => 'completed',
                'summary' => 'Mock survey menunjukkan kesiapan baik dengan beberapa area perbaikan.',
            ]
        );

        $gapSamples = [
            ['severity' => 'critical', 'desc' => 'Dokumen kebijakan keselamatan pasien belum lengkap dan belum disosialisasikan'],
            ['severity' => 'critical', 'desc' => 'Prosedur identifikasi pasien belum distandardisasi di seluruh unit'],
            ['severity' => 'major', 'desc' => 'Rekam medis elektronik belum terintegrasi penuh dengan sistem informasi'],
            ['severity' => 'major', 'desc' => 'Program pelatihan SDM belum mencakup seluruh kompetensi wajib'],
            ['severity' => 'minor', 'desc' => 'Format dokumentasi audit internal perlu diseragamkan'],
            ['severity' => 'minor', 'desc' => 'Penandaan area restricted access perlu diperjelas'],
        ];

        $gapCounts = $config['gaps'];
        $created = 0;

        foreach (['critical', 'major', 'minor'] as $severity) {
            for ($i = 0; $i < ($gapCounts[$severity] ?? 0); $i++) {
                $sample = $gapSamples[$created % count($gapSamples)];
                $std = array_values($standards)[$created % count($standards)] ?? null;

                AccreditationGapItem::firstOrCreate(
                    [
                        'organization_id' => $organization->id,
                        'program_id' => $program->id,
                        'gap_description' => $sample['desc'].' ('.$clinic->code.' #'.($i + 1).')',
                    ],
                    [
                        'clinic_id' => $clinic->id,
                        'standard_id' => $std?->id,
                        'severity' => $severity,
                        'status' => 'open',
                        'recommended_action' => 'Susun rencana perbaikan dan assign PIC',
                        'target_date' => now()->addMonths(2)->toDateString(),
                        'created_by' => $admin->id,
                    ]
                );
                $created++;
            }
        }

        for ($i = 1; $i <= $config['evidence_total']; $i++) {
            $status = $i <= $config['evidence_uploaded'] ? 'uploaded' : 'required';

            AccreditationEvidence::firstOrCreate(
                [
                    'organization_id' => $organization->id,
                    'program_id' => $program->id,
                    'title' => 'Bukti Akreditasi #'.$i.' — '.$clinic->code,
                ],
                [
                    'clinic_id' => $clinic->id,
                    'standard_id' => array_values($standards)[$i % count($standards)]?->id,
                    'status' => $status,
                ]
            );
        }
    }
}
