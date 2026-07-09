<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
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

        return view('reports.index', [
            'ranking_preview' => $ranking,
            'stats' => $data['stats'],
        ]);
    }
}
