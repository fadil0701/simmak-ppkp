<?php

namespace App\Http\Controllers;

use App\Models\AuditReport;
use App\Models\AuditSchedule;
use App\Support\SimmakMenu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        return redirect()->to(SimmakMenu::homeRouteFor($request->user()));
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
        // Super admin: seluruh laporan lintas organisasi.
        $reports = AuditReport::forOrganization($request->user()->organizationFilterId())
            ->with(['schedule.clinic', 'schedule.program', 'preparer'])
            ->orderByDesc('report_date')
            ->paginate(15);

        return view('audit.reports', compact('reports'));
    }
}
