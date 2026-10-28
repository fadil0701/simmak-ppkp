<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Template impor Elemen Penilaian (EP).
 *
 * Berkas ini sengaja berisi contoh baris yang sudah benar supaya pengguna
 * bisa langsung menimpa isinya tanpa menebak format kolom.
 */
class InstrumentImportTemplate implements FromArray, WithColumnWidths, WithHeadings, WithStyles, WithTitle
{
    public function title(): string
    {
        return 'Impor EP';
    }

    public function headings(): array
    {
        return [
            'Kode Standar',
            'Kode EP',
            'Elemen Penilaian',
            'Kelengkapan Bukti',
            'Panduan Penilaian',
            'Skor Maksimal',
        ];
    }

    public function array(): array
    {
        return [
            [
                'STANDAR 1.1',
                'EP 1',
                'Contoh: Terdapat kebijakan tertulis tentang pelayanan klinik yang disahkan kepala klinik.',
                "Kebijakan pelayanan klinik yang disahkan\nBukti sosialisasi kepada staf",
                'Nilai sesuai bila kebijakan ada dan disahkan. Nilai sebagian bila ada tetapi belum disahkan.',
                10,
            ],
            [
                'STANDAR 1.1',
                'EP 2',
                'Contoh: Kebijakan tersebut telah disosialisasikan kepada seluruh staf klinik.',
                "Daftar hadir sosialisasi\nNotulen rapat",
                '',
                10,
            ],
            [
                'STANDAR 2.1',
                'EP 1',
                'Contoh: Terdapat program peningkatan mutu yang dilaksanakan setiap tahun.',
                "Dokumen program mutu tahunan\nLaporan evaluasi program",
                '',
                5,
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18,
            'B' => 12,
            'C' => 60,
            'D' => 45,
            'E' => 45,
            'F' => 14,
        ];
    }

    public function styles(Worksheet $sheet): ?array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1E3A5F'],
                ],
            ],
        ];
    }
}
