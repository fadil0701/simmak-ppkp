<?php

namespace App\Http\Controllers;

use App\Models\AuditProgram;
use App\Models\AuditReport;
use App\Models\AuditSchedule;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $orgId = $user->organization_id;

        $programs = AuditProgram::forOrganization($orgId)
            ->withCount('schedules')
            ->orderByDesc('created_at')
            ->get();

        $schedules = AuditSchedule::forUserClinics($user)
            ->with(['program', 'clinic', 'leadAuditor'])
            ->withCount('findings')
            ->orderBy('scheduled_start')
            ->paginate(10);

        $stats = [
            'programs' => $programs->count(),
            'scheduled' => AuditSchedule::forUserClinics($user)->where('status', 'scheduled')->count(),
            'ongoing' => AuditSchedule::forUserClinics($user)->where('status', 'ongoing')->count(),
            'completed' => AuditSchedule::forUserClinics($user)->where('status', 'completed')->count(),
        ];

        return view('audit.index', compact('programs', 'schedules', 'stats'));
    }

    public function checklist(Request $request, AuditSchedule $schedule): View
    {
        abort_unless($request->user()->belongsToOrganization($schedule->organization_id), 403);

        $schedule->load(['program', 'clinic', 'leadAuditor', 'checklists.items']);

        $checklist = $schedule->checklists->first();

        return view('audit.checklist', compact('schedule', 'checklist'));
    }

    public function reports(Request $request): View
    {
        $orgId = $request->user()->organization_id;

        $reports = AuditReport::forOrganization($orgId)
            ->with(['schedule.clinic', 'schedule.program', 'preparer'])
            ->orderByDesc('report_date')
            ->paginate(15);

        return view('audit.reports', compact('reports'));
    }
}
