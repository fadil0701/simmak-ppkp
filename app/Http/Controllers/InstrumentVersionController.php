<?php

namespace App\Http\Controllers;

use App\Models\AuditStandard;
use App\Models\InstrumentVersion;
use App\Services\AuditStandardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InstrumentVersionController extends Controller
{
    public function __construct(private AuditStandardService $standardService) {}

    public function versions(Request $request, AuditStandard $standard): View
    {
        abort_unless($request->user()->belongsToOrganization($standard->organization_id), 403);

        $versions = $this->standardService->getVersionsForStandard($standard->id);

        return view('audit.standards.versions', compact('standard', 'versions'));
    }

    public function create(Request $request, AuditStandard $standard): View
    {
        abort_unless($request->user()->belongsToOrganization($standard->organization_id), 403);

        return view('audit.standards.create-version', compact('standard'));
    }

    public function store(Request $request, AuditStandard $standard): RedirectResponse
    {
        abort_unless($request->user()->belongsToOrganization($standard->organization_id), 403);

        $validated = $request->validate([
            'version_label' => ['required', 'string', 'max:80', Rule::unique('instrument_versions', 'version_label')->where('audit_standard_id', $standard->id)],
            'effective_from' => ['nullable', 'date'],
            'effective_until' => ['nullable', 'date'],
            'source_snapshot' => ['nullable', 'string'],
        ]);

        $this->standardService->createInstrumentVersion(array_merge($validated, ['audit_standard_id' => $standard->id]), $request->user());

        return redirect()->route('audit.standards.versions', $standard)->with('status', 'Versi instrumen berhasil ditambahkan.');
    }

    public function submitReview(Request $request, InstrumentVersion $version): RedirectResponse
    {
        $this->standardService->submitForReview($version, $request->user());

        return back()->with('status', 'Versi instrumen diajukan untuk tinjauan.');
    }

    public function approve(Request $request, InstrumentVersion $version): RedirectResponse
    {
        $this->standardService->approveVersion($version, $request->user());

        return back()->with('status', 'Versi instrumen telah disahkan.');
    }

    public function activate(Request $request, InstrumentVersion $version): RedirectResponse
    {
        $this->standardService->activateVersion($version, $request->user());

        return back()->with('status', 'Versi instrumen telah diaktifkan.');
    }
}
