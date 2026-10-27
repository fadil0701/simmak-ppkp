<?php

use App\Http\Controllers\AccreditationController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AuditAssignmentController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuditEvidenceController;
use App\Http\Controllers\AuditExecutionController;
use App\Http\Controllers\AuditFindingController;
use App\Http\Controllers\AuditProgramController;
use App\Http\Controllers\AuditReportController;
use App\Http\Controllers\AuditStandardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CapaController;
use App\Http\Controllers\ClinicController;
use App\Http\Controllers\ClinicDashboardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DmsController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\FiscalYearController;
use App\Http\Controllers\InstrumentVersionController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QualityController;
use App\Http\Controllers\RegulatorySourceController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\RiskController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::get('/verify/document/{token}', [DmsController::class, 'verify'])->name('dms.verify');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    // 10 percobaan/menit: cukup untuk salah ketik, tetap membatasi brute force.
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
});

Route::middleware(['auth', 'clinic.context'])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/dashboard/export-ranking', [DashboardController::class, 'exportRanking'])->name('dashboard.export-ranking');
    Route::get('/audit/dashboard', [DashboardController::class, 'redirectAuditDashboard'])->name('audit.dashboard');

    Route::middleware('permission:dashboard.executive')->group(function () {
        Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
    });

    Route::middleware('permission:dashboard.clinic')->group(function () {
        Route::get('/clinic/dashboard', ClinicDashboardController::class)->name('clinic.dashboard');
    });

    Route::middleware('permission:users.view')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    });

    Route::middleware('permission:users.manage')->group(function () {
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
    });

    Route::middleware('permission:settings.view')->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingsController::class, 'update'])->middleware('permission:settings.manage')->name('settings.update');
    });

    Route::middleware('permission:settings.manage')->group(function () {
        Route::get('/settings/fiscal-years', [FiscalYearController::class, 'index'])->name('fiscal-years.index');
        Route::post('/settings/fiscal-years', [FiscalYearController::class, 'store'])->name('fiscal-years.store');
        Route::delete('/settings/fiscal-years/{fiscalYear}', [FiscalYearController::class, 'destroy'])->name('fiscal-years.destroy');
    });

    Route::middleware('permission:clinics.view')->group(function () {
        Route::get('/clinics', [ClinicController::class, 'index'])->name('clinics.index');
    });

    Route::middleware('permission:employees.view')->group(function () {
        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
    });
    Route::middleware('permission:positions.view')->group(function () {
        Route::get('/positions', [PositionController::class, 'index'])->name('positions.index');
    });
    Route::middleware('permission:positions.manage')->group(function () {
        Route::get('/positions/create', [PositionController::class, 'create'])->name('positions.create');
        Route::post('/positions', [PositionController::class, 'store'])->name('positions.store');
        Route::get('/positions/{position}/edit', [PositionController::class, 'edit'])->name('positions.edit');
        Route::put('/positions/{position}', [PositionController::class, 'update'])->name('positions.update');
        Route::delete('/positions/{position}', [PositionController::class, 'destroy'])->name('positions.destroy');
    });
    Route::middleware('permission:employees.manage')->group(function () {
        Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
    });
    Route::middleware('permission:clinics.manage')->group(function () {
        Route::get('/clinics/create', [ClinicController::class, 'create'])->name('clinics.create');
        Route::post('/clinics', [ClinicController::class, 'store'])->name('clinics.store');
        Route::get('/clinics/{clinic}/edit', [ClinicController::class, 'edit'])->name('clinics.edit');
        Route::put('/clinics/{clinic}', [ClinicController::class, 'update'])->name('clinics.update');
        Route::delete('/clinics/{clinic}', [ClinicController::class, 'destroy'])->name('clinics.destroy');
    });

    Route::post('/clinics/switch-all', [ClinicController::class, 'switchAll'])->name('clinics.switch-all');
    Route::post('/clinics/{clinic}/switch', [ClinicController::class, 'switch'])->name('clinics.switch');

    Route::middleware('permission:dms.manage')->group(function () {
        Route::get('/dms/create', [DmsController::class, 'create'])->name('dms.create');
        Route::post('/dms', [DmsController::class, 'store'])->name('dms.store');
        Route::post('/dms/{document}/publish', [DmsController::class, 'publish'])->name('dms.publish');
        Route::post('/dms/{document}/submit-review', [DmsController::class, 'submitReview'])->name('dms.submit-review');
        Route::post('/dms/{document}/approve-review', [DmsController::class, 'approveReview'])->name('dms.approve-review');
    });
    Route::middleware('permission:dms.view')->group(function () {
        Route::get('/dms', [DmsController::class, 'index'])->name('dms.index');
        Route::get('/dms/{document}', [DmsController::class, 'show'])->name('dms.show');
        Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download'])->name('attachments.download');
    });

    Route::middleware('permission:audit.finding.create')->group(function () {
        Route::get('/findings/create', [AuditFindingController::class, 'create'])->name('findings.create');
        Route::post('/findings', [AuditFindingController::class, 'store'])->name('findings.store');
    });

    Route::middleware('permission:audit.view')->group(function () {
        Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');
    });

    // Temuan Audit adalah menu tersendiri (07_TEMUAN_AUDIT.md §2): akses daftar,
    // ekspor, dan detail memakai izin audit.finding.view — bukan audit.view.
    Route::middleware('permission:audit.finding.view')->group(function () {
        Route::get('/findings', [AuditFindingController::class, 'index'])->name('findings.index');
        Route::get('/findings/export', [AuditFindingController::class, 'export'])->name('findings.export');
        Route::get('/findings/{finding}', [AuditFindingController::class, 'show'])->name('findings.show');
    });

    Route::middleware('permission:audit.finding.update')->group(function () {
        Route::get('/findings/{finding}/edit', [AuditFindingController::class, 'edit'])->name('findings.edit');
        Route::put('/findings/{finding}', [AuditFindingController::class, 'update'])->name('findings.update');
        Route::delete('/findings/{finding}', [AuditFindingController::class, 'destroy'])->name('findings.destroy');
    });

    Route::middleware('permission:audit.finding.review')->group(function () {
        Route::post('/findings/{finding}/review', [AuditFindingController::class, 'review'])->name('findings.review');
    });

    Route::middleware('permission:audit.finding.close')->group(function () {
        Route::post('/findings/{finding}/close', [AuditFindingController::class, 'close'])->name('findings.close');
    });

    Route::middleware('permission:audit.finding.reopen')->group(function () {
        Route::post('/findings/{finding}/reopen', [AuditFindingController::class, 'reopen'])->name('findings.reopen');
    });

    Route::middleware('permission:audit.finding.escalate')->group(function () {
        Route::post('/findings/{finding}/escalate', [AuditFindingController::class, 'escalate'])->name('findings.escalate');
    });

    Route::middleware('permission:audit.report.view')->group(function () {
        Route::get('/audit/reports', [AuditReportController::class, 'index'])->name('audit.reports.index');
        Route::get('/audit/reports/create', [AuditReportController::class, 'create'])->name('audit.reports.create');
        Route::post('/audit/reports', [AuditReportController::class, 'store'])->name('audit.reports.store');
        Route::get('/audit/reports/{report}', [AuditReportController::class, 'show'])->name('audit.reports.show');
        Route::get('/audit/reports/{report}/download-pdf', [AuditReportController::class, 'downloadPdf'])->name('audit.reports.download-pdf');
        Route::get('/audit/reports/{report}/download-excel', [AuditReportController::class, 'downloadExcel'])->name('audit.reports.download-excel');
        Route::delete('/audit/reports/{report}', [AuditReportController::class, 'destroy'])->name('audit.reports.destroy');
    });
    Route::middleware('permission:audit.manage')->group(function () {
        Route::get('/audit/{schedule}/checklist', [AuditController::class, 'checklist'])->name('audit.checklist');
        Route::post('/findings/{finding}/capa', [AuditFindingController::class, 'createCapa'])->name('findings.create-capa');
    });

    Route::middleware('permission:audit.report.export|audit.report.publish')->group(function () {
        Route::get('/reports/export', [ReportsController::class, 'export'])->name('audit.reports.export');
    });

    Route::middleware('permission:audit.standard.view')->group(function () {
        Route::get('/audit/regulatory-sources', [RegulatorySourceController::class, 'index'])->name('audit.regulatory.index');
    });

    Route::middleware('permission:audit.standard.manage')->group(function () {
        Route::get('/audit/regulatory-sources/create', [RegulatorySourceController::class, 'create'])->name('audit.regulatory.create');
        Route::post('/audit/regulatory-sources', [RegulatorySourceController::class, 'store'])->name('audit.regulatory.store');
        Route::get('/audit/regulatory-sources/{source}/edit', [RegulatorySourceController::class, 'edit'])->name('audit.regulatory.edit');
        Route::put('/audit/regulatory-sources/{source}', [RegulatorySourceController::class, 'update'])->name('audit.regulatory.update');
        Route::delete('/audit/regulatory-sources/{source}', [RegulatorySourceController::class, 'destroy'])->name('audit.regulatory.destroy');
    });

    Route::middleware('permission:audit.standard.manage|audit.standard.review')->group(function () {
        Route::post('/audit/regulatory-sources/{source}/verify', [RegulatorySourceController::class, 'verify'])->name('audit.regulatory.verify');
    });

    Route::middleware('permission:audit.standard.view')->group(function () {
        Route::get('/audit/standards', [AuditStandardController::class, 'index'])->name('audit.standards.index');
        Route::get('/audit/standards/create', [AuditStandardController::class, 'create'])->name('audit.standards.create');
        Route::post('/audit/standards', [AuditStandardController::class, 'store'])->name('audit.standards.store');
        Route::get('/audit/standards/{standard}', [AuditStandardController::class, 'show'])->name('audit.standards.show');
        Route::get('/audit/standards/{standard}/edit', [AuditStandardController::class, 'edit'])->name('audit.standards.edit');
        Route::put('/audit/standards/{standard}', [AuditStandardController::class, 'update'])->name('audit.standards.update');
        Route::delete('/audit/standards/{standard}', [AuditStandardController::class, 'destroy'])->name('audit.standards.destroy');
    });

    Route::middleware('permission:audit.standard.manage')->group(function () {
        Route::get('/audit/standards/{standard}/versions', [InstrumentVersionController::class, 'versions'])->name('audit.standards.versions');
        Route::get('/audit/standards/{standard}/versions/create', [InstrumentVersionController::class, 'create'])->name('audit.standards.versions.create');
        Route::post('/audit/standards/{standard}/versions', [InstrumentVersionController::class, 'store'])->name('audit.standards.versions.store');
        Route::post('/audit/versions/{version}/submit-review', [InstrumentVersionController::class, 'submitReview'])->name('audit.versions.submit-review');
        Route::post('/audit/versions/{version}/approve', [InstrumentVersionController::class, 'approve'])->name('audit.versions.approve');
        Route::post('/audit/versions/{version}/activate', [InstrumentVersionController::class, 'activate'])->name('audit.versions.activate');
        Route::post('/audit/versions/{version}/sections', [AuditStandardController::class, 'storeSection'])->name('audit.versions.sections.store');
        Route::post('/audit/sections/{section}/criteria', [AuditStandardController::class, 'storeCriterion'])->name('audit.sections.criteria.store');
        Route::get('/audit/standards/{standard}/import', [AuditStandardController::class, 'importForm'])->name('audit.standards.import');
        Route::post('/audit/standards/{standard}/import/preview', [AuditStandardController::class, 'previewImport'])->name('audit.standards.import.preview');
        Route::post('/audit/standards/{standard}/import/store', [AuditStandardController::class, 'storeImport'])->name('audit.standards.import.store');
    });

    Route::middleware('permission:audit.program.view')->group(function () {
        Route::get('/audit/programs', [AuditProgramController::class, 'index'])->name('audit.programs.index');
        Route::get('/audit/programs/create', [AuditProgramController::class, 'create'])->name('audit.programs.create');
        Route::post('/audit/programs', [AuditProgramController::class, 'store'])->name('audit.programs.store');
        Route::get('/audit/programs/{program}', [AuditProgramController::class, 'show'])->name('audit.programs.show');
        Route::get('/audit/programs/{program}/edit', [AuditProgramController::class, 'edit'])->name('audit.programs.edit');
        Route::put('/audit/programs/{program}', [AuditProgramController::class, 'update'])->name('audit.programs.update');
        Route::delete('/audit/programs/{program}', [AuditProgramController::class, 'destroy'])->name('audit.programs.destroy');
    });

    Route::middleware('permission:audit.program.submit')->group(function () {
        Route::post('/audit/programs/{program}/submit', [AuditProgramController::class, 'submit'])->name('audit.programs.submit');
    });

    Route::middleware('permission:audit.program.approve')->group(function () {
        Route::post('/audit/programs/{program}/approve', [AuditProgramController::class, 'approve'])->name('audit.programs.approve');
        Route::post('/audit/programs/{program}/return', [AuditProgramController::class, 'returnRevision'])->name('audit.programs.return');
    });

    Route::middleware('permission:audit.assignment.view')->group(function () {
        Route::get('/audit/assignments', [AuditAssignmentController::class, 'index'])->name('audit.assignments.index');
        Route::get('/audit/assignments/create', [AuditAssignmentController::class, 'create'])->name('audit.assignments.create');
        Route::post('/audit/assignments', [AuditAssignmentController::class, 'store'])->name('audit.assignments.store');
        Route::get('/audit/assignments/{assignment}', [AuditAssignmentController::class, 'show'])->name('audit.assignments.show');
        Route::get('/audit/assignments/{assignment}/edit', [AuditAssignmentController::class, 'edit'])->name('audit.assignments.edit');
        Route::put('/audit/assignments/{assignment}', [AuditAssignmentController::class, 'update'])->name('audit.assignments.update');
        Route::delete('/audit/assignments/{assignment}', [AuditAssignmentController::class, 'destroy'])->name('audit.assignments.destroy');
    });

    Route::middleware('permission:audit.assignment.manage')->group(function () {
        Route::post('/audit/assignments/{assignment}/team', [AuditAssignmentController::class, 'addTeamMember'])->name('audit.assignments.team');
        Route::post('/audit/team-members/{member}/conflict', [AuditAssignmentController::class, 'declareConflict'])->name('audit.team-members.conflict');
        Route::post('/audit/assignments/{assignment}/submit', [AuditAssignmentController::class, 'submit'])->name('audit.assignments.submit');
        Route::post('/audit/assignments/{assignment}/activate', [AuditAssignmentController::class, 'activate'])->name('audit.assignments.activate');
    });

    Route::middleware('permission:audit.execution.view')->group(function () {
        Route::get('/audit/executions', [AuditExecutionController::class, 'index'])->name('audit.executions.index');
        Route::get('/audit/executions/{execution}/checklist', [AuditExecutionController::class, 'checklist'])->name('audit.executions.checklist');
    });

    Route::middleware('permission:audit.execution.execute')->group(function () {
        Route::post('/audit/assignments/{assignment}/executions', [AuditExecutionController::class, 'store'])->name('audit.executions.store');
        Route::post('/audit/executions', [AuditExecutionController::class, 'storeFromSelection'])->name('audit.executions.store-selection');
        Route::post('/audit/executions/{execution}/start', [AuditExecutionController::class, 'start'])->name('audit.executions.start');
        Route::post('/audit/executions/{execution}/submit', [AuditExecutionController::class, 'submit'])->name('audit.executions.submit');
        Route::post('/audit/executions/{execution}/finalize', [AuditExecutionController::class, 'finalize'])->name('audit.executions.finalize');
        Route::post('/audit/executions/{execution}/response', [AuditExecutionController::class, 'saveResponse'])->name('audit.executions.response');
        Route::post('/audit/executions/{execution}/table', [AuditExecutionController::class, 'saveTable'])->name('audit.executions.table');
    });

    Route::middleware('permission:audit.evidence.view')->group(function () {
        Route::get('/audit/evidence', [AuditEvidenceController::class, 'index'])->name('audit.evidence.index');
        Route::get('/audit/evidence/create', [AuditEvidenceController::class, 'create'])->name('audit.evidence.create');
        Route::post('/audit/evidence', [AuditEvidenceController::class, 'store'])->name('audit.evidence.store');
        Route::get('/audit/evidence/{evidence}', [AuditEvidenceController::class, 'show'])->name('audit.evidence.show');
        Route::get('/audit/evidence/{evidence}/download', [AuditEvidenceController::class, 'download'])->name('audit.evidence.download');
        Route::delete('/audit/evidence/{evidence}', [AuditEvidenceController::class, 'destroy'])->name('audit.evidence.destroy');
    });

    Route::middleware('permission:capa.view')->group(function () {
        Route::get('/capa', [CapaController::class, 'index'])->name('capa.index');
        Route::get('/capa/export', [CapaController::class, 'export'])->name('capa.export');
        Route::get('/capa/{capa}', [CapaController::class, 'show'])->name('capa.show');
    });
    Route::middleware('permission:audit.action.update|capa.manage')->group(function () {
        Route::post('/capa/{capa}/rca', [CapaController::class, 'storeRca'])->name('capa.store-rca');
        Route::post('/capa/{capa}/actions', [CapaController::class, 'storeAction'])->name('capa.store-action');
        Route::post('/capa/actions/{action}/evidence', [CapaController::class, 'submitEvidence'])->name('capa.submit-evidence');
    });
    Route::middleware('permission:audit.capa.verify')->group(function () {
        Route::post('/capa/{capa}/verification', [CapaController::class, 'storeVerification'])->name('capa.store-verification');
    });

    Route::middleware('permission:risk.manage')->group(function () {
        Route::get('/risk/create', [RiskController::class, 'create'])->name('risk.create');
        Route::post('/risk', [RiskController::class, 'store'])->name('risk.store');
        Route::post('/risk/{risk}/mitigation', [RiskController::class, 'storeMitigation'])->name('risk.store-mitigation');
    });
    Route::middleware('permission:risk.view')->group(function () {
        Route::get('/risk', [RiskController::class, 'index'])->name('risk.index');
        Route::get('/risk/{risk}', [RiskController::class, 'show'])->name('risk.show');
    });

    Route::middleware('permission:quality.view')->group(function () {
        Route::get('/quality', [QualityController::class, 'index'])->name('quality.index');
    });

    Route::middleware('permission:accreditation.view')->group(function () {
        Route::get('/accreditation', [AccreditationController::class, 'index'])->name('accreditation.index');
        Route::get('/accreditation/gaps', [AccreditationController::class, 'gaps'])->name('accreditation.gaps');
        Route::get('/accreditation/self-assessment', [AccreditationController::class, 'selfAssessment'])->name('accreditation.self-assessment');
        Route::get('/accreditation/mock-survey', [AccreditationController::class, 'mockSurvey'])->name('accreditation.mock-survey');
        Route::get('/accreditation/evidence', [AccreditationController::class, 'evidenceIndex'])->name('accreditation.evidence');
    });
    Route::middleware('permission:accreditation.manage')->group(function () {
        Route::post('/accreditation/evidence/{evidence}/link', [AccreditationController::class, 'linkEvidence'])->name('accreditation.link-evidence');
        Route::get('/accreditation/standards', [AccreditationController::class, 'standardsIndex'])->name('accreditation.standards');
    });
});
