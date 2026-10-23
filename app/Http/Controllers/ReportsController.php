<?php

namespace App\Http\Controllers;

use App\Models\AuditFinding;
use App\Models\Clinic;
use App\Services\DashboardService;
use App\Support\SpreadsheetCell;
use App\Support\UiLabel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportsController extends Controller
{
    public function __construct(private DashboardService $dashboard) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $orgId = $user->organization_id;
        $ranking = $this->dashboard->buildClinicRanking($orgId)->take(5);
        $data = $this->dashboard->executiveData($user);

        $clinics = Clinic::forOrganization($orgId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('reports.index', [
            'ranking_preview' => $ranking,
            'stats' => $data['stats'],
            'clinics' => $clinics,
        ]);
    }

    public function export(Request $request)
    {
        abort_unless($request->user()->can('audit.report.export') || $request->user()->can('audit.report.publish'), 403);

        $format = $request->get('format', 'pdf');

        $query = AuditFinding::forUserClinics($request->user())
            ->with(['clinic', 'schedule.program', 'execution', 'owner'])
            ->orderBy('finding_code');

        if ($request->get('date_from')) {
            $query->whereDate('identified_at', '>=', $request->get('date_from'));
        }
        if ($request->get('date_to')) {
            $query->whereDate('identified_at', '<=', $request->get('date_to'));
        }
        if ($request->get('clinic_id')) {
            $query->where('clinic_id', $request->get('clinic_id'));
        }
        if ($request->get('audit_type')) {
            $query->whereHas('execution', function ($q) use ($request) {
                $q->where('metadata->audit_type', $request->get('audit_type'));
            });
        }
        if ($request->get('finding_status')) {
            $query->where('status', $request->get('finding_status'));
        }

        $findings = $query->get();

        $reportData = [
            'title' => 'Laporan Audit Internal',
            'subtitle' => 'PPKP Provinsi DKI Jakarta',
            'period_from' => $request->get('date_from') ?? '—',
            'period_to' => $request->get('date_to') ?? '—',
            'generated_at' => now()->format('d M Y H:i'),
            'generated_by' => $request->user()->name,
            'total_findings' => $findings->count(),
            'findings' => $findings,
        ];

        if ($format === 'excel') {
            return $this->exportExcel($reportData);
        }

        return $this->exportPdf($reportData);
    }

    private function exportPdf(array $data)
    {
        // PDF export requires a PDF library (e.g., barryvdh/laravel-dompdf).
        // Jika tidak terpasang, kembalikan respons HTML yang siap dicetak ke PDF.
        $html = view('reports.export-pdf', $data)->render();

        if (class_exists('\Barryvdh\DomPDF\Facade\Pdf')) {
            $pdf = Pdf::loadHtml($html)
                ->setPaper('a4', 'portrait')
                ->setOptions(['defaultFont' => 'sans-serif']);

            return $pdf->download('laporan-audit-internal-'.now()->format('Ymd_His').'.pdf');
        }

        return response($html)
            ->header('Content-Type', 'text/html')
            ->header('Content-Disposition', 'attachment; filename="laporan-audit-internal.html"');
    }

    private function exportExcel(array $data)
    {
        // Excel export requires maatwebsite/excel or PhpSpreadsheet.
        // Jika tidak terpasang, kembalikan CSV yang dapat dibuka Excel.
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="laporan-audit-internal-'.now()->format('Ymd_His').'.csv"',
        ];

        $callback = function () use ($data) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($handle, ['Kode Temuan', 'Klinik', 'Program', 'Tipe Temuan', 'Tingkat Risiko', 'Status', 'PIC', 'Tenggat', 'Ditemukan Pada', 'Judul', 'Kondisi', 'Kriteria', 'Rekomendasi'], ',', '"', '\\');

            foreach ($data['findings'] as $finding) {
                fputcsv($handle, array_map([SpreadsheetCell::class, 'sanitize'], [
                    $finding->finding_code,
                    $finding->clinic?->code.' — '.$finding->clinic?->name,
                    $finding->schedule?->program?->program_code ?? '—',
                    $finding->finding_type ?? '—',
                    UiLabel::findingSeverity($finding->severity),
                    UiLabel::findingStatus($finding->status),
                    $finding->owner?->name ?? $finding->assignee?->name ?? '—',
                    $finding->due_at?->format('d M Y') ?? '—',
                    $finding->identified_at?->format('d M Y') ?? '—',
                    $finding->title,
                    $finding->condition ?? '—',
                    $finding->requirement ?? '—',
                    $finding->recommendation ?? '—',
                ]), ',', '"', '\\');
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
