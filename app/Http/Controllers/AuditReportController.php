<?php

namespace App\Http\Controllers;

use App\Models\AuditExecution;
use App\Models\AuditReport;
use App\Models\Clinic;
use App\Services\AuditReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditReportController extends Controller
{
    public function __construct(private AuditReportService $reportService) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $reports = $this->reportService->getReportsForUser($user);

        return view('audit.reports.index', compact('reports'));
    }

    public function create(Request $request): View
    {
        $orgId = $request->user()->organization_id;

        $executions = AuditExecution::where('organization_id', $orgId)
            ->with('clinic')
            ->orderByDesc('created_at')
            ->get();

        $clinics = Clinic::forUserClinics($request->user())
            ->auditable()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('audit.reports.create', compact('executions', 'clinics'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'report_type' => ['required', 'string', 'max:40', Rule::in(['program', 'findings', 'capa', 'execution', 'summary'])],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date'],
            'clinic_id' => ['nullable', 'uuid'],
            'audit_execution_id' => ['nullable', 'uuid'],
            'executive_summary' => ['nullable', 'string'],
        ]);

        $clinicIds = $request->user()->clinicScopeIds();
        if ($request->filled('clinic_id') && ! $clinicIds->contains($request->string('clinic_id'))) {
            abort(403, 'Klinik tidak dalam ruang lingkup akses Anda.');
        }

        $this->reportService->generateReport($request->all(), $request->user());

        return redirect()->route('audit.reports.index')->with('status', 'Laporan berhasil dibuat.');
    }

    public function show(Request $request, AuditReport $report): View
    {
        $this->authorize('view', $report);

        $report->load(['clinic', 'execution', 'generator']);

        return view('audit.reports.show', compact('report'));
    }

    public function downloadPdf(Request $request, AuditReport $report): StreamedResponse
    {
        $this->authorize('view', $report);

        abort_unless($request->user()->belongsToOrganization($report->organization_id), 403);

        if (! $report->file_path || ! Storage::disk('local')->exists($report->file_path)) {
            abort(404, 'File PDF laporan tidak tersedia.');
        }

        return Storage::disk('local')->download(
            $report->file_path,
            $report->report_number.'.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    public function downloadExcel(Request $request, AuditReport $report): StreamedResponse
    {
        $this->authorize('view', $report);

        abort_unless($request->user()->belongsToOrganization($report->organization_id), 403);

        $xlsxPath = $report->metadata['xlsx_path'] ?? null;

        if (! $xlsxPath || ! Storage::disk('local')->exists($xlsxPath)) {
            abort(404, 'File Excel laporan tidak tersedia.');
        }

        return Storage::disk('local')->download(
            $xlsxPath,
            $report->report_number.'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    public function destroy(Request $request, AuditReport $report): RedirectResponse
    {
        $this->authorize('delete', $report);

        $this->reportService->deleteReport($report, $request->user());

        return redirect()->route('audit.reports.index')->with('status', 'Laporan berhasil dihapus.');
    }
}
