<?php

namespace App\Http\Controllers;

use App\Services\ClinicDashboardService;
use Illuminate\View\View;

class ClinicDashboardController extends Controller
{
    public function __construct(private ClinicDashboardService $clinicDashboard) {}

    public function __invoke(): View
    {
        $user = auth()->user();
        $clinic = request()->attributes->get('active_clinic');
        $data = $this->clinicDashboard->data($user, $clinic);

        return view('clinic.dashboard', array_merge($data, ['user' => $user]));
    }
}
