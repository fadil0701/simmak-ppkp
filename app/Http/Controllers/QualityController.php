<?php

namespace App\Http\Controllers;

use App\Models\QualityProgram;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QualityController extends Controller
{
    public function index(Request $request): View
    {
        $orgId = $request->user()->organization_id;
        $clinic = $request->attributes->get('active_clinic');

        $programs = QualityProgram::forOrganization($orgId)
            ->when($clinic, fn ($q) => $q->where(fn ($inner) => $inner->where('clinic_id', $clinic->id)->orWhereNull('clinic_id')))
            ->with([
                'clinic',
                'fiscalYear',
                'indicators' => fn ($q) => $q->where('is_active', true)->with(['measurements' => fn ($m) => $m->orderByDesc('measurement_date')->limit(6)]),
            ])
            ->orderByDesc('created_at')
            ->get();

        $stats = [
            'programs' => $programs->count(),
            'indicators' => $programs->sum(fn ($p) => $p->indicators->count()),
            'measurements' => $programs->sum(fn ($p) => $p->indicators->sum(fn ($i) => $i->measurements->count())),
        ];

        return view('quality.index', compact('programs', 'stats', 'clinic'));
    }
}
