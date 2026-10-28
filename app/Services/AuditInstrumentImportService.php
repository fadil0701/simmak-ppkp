<?php

namespace App\Services;

use App\Models\AuditStandard;
use App\Models\InstrumentVersion;
use App\Models\StandardCriterion;
use App\Models\StandardSection;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AuditInstrumentImportService
{
    /**
     * Kolom yang diharapkan pada berkas impor (BCA / Excel).
     *
     * - kode_standar       : kode standar pada dokumen (mis. "STANDAR 1.1.")
     * - kode_ep            : kode elemen penilaian (mis. "EP 1")
     * - elemen_penilaian   : redaksi resmi EP
     * - kelengkapan_bukti  : daftar bukti yang harus diperiksa (opsional)
     * - panduan_penilaian  : panduan cara menilai EP (opsional)
     * - skor_maks          : skor maksimal EP (default 10)
     */
    public const COLUMNS = [
        'kode_standar' => 'Kode Standar',
        'kode_ep' => 'Kode EP',
        'elemen_penilaian' => 'Elemen Penilaian',
        'kelengkapan_bukti' => 'Kelengkapan Bukti',
        'panduan_penilaian' => 'Panduan Penilaian',
        'skor_maks' => 'Skor Maksimal',
    ];

    /**
     * Alias header yang diterima agar berkas dari berbagai sumber tetap terbaca.
     */
    public const HEADER_ALIASES = [
        'kode_standar' => ['kode_standar', 'kode_standar_', 'standar', 'kode_seksi', 'seksi', 'standard_code', 'kode_standar', 'kode'],
        'kode_ep' => ['kode_ep', 'ep', 'kode_elemen', 'kode_elemen_penilaian', 'ep_code', 'no_ep'],
        'elemen_penilaian' => ['elemen_penilaian', 'elemen_penilaian_', 'penilaian', 'uraian_ep', 'elemen', 'description', 'keterangan'],
        'kelengkapan_bukti' => ['kelengkapan_bukti', 'kelengkapan_bukti_', 'bukti', 'kelengkapan', 'evidence', 'daftar_bukti'],
        'panduan_penilaian' => ['panduan_penilaian', 'panduan_penilaian_', 'panduan', 'assessment_guidance', 'petunjuk_penilaian'],
        'skor_maks' => ['skor_maks', 'skor_maksimal', 'skor', 'skor_maks_', 'max_score', 'bobot'],
    ];

    /**
     * Membaca berkas Excel/CSV menjadi array baris mentah.
     *
     * @return array{rows: array<int, array<string, string>>, errors: array<int, string>}
     */
    public function parse(UploadedFile $file): array
    {
        $rows = [];
        $errors = [];

        try {
            if (in_array(strtolower($file->getClientOriginalExtension()), ['csv', 'txt'], true)) {
                $handle = fopen($file->getRealPath(), 'r');
                $header = fgetcsv($handle);
                $rows = $this->mapRows($header ?: [], $this->readCsv($handle));
                fclose($handle);
            } else {
                $spreadsheet = IOFactory::load($file->getRealPath());
                $sheet = $spreadsheet->getSheet(0);
                $lastColumn = $sheet->getHighestColumn();
                $lastRow = $sheet->getHighestRow();

                $rows = $this->mapRows(
                    $sheet->rangeToArray("A1:{$lastColumn}1")[0] ?? [],
                    $sheet->rangeToArray("A2:{$lastColumn}{$lastRow}")
                );
            }
        } catch (\Throwable $e) {
            $errors[] = 'Berkas tidak dapat dibaca: '.$e->getMessage();
        }

        // Header wajib dikenali — beri pesan yang memandu, bukan error teknis.
        if ($rows === [] && $errors === []) {
            $errors[] = 'Tidak ada baris data yang terbaca. Pastikan baris pertama berisi judul kolom '
                .'(Kode Standar, Kode EP, Elemen Penilaian) dan data dimulai dari baris kedua. '
                .'Gunakan tombol "Unduh Template" untuk memperoleh format yang benar.';
        }

        return ['rows' => $rows, 'errors' => $errors];
    }

    private function readCsv($handle): array
    {
        $out = [];
        while (($line = fgetcsv($handle)) !== false) {
            $out[] = $line;
        }

        return $out;
    }

    /**
     * Memetakan header ke nama kolom internal, dengan dukungan alias header
     * supaya berkas lama atau buatan manusia tetap terbaca.
     */
    private function mapRows(array $header, array $body): array
    {
        $normalized = array_map(
            fn ($h) => Str::of((string) $h)->lower()->replace([' ', '-', '.', '/'], '_')->replaceMatches('/_+/', '_')->trim('_')->toString(),
            $header
        );

        $index = [];
        foreach (self::HEADER_ALIASES as $column => $aliases) {
            $index[$column] = false;

            foreach ($aliases as $alias) {
                $position = array_search($alias, $normalized, true);
                if ($position !== false) {
                    $index[$column] = $position;
                    break;
                }
            }
        }

        // Tanpa kolom kunci, seluruh baris akan kosong — sinyal berkas salah format.
        $hasKeyColumns = $index['kode_standar'] !== false
            && $index['kode_ep'] !== false
            && $index['elemen_penilaian'] !== false;

        if (! $hasKeyColumns) {
            return [];
        }

        $rows = [];
        foreach ($body as $line) {
            if (! is_array($line) || $line === [null]) {
                continue;
            }

            $row = [];
            foreach (array_keys(self::COLUMNS) as $column) {
                $position = $index[$column];
                $row[$column] = $position === false ? '' : trim((string) ($line[$position] ?? ''));
            }

            if ($row['kode_standar'] === '' && $row['kode_ep'] === '' && $row['elemen_penilaian'] === '') {
                continue;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Validasi baris terhadap master yang sudah ada.
     *
     * @param  array<int, array<string, string>>  $rows
     * @return array{valid: array<int, array<string, string>>, invalid: array<int, array<string, string>>, duplicates: array<int, string>}
     */
    public function validate(array $rows, AuditStandard $standard, ?InstrumentVersion $version = null): array
    {
        $valid = [];
        $invalid = [];
        $duplicates = [];

        $seen = [];

        foreach ($rows as $number => $row) {
            $lineNumber = $number + 2; // +2 untuk header dan penomoran 1-based

            $row = array_merge([
                'kode_standar' => '',
                'kode_ep' => '',
                'elemen_penilaian' => '',
                'kelengkapan_bukti' => '',
                'panduan_penilaian' => '',
                'skor_maks' => '',
            ], $row);

            if ($row['kode_standar'] === '' || $row['kode_ep'] === '' || $row['elemen_penilaian'] === '') {
                $invalid[] = [
                    'baris' => (string) $lineNumber,
                    'pesan' => 'Kode standar, kode EP, dan elemen penilaian wajib diisi.',
                    'data' => $row,
                ];

                continue;
            }

            if ($row['skor_maks'] !== '' && ! is_numeric($row['skor_maks'])) {
                $invalid[] = [
                    'baris' => (string) $lineNumber,
                    'pesan' => 'Skor maksimal harus berupa angka (kolom Skor Maksimal).',
                    'data' => $row,
                ];

                continue;
            }

            $key = Str::lower($row['kode_standar']).'|'.Str::lower($row['kode_ep']);
            if (isset($seen[$key])) {
                $duplicates[] = "Baris {$lineNumber}: duplikat dalam berkas (kode {$row['kode_ep']} pada {$row['kode_standar']}).";

                continue;
            }
            $seen[$key] = true;

            // Deteksi duplikat terhadap data yang sudah tersimpan.
            if ($version) {
                $section = StandardSection::where('instrument_version_id', $version->id)
                    ->whereRaw('LOWER(code) = ?', [Str::lower($row['kode_standar'])])
                    ->first();

                if ($section && StandardCriterion::where('standard_section_id', $section->id)
                    ->whereRaw('LOWER(code) = ?', [Str::lower($row['kode_ep'])])
                    ->exists()) {
                    $duplicates[] = "Baris {$lineNumber}: {$row['kode_ep']} pada {$row['kode_standar']} sudah ada di instrumen ini.";

                    continue;
                }
            }

            $valid[] = $row;
        }

        return ['valid' => $valid, 'invalid' => $invalid, 'duplicates' => $duplicates];
    }

    /**
     * Menyimpan baris yang valid ke standar/instrumen.
     *
     * @param  array<int, array<string, string>>  $rows
     * @return array{seksi: int, ep: int}
     */
    public function store(array $rows, AuditStandard $standard, ?InstrumentVersion $version, User $user): array
    {
        $version ??= $this->ensureVersion($standard, $user);

        $sectionCount = 0;
        $criterionCount = 0;

        foreach ($rows as $row) {
            $section = StandardSection::firstOrCreate(
                ['instrument_version_id' => $version->id, 'code' => $row['kode_standar']],
                [
                    'title' => $row['kode_standar'],
                    'sort_order' => StandardSection::where('instrument_version_id', $version->id)->count() + 1,
                ]
            );
            $sectionCount += $section->wasRecentlyCreated ? 1 : 0;

            $criterion = StandardCriterion::firstOrCreate(
                ['standard_section_id' => $section->id, 'code' => $row['kode_ep']],
                [
                    'title' => $row['elemen_penilaian'],
                    'requirement_text' => $row['elemen_penilaian'],
                    'assessment_guidance' => $row['panduan_penilaian'] !== '' ? $row['panduan_penilaian'] : null,
                    'evidence_guidance' => $row['kelengkapan_bukti'] !== '' ? $row['kelengkapan_bukti'] : null,
                    'assessment_type' => 'compliance',
                    'max_score' => $row['skor_maks'] !== '' ? (int) $row['skor_maks'] : 10,
                    'sort_order' => StandardCriterion::where('standard_section_id', $section->id)->count() + 1,
                    'active' => true,
                ]
            );
            $criterionCount += $criterion->wasRecentlyCreated ? 1 : 0;
        }

        return ['seksi' => $sectionCount, 'ep' => $criterionCount];
    }

    private function ensureVersion(AuditStandard $standard, User $user): InstrumentVersion
    {
        $version = InstrumentVersion::where('audit_standard_id', $standard->id)
            ->whereIn('status', ['draft'])
            ->orderByDesc('created_at')
            ->first();

        if ($version) {
            return $version;
        }

        return InstrumentVersion::create([
            'audit_standard_id' => $standard->id,
            'version_label' => 'v'.(InstrumentVersion::where('audit_standard_id', $standard->id)->count() + 1),
            'status' => 'draft',
        ]);
    }
}
