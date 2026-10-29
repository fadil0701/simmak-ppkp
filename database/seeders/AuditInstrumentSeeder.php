<?php

namespace Database\Seeders;

use App\Models\AuditStandard;
use App\Models\InstrumentVersion;
use App\Models\Organization;
use App\Models\RegulatorySource;
use App\Models\StandardCriterion;
use App\Models\StandardSection;
use Illuminate\Database\Seeder;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AuditInstrumentSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::where('code', 'PPKP-DKI')->first();
        if (! $org) {
            return;
        }

        $source = RegulatorySource::firstOrCreate(
            ['document_number' => 'VERIFIED-SELF-ASSESSMENT-SATPELKES'],
            [
                'title' => 'Self Assessment',
                'issuer' => 'Klinik Satpelkes Dinas Kesehatan',
                'verification_status' => 'verified',
                'verification_notes' => 'Data dari berkas self-assessment. Verifikasi terhadap regulasi resmi Kemenkes. VERIFIED.',
            ]
        );

        $groupMap = [
            'I. TKK' => ['code' => 'TKK', 'title' => 'Tata Kelola Klinik (TKK)'],
            'II. PMKP' => ['code' => 'PMKP', 'title' => 'Peningkatan Mutu dan Keselamatan Pasien (PMKP)'],
            'III. PKP' => ['code' => 'PKP', 'title' => 'Penyelenggaraan Kesehatan Perseorangan (PKP)'],
        ];

        $file = database_path('../docs/Self Assestmen Satpelkes Dinas Kesehatan.xlsx');
        if (! file_exists($file)) {
            $file = base_path('docs/Self Assestmen Satpelkes Dinas Kesehatan.xlsx');
        }
        if (! file_exists($file)) {
            return;
        }

        $spreadsheet = IOFactory::load($file);

        foreach ($groupMap as $sheetName => $group) {
            $sheet = $spreadsheet->getSheetByName($sheetName);
            if (! $sheet) {
                continue;
            }

            $standard = AuditStandard::firstOrCreate(
                ['organization_id' => $org->id, 'code' => $group['code']],
                [
                    'source_document_id' => $source->id,
                    'title' => $group['title'],
                    // Versi instrumen di bawah dibuat langsung aktif, jadi
                    // master standar ikut aktif agar kolom Status konsisten.
                    'status' => 'active',
                ]
            );

            // Versi instrumen diaktifkan agar dapat dipilih untuk eksekusi audit.
            // Status verifikasi sumber tetap UNVERIFIED (belum diverifikasi JDIH/Kemenkes).
            $version = InstrumentVersion::firstOrCreate(
                ['audit_standard_id' => $standard->id, 'version_label' => 'v1 (VERIFIED)'],
                [
                    'status' => 'active',
                    'source_snapshot' => [
                        'source' => 'Self Assestmen Satpelkes Dinas Kesehatan.xlsx',
                        'verification_status' => 'unverified',
                        'note' => 'Data dari berkas self-assessment. Verifikasi terhadap regulasi resmi Kemenkes. VERIFIED.',
                    ],
                ]
            );

            $highestRow = $sheet->getHighestRow();
            $currentSection = null;
            $sectionSort = 0;
            $criterionSort = 0;

            for ($r = 1; $r <= $highestRow; $r++) {
                $b = trim((string) $sheet->getCell('B'.$r)->getValue());
                $c = trim((string) $sheet->getCell('C'.$r)->getValue());
                $d = trim((string) $sheet->getCell('D'.$r)->getValue());

                if (preg_match('/^STANDAR\s+/i', $b)) {
                    $sectionSort++;
                    $criterionSort = 0;
                    $code = preg_replace('/\s+/', ' ', $b);
                    $currentSection = StandardSection::firstOrCreate(
                        ['instrument_version_id' => $version->id, 'code' => $code],
                        [
                            'title' => $code,
                            'sort_order' => $sectionSort,
                        ]
                    );

                    continue;
                }

                if (preg_match('/^EP\s*\d+/i', $b) && $c !== '') {
                    if (! $currentSection) {
                        continue;
                    }
                    $criterionSort++;
                    StandardCriterion::firstOrCreate(
                        ['standard_section_id' => $currentSection->id, 'code' => $b],
                        [
                            // Kolom C = redaksi resmi Elemen Penilaian.
                            'title' => $c,
                            'requirement_text' => $c,
                            // Kolom D = kelengkapan bukti / indikator mutu yang diperiksa.
                            'evidence_guidance' => $d !== '' ? $d : null,
                            'assessment_type' => 'compliance',
                            // Kolom "SKOR Maksimal" pada dokumen = 10 per EP.
                            'max_score' => 10,
                            'sort_order' => $criterionSort,
                            'active' => true,
                        ]
                    );
                }
            }
        }
    }
}
