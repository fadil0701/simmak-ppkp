<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboard) {}

    public function __invoke(Request $request): View
    {
        $user = auth()->user();
        $clinic = $request->attributes->get('active_clinic');
        $data = $this->dashboard->executiveData($user);

        $region = $request->string('region')->toString();
        $ranking = $data['clinic_ranking'];
        if ($region !== '') {
            $ranking = $ranking->filter(fn (array $row) => $row['region'] === $region)->values()
                ->map(fn (array $row, int $i) => array_merge($row, ['rank' => $i + 1]));
        }

        return view('dashboard.index', [
            'user' => $user,
            'clinic' => $clinic,
            'stats' => $data['stats'],
            'capa_breakdown' => $data['capa_breakdown'],
            'heatmap' => $data['heatmap'],
            'clinic_ranking' => $ranking,
            'activities' => $data['activities'],
            'regions' => $this->dashboard->availableRegions($user->organization_id),
            'selected_region' => $region,
        ]);
    }

    public function exportRanking(Request $request): StreamedResponse
    {
        $user = $request->user();
        $ranking = $this->dashboard->buildClinicRanking($user->organization_id);
        $filename = 'ranking-klinik-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($ranking) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Rank', 'Kode', 'Klinik', 'Wilayah', 'Skor Akreditasi', 'Temuan', 'CAPA Selesai %', 'Risk Score', 'Status']);

            foreach ($ranking as $row) {
                fputcsv($handle, [
                    $row['rank'],
                    $row['code'],
                    $row['name'],
                    $row['region'],
                    $row['readiness'],
                    $row['open_findings'],
                    $row['capa_done'],
                    $row['risk_score'],
                    $row['status_label'],
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
