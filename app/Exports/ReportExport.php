<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportExport implements FromCollection, WithColumnWidths, WithHeadings, WithMapping, WithStyles
{
    protected array $data;

    protected string $reportType;

    public function __construct(array $data, string $reportType)
    {
        $this->data = $data;
        $this->reportType = $reportType;
    }

    public function collection()
    {
        switch ($this->reportType) {
            case 'findings':
                return $this->data['findings'] ?? collect();
            case 'capa':
                return $this->data['capa_cases'] ?? collect();
            case 'execution':
                return $this->data['executions'] ?? collect();
            case 'program':
            default:
                return collect();
        }
    }

    public function headings(): array
    {
        switch ($this->reportType) {
            case 'findings':
                return [
                    'Kode Temuan', 'Judul', 'Klinik', 'Eksekusi', 'Tipe',
                    'Tingkat Risiko', 'Status', 'PIC', 'Tenggat', 'Dibuat pada',
                ];
            case 'capa':
                return [
                    'Kode CAPA', 'Judul', 'Klinik', 'PIC', 'Prioritas',
                    'Status', 'Target Penyelesaian', 'Dibuat pada',
                ];
            case 'execution':
                return [
                    'Kode Eksekusi', 'Judul', 'Klinik', 'Program', 'Versi Instrumen',
                    'Status', 'Mulai', 'Diselesaikan', 'Finalisasi', 'Dibuat pada',
                ];
            default:
                return ['Data', 'Keterangan'];
        }
    }

    public function map($row): array
    {
        switch ($this->reportType) {
            case 'findings':
                $result = [
                    $row->finding_code ?? '-',
                    $row->title ?? '-',
                    $row->clinic?->name ?? '-',
                    $row->execution?->execution_code ?? '-',
                    ucfirst(str_replace('_', ' ', $row->finding_type ?? '-')),
                    ucfirst($row->severity ?? '-'),
                    $row->status ?? '-',
                    $row->owner?->name ?? $row->assignee?->name ?? '-',
                    $row->due_at?->format('d M Y') ?? '-',
                    $row->created_at?->format('d M Y H:i') ?? '-',
                ];
                break;
            case 'capa':
                $result = [
                    $row->case_number ?? '-',
                    $row->problem_statement ?? '-',
                    $row->clinic?->name ?? '-',
                    $row->owner?->name ?? '-',
                    ucfirst($row->priority ?? '-'),
                    $row->status ?? '-',
                    $row->target_close_date?->format('d M Y') ?? '-',
                    $row->created_at?->format('d M Y H:i') ?? '-',
                ];
                break;
            case 'execution':
                $result = [
                    $row->execution_code ?? '-',
                    $row->title ?? '-',
                    $row->clinic?->name ?? '-',
                    $row->program?->name ?? '-',
                    $row->instrumentVersion?->version_label ?? '-',
                    $row->status ?? '-',
                    $row->started_at?->format('d M Y H:i') ?? '-',
                    $row->submitted_at?->format('d M Y H:i') ?? '-',
                    $row->finalized_at?->format('d M Y H:i') ?? '-',
                    $row->created_at?->format('d M Y H:i') ?? '-',
                ];
                break;
            default:
                $result = ['-', '-'];
                break;
        }

        return array_map([$this, 'sanitizeCell'], $result);
    }

    protected function sanitizeCell($value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }
        $trimmed = ltrim($value);
        if ($trimmed !== '' && in_array($trimmed[0], ['=', '+', '-', '@'], true)) {
            return "'".$value;
        }

        return $value;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 40,
            'C' => 30,
            'D' => 25,
            'E' => 20,
            'F' => 15,
            'G' => 15,
            'H' => 25,
            'I' => 20,
            'J' => 20,
        ];
    }
}
