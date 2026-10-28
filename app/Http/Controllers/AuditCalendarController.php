<?php

namespace App\Http\Controllers;

use App\Services\AuditCalendarService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditCalendarController extends Controller
{
    public function __construct(private AuditCalendarService $calendar) {}

    public function __invoke(Request $request): View
    {
        $year = (int) $request->integer('year', now()->year);
        $month = (int) $request->integer('month', now()->month);

        if ($year < 2000 || $year > 2100) {
            $year = now()->year;
        }
        if ($month < 1 || $month > 12) {
            $month = now()->month;
        }

        $calendar = $this->calendar->monthView($request->user(), $year, $month);

        return view('audit.assignments.calendar', compact('calendar'));
    }
}
