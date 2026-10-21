<?php

namespace App\Http\Controllers;

use App\Services\AuditDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditDashboardController extends Controller
{
    public function __construct(private AuditDashboardService $dashboard) {}

    public function __invoke(Request $request): View
    {
        abort_unless($request->user()->can('audit.dashboard.view'), 403);

        $user = $request->user();
        $data = $this->dashboard->auditDashboardData($user);

        return view('audit.dashboard.index', [
            'user' => $user,
            'stats' => $data['stats'],
            'overdueActions' => $data['overdueActions'],
            'reviewQueue' => $data['reviewQueue'],
            'highRiskFindings' => $data['highRiskFindings'],
            'recentActivities' => $data['recentActivities'],
            'findingsByClinic' => $data['findingsByClinic'],
        ]);
    }
}
