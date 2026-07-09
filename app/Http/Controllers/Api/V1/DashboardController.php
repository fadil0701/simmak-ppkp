<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function executive(Request $request): JsonResponse
    {
        $user = $request->user();
        $organizationId = $user->organization_id;

        $clinics = Clinic::query()
            ->where('organization_id', $organizationId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'clinic_type', 'accreditation_status', 'accreditation_type']);

        return response()->json([
            'stats' => [
                'readiness' => 78,
                'open_findings' => 24,
                'capa_open' => 12,
                'capa_progress' => 65,
                'expired_documents' => 8,
                'active_risks' => 15,
            ],
            'clinic_ranking' => $clinics->map(fn (Clinic $clinic, int $index) => [
                'rank' => $index + 1,
                'clinic_id' => $clinic->id,
                'code' => $clinic->code,
                'name' => $clinic->name,
                'readiness' => rand(60, 95),
                'open_findings' => rand(0, 8),
                'accreditation_status' => $clinic->accreditation_status,
            ])->sortByDesc('readiness')->values(),
            'modules' => [
                ['code' => 'dms', 'name' => 'DMS', 'status' => 'phase_2', 'progress' => 0],
                ['code' => 'audit', 'name' => 'Audit Internal', 'status' => 'phase_3', 'progress' => 0],
                ['code' => 'capa', 'name' => 'CAPA', 'status' => 'phase_3', 'progress' => 0],
                ['code' => 'risk', 'name' => 'Manajemen Risiko', 'status' => 'phase_4', 'progress' => 0],
                ['code' => 'accreditation', 'name' => 'Akreditasi', 'status' => 'phase_5', 'progress' => 0],
                ['code' => 'quality', 'name' => 'Program Mutu', 'status' => 'phase_4', 'progress' => 0],
            ],
            'implementation' => [
                'current_phase' => 'Fase 1b — React UI + API',
                'overall_progress' => 72,
            ],
        ]);
    }
}
