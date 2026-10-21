<?php

namespace App\Http\Controllers;

use App\Models\AuditStandard;
use App\Models\InstrumentVersion;
use App\Models\RegulatorySource;
use App\Models\StandardSection;
use App\Services\AuditStandardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuditStandardController extends Controller
{
    public function __construct(private AuditStandardService $standardService) {}

    public function index(Request $request): View
    {
        $orgId = $request->user()->organization_id;

        $standards = $this->standardService->getStandardsForOrganization($orgId);

        return view('audit.standards.index', compact('standards'));
    }

    public function create(Request $request): View
    {
        $sources = RegulatorySource::orderBy('document_number')->get();

        return view('audit.standards.create', compact('sources'));
    }

    public function store(Request $request): RedirectResponse
    {
        $orgId = $request->user()->organization_id;

        $validated = $request->validate([
            'source_document_id' => ['nullable', 'uuid', Rule::exists('regulatory_sources', 'id')],
            'code' => ['required', 'string', 'max:80', Rule::unique('audit_standards', 'code')->where('organization_id', $orgId)],
            'title' => ['required', 'string', 'max:255'],
        ]);

        $this->standardService->createStandard(array_merge($validated, ['organization_id' => $orgId]), $request->user());

        return redirect()->route('audit.standards.index')->with('status', 'Standar audit berhasil ditambahkan.');
    }

    public function show(Request $request, AuditStandard $standard): View
    {
        abort_unless($request->user()->belongsToOrganization($standard->organization_id), 403);

        $standard->load(['sourceDocument', 'instrumentVersions', 'instrumentVersions.sections.criteria']);

        return view('audit.standards.show', compact('standard'));
    }

    public function edit(Request $request, AuditStandard $standard): View
    {
        abort_unless($request->user()->belongsToOrganization($standard->organization_id), 403);

        $sources = RegulatorySource::orderBy('document_number')->get();

        return view('audit.standards.edit', compact('standard', 'sources'));
    }

    public function update(Request $request, AuditStandard $standard): RedirectResponse
    {
        $orgId = $request->user()->organization_id;
        abort_unless($standard->organization_id === $orgId, 403);

        $validated = $request->validate([
            'source_document_id' => ['nullable', 'uuid', Rule::exists('regulatory_sources', 'id')],
            'code' => ['required', 'string', 'max:80', Rule::unique('audit_standards', 'code')->where('organization_id', $orgId)->ignore($standard->id)],
            'title' => ['required', 'string', 'max:255'],
        ]);

        $standard->update($validated);

        return redirect()->route('audit.standards.show', $standard)->with('status', 'Standar audit berhasil diperbarui.');
    }

    public function destroy(Request $request, AuditStandard $standard): RedirectResponse
    {
        $orgId = $request->user()->organization_id;
        abort_unless($standard->organization_id === $orgId, 403);

        if ($standard->instrumentVersions()->count() > 0) {
            return back()->with('error', 'Standar tidak dapat dihapus karena sudah memiliki versi instrumen.');
        }

        $standard->delete();

        return redirect()->route('audit.standards.index')->with('status', 'Standar audit berhasil dihapus.');
    }

    /**
     * Menambah seksi pada versi instrumen. Versi yang sudah dipakai eksekusi
     * dikunci dan menolak perubahan (PERINTAH Wave 2 — Instrumen).
     */
    public function storeSection(Request $request, InstrumentVersion $version): RedirectResponse
    {
        $standard = $version->standard;
        abort_unless($request->user()->belongsToOrganization($standard?->organization_id), 403);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:80'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $this->standardService->createSection(
            array_merge($validated, ['instrument_version_id' => $version->id]),
            $request->user()
        );

        return back()->with('status', 'Seksi instrumen berhasil ditambahkan.');
    }

    /**
     * Menambah kriteria pada seksi. Mewarisi penguncian dari versi instrumen.
     */
    public function storeCriterion(Request $request, StandardSection $section): RedirectResponse
    {
        $version = $section->instrumentVersion;
        $standard = $version?->standard;
        abort_unless($request->user()->belongsToOrganization($standard?->organization_id), 403);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:80'],
            'title' => ['required', 'string', 'max:255'],
            'requirement_text' => ['nullable', 'string'],
            'assessment_guidance' => ['nullable', 'string'],
            'evidence_guidance' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $this->standardService->createCriterion(
            array_merge($validated, ['standard_section_id' => $section->id]),
            $request->user()
        );

        return back()->with('status', 'Kriteria instrumen berhasil ditambahkan.');
    }
}
