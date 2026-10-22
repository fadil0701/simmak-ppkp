<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class AuditDashboardController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        return redirect()->route('dashboard');
    }
}
