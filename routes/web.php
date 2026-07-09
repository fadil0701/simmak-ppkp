<?php

use App\Http\Controllers\AccreditationController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuditFindingController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CapaController;
use App\Http\Controllers\ClinicDashboardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DmsController;
use App\Http\Controllers\QualityController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\RiskController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::get('/verify/document/{token}', [DmsController::class, 'verify'])->name('dms.verify');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware(['auth', 'clinic.context'])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::middleware('permission:dashboard.executive')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/dashboard/export-ranking', [DashboardController::class, 'exportRanking'])->name('dashboard.export-ranking');
        Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
    });

    Route::middleware('permission:dashboard.clinic')->group(function () {
        Route::get('/clinic/dashboard', ClinicDashboardController::class)->name('clinic.dashboard');
    });

    Route::middleware('permission:users.view')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->middleware('permission:users.manage')->name('users.store');
    });

    Route::middleware('permission:settings.view')->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingsController::class, 'update'])->middleware('permission:settings.manage')->name('settings.update');
    });

    Route::middleware('permission:dms.view')->group(function () {
        Route::get('/dms', [DmsController::class, 'index'])->name('dms.index');
        Route::get('/dms/{document}', [DmsController::class, 'show'])->name('dms.show');
        Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download'])->name('attachments.download');
    });
    Route::middleware('permission:dms.manage')->group(function () {
        Route::get('/dms/create', [DmsController::class, 'create'])->name('dms.create');
        Route::post('/dms', [DmsController::class, 'store'])->name('dms.store');
        Route::post('/dms/{document}/publish', [DmsController::class, 'publish'])->name('dms.publish');
        Route::post('/dms/{document}/submit-review', [DmsController::class, 'submitReview'])->name('dms.submit-review');
        Route::post('/dms/{document}/approve-review', [DmsController::class, 'approveReview'])->name('dms.approve-review');
    });

    Route::middleware('permission:audit.view')->group(function () {
        Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');
        Route::get('/findings', [AuditFindingController::class, 'index'])->name('findings.index');
        Route::get('/findings/{finding}', [AuditFindingController::class, 'show'])->name('findings.show');
    });
    Route::middleware('permission:audit.manage')->group(function () {
        Route::get('/audit/reports', [AuditController::class, 'reports'])->name('audit.reports');
        Route::get('/audit/{schedule}/checklist', [AuditController::class, 'checklist'])->name('audit.checklist');
        Route::post('/findings/{finding}/capa', [AuditFindingController::class, 'createCapa'])->name('findings.create-capa');
    });

    Route::middleware('permission:capa.view')->group(function () {
        Route::get('/capa', [CapaController::class, 'index'])->name('capa.index');
        Route::get('/capa/{capa}', [CapaController::class, 'show'])->name('capa.show');
    });
    Route::middleware('permission:capa.manage')->group(function () {
        Route::post('/capa/{capa}/rca', [CapaController::class, 'storeRca'])->name('capa.store-rca');
        Route::post('/capa/{capa}/verification', [CapaController::class, 'storeVerification'])->name('capa.store-verification');
    });

    Route::middleware('permission:risk.view')->group(function () {
        Route::get('/risk', [RiskController::class, 'index'])->name('risk.index');
        Route::get('/risk/{risk}', [RiskController::class, 'show'])->name('risk.show');
    });
    Route::middleware('permission:risk.manage')->group(function () {
        Route::get('/risk/create', [RiskController::class, 'create'])->name('risk.create');
        Route::post('/risk', [RiskController::class, 'store'])->name('risk.store');
        Route::post('/risk/{risk}/mitigation', [RiskController::class, 'storeMitigation'])->name('risk.store-mitigation');
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
