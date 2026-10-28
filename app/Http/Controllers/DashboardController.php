<?php

namespace App\Http\Controllers;

use App\Services\AuditDashboardService;
use App\Services\DashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboard,
        private AuditDashboardService $auditDashboard,
    ) {}

    public function __invoke(Request $request): View
    {
        $user = $request->user();

        abort_unless(
            $user->can('dashboard.executive') || $user->can('audit.dashboard.view'),
            403
        );

        $showExecutive = $user->can('dashboard.executive');
        $payload = [
            'user' => $user,
            'clinic' => $request->attributes->get('active_clinic'),
            'showExecutive' => $showExecutive,
            'canViewClinicDashboard' => $user->can('dashboard.clinic'),
        ];

        if ($showExecutive) {
            $data = $this->dashboard->executiveData($user);
            $region = $request->string('region')->toString();
            $ranking = $data['clinic_ranking'];
            if ($region !== '') {
                $ranking = $ranking->filter(fn (array $row) => $row['region'] === $region)->values()
                    ->map(fn (array $row, int $i) => array_merge($row, ['rank' => $i + 1]));
            }

            $payload = array_merge($payload, [
                'stats' => $data['stats'],
                'capa_breakdown' => $data['capa_breakdown'],
                'heatmap' => $data['heatmap'],
                'clinic_ranking' => $ranking,
                'activities' => $data['activities'],
                'regions' => $this->dashboard->availableRegions($user->organization_id),
                'selected_region' => $region,
            ]);
        }

        $audit = $this->auditDashboard->auditDashboardData($user);
        $payload = array_merge($payload, [
            'auditStats' => $audit['stats'],
            'overdueActions' => $audit['overdueActions'],
            'reviewQueue' => $audit['reviewQueue'],
            'highRiskFindings' => $audit['highRiskFindings'],
            'upcomingAssignments' => $audit['upcomingAssignments'],
            'recentActivities' => $audit['recentActivities'],
            'findingsByClinic' => $audit['findingsByClinic'],
            'auditCharts' => $audit['charts'],
        ]);

        return view('dashboard.index', $payload);
    }

    public function exportRanking(Request $request): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user->can('dashboard.executive'), 403);

        $ranking = $this->dashboard->buildClinicRanking($user->organization_id);
        $filename = 'ranking-klinik-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($ranking) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Rank', 'Kode', 'Klinik', 'Wilayah', 'Skor Akreditasi', 'Temuan', 'Tindak Lanjut Selesai %', 'Risk Score', 'Status']);

            foreach ($ranking as $row) {
                fputcsv($handle, array_map(fn ($value) => $this->sanitizeCsvValue($value), [
                    $row['rank'],
                    $row['code'],
                    $row['name'],
                    $row['region'],
                    $row['readiness'],
                    $row['open_findings'],
                    $row['capa_done'],
                    $row['risk_score'],
                    $row['status_label'],
                ]));
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=utf-8']);
    }

    public function redirectAuditDashboard(): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    /**
     * Sanitasi nilai CSV untuk mencegah formula injection.
     * Nilai yang diawali dengan =, +, -, @, tab, atau carriage return
     * diawali dengan apostrof agar tidak dieksekusi sebagai formula di Excel.
     */
    private function sanitizeCsvValue(mixed $value): string
    {
        $text = (string) $value;

        if (preg_match('/^[=+\-@\t\r]/', $text)) {
            return "'".$text;
        }

        return $text;
    }
}
