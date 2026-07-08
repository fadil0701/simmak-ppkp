<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();
        $clinic = request()->attributes->get('active_clinic');

        $stats = [
            'readiness' => 78,
            'open_findings' => 24,
            'capa_open' => 12,
            'capa_progress' => 65,
            'expired_documents' => 8,
            'active_risks' => 15,
        ];

        return view('dashboard.index', compact('user', 'clinic', 'stats'));
    }
}
