<?php

namespace App\Services;

use App\Exports\ReportExport;
use App\Models\ApprovalEvent;
use App\Models\AuditExecution;
use App\Models\AuditFinding;
use App\Models\AuditProgram;
use App\Models\AuditReport;
use App\Models\CapaCase;
use App\Models\Clinic;
use App\Models\User;
use App\Support\AuditWorkScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class AuditReportService
{
    public function generateReport(array $data, User $user): AuditReport
    {
        $orgId = $user->organization_id;
        $reportType = $data['report_type'];
        $periodStart = $data['period_start'] ?? null;
        $periodEnd = $data['period_end'] ?? null;
        $clinicId = $data['clinic_id'] ?? null;
        $executionId = $data['audit_execution_id'] ?? null;

        $reportData = $this->collectReportData($user, $data);
        $reportNumber = $this->generateReportNumber($orgId, $reportType);

        $clinicName = $clinicId
            ? Clinic::query()->where('id', $clinicId)->value('name')
            : 'Ruang lingkup pengguna';

        $reportData = array_merge($reportData, [
            'document_title' => 'SIMMAK Audit Internal Klinik',
            'report_number' => $reportNumber,
            'report_title' => $data['title'] ?? null,
            'document_version' => '1',
            'document_status' => 'generated',
            'clinic_name' => $clinicName,
            'executive_summary' => $data['executive_summary'] ?? null,
            'generated_by' => $user->name,
            'generated_at' => now()->format('d M Y H:i'),
        ]);

        $fileName = strtolower(Str::slug($data['title'] ?? $reportType)).'_'.date('YmdHis');
        $pdfPath = 'reports/'.$orgId.'/'.$fileName.'.pdf';
        $xlsxPath = 'reports/'.$orgId.'/'.$fileName.'.xlsx';

        $pdf = Pdf::loadView('audit.reports.pdf', $reportData);
        Storage::disk('local')->put($pdfPath, $pdf->output());

        // Generate Excel
        Excel::store(new ReportExport($reportData, $reportType), $xlsxPath, 'local');

        // Hitung ukuran dan checksum
        $pdfSize = Storage::disk('local')->size($pdfPath);
        $xlsxSize = Storage::disk('local')->size($xlsxPath);
        $pdfChecksum = hash_file('sha256', Storage::disk('local')->path($pdfPath));
        $xlsxChecksum = hash_file('sha256', Storage::disk('local')->path($xlsxPath));

        // Simpan record report (pilih PDF sebagai primary)
        $report = AuditReport::create([
            'organization_id' => $orgId,
            'audit_execution_id' => $executionId,
            'clinic_id' => $clinicId,
            'report_number' => $reportNumber,
            'title' => $data['title'] ?? ucfirst($reportType).' Report',
            'report_type' => $reportType,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'executive_summary' => $data['executive_summary'] ?? null,
            'findings_summary' => $reportData['findings_summary'] ?? [],
            'capa_summary' => $reportData['capa_summary'] ?? [],
            'file_path' => $pdfPath,
            'file_type' => 'pdf',
            'file_size' => $pdfSize,
            'checksum' => $pdfChecksum,
            'status' => 'generated',
            'generated_by' => $user->id,
            'generated_at' => now(),
            'metadata' => [
                'pdf_path' => $pdfPath,
                'pdf_size' => $pdfSize,
                'pdf_checksum' => $pdfChecksum,
                'xlsx_path' => $xlsxPath,
                'xlsx_size' => $xlsxSize,
                'xlsx_checksum' => $xlsxChecksum,
                'generated_by_name' => $user->name,
            ],
        ]);

        // Audit trail
        (new AuditTrailService)->log(
            'audit.report.generated',
            $report,
            null,
            [
                'report_number' => $reportNumber,
                'report_type' => $reportType,
                'file_type' => 'pdf',
                'file_size' => $pdfSize,
            ],
            ['actor' => $user->name]
        );

        return $report;
    }

    public function collectReportData(User $user, array $filters): array
    {
        $periodStart = $filters['period_start'] ?? null;
        $periodEnd = $filters['period_end'] ?? null;
        $clinicId = $filters['clinic_id'] ?? null;
        $executionId = $filters['audit_execution_id'] ?? null;

        $queryFindings = AuditWorkScope::findings(AuditFinding::query(), $user);
        $queryCapa = AuditWorkScope::capaCases(CapaCase::query(), $user);
        $queryExecutions = AuditWorkScope::executions(AuditExecution::query(), $user);
        $queryPrograms = AuditProgram::forOrganization($user->organization_id);

        if ($periodStart) {
            $queryFindings->whereDate('identified_at', '>=', $periodStart);
            $queryCapa->whereDate('created_at', '>=', $periodStart);
            $queryExecutions->whereDate('created_at', '>=', $periodStart);
        }
        if ($periodEnd) {
            $queryFindings->whereDate('identified_at', '<=', $periodEnd);
            $queryCapa->whereDate('created_at', '<=', $periodEnd);
            $queryExecutions->whereDate('created_at', '<=', $periodEnd);
        }
        if ($clinicId) {
            $queryFindings->where('clinic_id', $clinicId);
            $queryCapa->where('clinic_id', $clinicId);
            $queryExecutions->where('clinic_id', $clinicId);
        }
        if ($executionId) {
            $queryFindings->where('audit_execution_id', $executionId);
            $queryCapa->whereHas('sourceFinding', fn ($q) => $q->where('audit_execution_id', $executionId));
            $queryExecutions->where('id', $executionId);
        }
        if (! empty($filters['finding_status'])) {
            $queryFindings->where('status', $filters['finding_status']);
        }
        if (! empty($filters['severity'])) {
            $queryFindings->where('severity', $filters['severity']);
        }
        if (! empty($filters['capa_status'])) {
            $queryCapa->where('status', $filters['capa_status']);
        }
        if (! empty($filters['audit_type'])) {
            $queryPrograms->where('audit_type', $filters['audit_type']);
            $queryExecutions->whereHas('program', fn ($q) => $q->where('audit_type', $filters['audit_type']));
            $queryFindings->whereHas('execution.program', fn ($q) => $q->where('audit_type', $filters['audit_type']));
        }
        if (! empty($filters['auditor_id'])) {
            $auditorId = $filters['auditor_id'];
            $queryExecutions->whereHas('assignment.teamMembers', fn ($q) => $q->where('user_id', $auditorId)->where('active', true));
            $queryFindings->whereHas('execution.assignment.teamMembers', fn ($q) => $q->where('user_id', $auditorId)->where('active', true));
        }
        if (! empty($filters['standard_criterion_id'])) {
            $queryFindings->where('standard_criterion_id', $filters['standard_criterion_id']);
        }

        $findings = $queryFindings->with(['clinic', 'execution.program', 'owner', 'assignee', 'capaCase.verifications'])->get();
        $capaCases = $queryCapa->with(['clinic', 'owner', 'verifications.verifier', 'sourceFinding'])->get();
        $executions = $queryExecutions->with(['clinic', 'program', 'assignment'])->get();
        $programs = $queryPrograms->orderBy('name')->get();

        $programIds = $programs->pluck('id');
        $approvals = ApprovalEvent::query()
            ->where('organization_id', $user->organization_id)
            ->where('entity_type', 'audit_program')
            ->whereIn('entity_id', $programIds)
            ->with('actor')
            ->orderByDesc('acted_at')
            ->limit(50)
            ->get();

        $findingsSummary = [
            'total' => $findings->count(),
            'by_status' => $findings->groupBy('status')->map(fn ($g) => $g->count())->all(),
            'by_severity' => $findings->groupBy('severity')->map(fn ($g) => $g->count())->all(),
            'by_clinic' => $findings->groupBy('clinic.name')->map(fn ($g) => $g->count())->all(),
        ];

        $capaSummary = [
            'total' => $capaCases->count(),
            'by_status' => $capaCases->groupBy('status')->map(fn ($g) => $g->count())->all(),
            'by_priority' => $capaCases->groupBy('priority')->map(fn ($g) => $g->count())->all(),
        ];

        $planVsActual = $programs->map(function (AuditProgram $program) use ($executions) {
            $related = $executions->where('audit_program_id', $program->id);

            return [
                'code' => $program->program_code,
                'name' => $program->name,
                'status' => $program->status,
                'planned' => $related->count(),
                'finalized' => $related->where('status', 'finalized')->count(),
            ];
        });

        return [
            'report_type' => $filters['report_type'] ?? 'summary',
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'clinic_id' => $clinicId,
            'execution_id' => $executionId,
            'findings' => $findings,
            'capa_cases' => $capaCases,
            'executions' => $executions,
            'programs' => $programs,
            'plan_vs_actual' => $planVsActual,
            'approvals' => $approvals,
            'findings_summary' => $findingsSummary,
            'capa_summary' => $capaSummary,
            'generated_at' => now()->format('d M Y H:i'),
            'generated_by' => $user->name,
        ];
    }

    protected function generateReportNumber(string $orgId, string $reportType): string
    {
        $year = now()->format('Y');
        $prefix = strtoupper($reportType).'-'.$year.'-';
        $last = AuditReport::where('organization_id', $orgId)
            ->where('report_number', 'like', $prefix.'%')
            ->orderByDesc('report_number')
            ->value('report_number');

        $seq = 1;
        if ($last) {
            $parts = explode('-', $last);
            $seq = (int) end($parts) + 1;
        }

        return $prefix.str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function getReportsForOrganization(?string $organizationId)
    {
        return AuditReport::forOrganization($organizationId)
            ->with(['clinic', 'execution', 'generator'])
            ->orderByDesc('generated_at')
            ->paginate(15);
    }

    public function getReportsForUser(User $user)
    {
        return AuditReport::forUserClinics($user)
            ->with(['clinic', 'execution', 'generator'])
            ->orderByDesc('generated_at')
            ->paginate(15);
    }

    public function deleteReport(AuditReport $report, User $user): void
    {
        if (! $user->belongsToOrganization($report->organization_id)) {
            throw new AuthorizationException('Tidak berwenang menghapus laporan ini.');
        }

        // Hapus file fisik
        if ($report->file_path && Storage::disk('local')->exists($report->file_path)) {
            Storage::disk('local')->delete($report->file_path);
        }

        // Hapus file Excel jika ada di metadata
        if (isset($report->metadata['xlsx_path']) && Storage::disk('local')->exists($report->metadata['xlsx_path'])) {
            Storage::disk('local')->delete($report->metadata['xlsx_path']);
        }

        $report->delete();
    }
}
