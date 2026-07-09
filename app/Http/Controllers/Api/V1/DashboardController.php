<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboard) {}

    public function executive(Request $request): JsonResponse
    {
        $data = $this->dashboard->executiveData($request->user());

        return response()->json([
            'stats' => $data['stats'],
            'capa_breakdown' => $data['capa_breakdown'],
            'clinic_ranking' => $data['clinic_ranking'],
            'heatmap' => $data['heatmap'],
            'activities' => $data['activities'],
            'implementation' => [
                'current_phase' => 'Fase 6 — Dashboard Enterprise',
                'overall_progress' => 45,
            ],
            'modules' => [
                ['code' => 'dms', 'name' => 'DMS', 'status' => 'phase_2', 'progress' => 55],
                ['code' => 'audit', 'name' => 'Audit Internal', 'status' => 'phase_3', 'progress' => 30],
                ['code' => 'capa', 'name' => 'CAPA', 'status' => 'phase_3', 'progress' => 30],
                ['code' => 'risk', 'name' => 'Manajemen Risiko', 'status' => 'phase_4', 'progress' => 25],
                ['code' => 'accreditation', 'name' => 'Akreditasi', 'status' => 'phase_5', 'progress' => 30],
                ['code' => 'dashboard', 'name' => 'Dashboard Enterprise', 'status' => 'phase_6', 'progress' => 40],
            ],
        ]);
    }
}
