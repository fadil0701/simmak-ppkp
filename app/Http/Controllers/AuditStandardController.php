<?php

namespace App\Http\Controllers;

use App\Exports\InstrumentImportTemplate;
use App\Models\AuditStandard;
use App\Models\InstrumentVersion;
use App\Models\RegulatorySource;
use App\Models\StandardCriterion;
use App\Models\StandardSection;
use App\Services\AuditInstrumentImportService;
use App\Services\AuditStandardService;
use App\Services\AuditTrailService;
use App\Support\UiLabel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class AuditStandardController extends Controller
{
    public function __construct(
        private AuditStandardService $standardService,
        private AuditInstrumentImportService $importService
    ) {}

    public function index(Request $request): View
    {
        // organizationFilterId() = null untuk super admin → daftar lintas organisasi.
        $standards = $this->standardService->getStandardsForOrganization(
            $request->user()->organizationFilterId()
        );

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
            'status' => ['nullable', Rule::in(AuditStandardService::STANDARD_STATUSES)],
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
        abort_unless($request->user()->belongsToOrganization($standard->organization_id), 403);

        $orgId = $standard->organization_id;

        $validated = $request->validate([
            'source_document_id' => ['nullable', 'uuid', Rule::exists('regulatory_sources', 'id')],
            'code' => ['required', 'string', 'max:80', Rule::unique('audit_standards', 'code')->where('organization_id', $orgId)->ignore($standard->id)],
            'title' => ['required', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(AuditStandardService::STANDARD_STATUSES)],
        ]);

        // Status diubah lewat service agar aturan bisnis (mis. standar tanpa
        // versi instrumen tidak boleh diaktifkan) tetap berlaku.
        $status = $validated['status'] ?? null;
        unset($validated['status']);

        $standard->update($validated);

        if ($status !== null && $status !== $standard->status) {
            try {
                $this->standardService->setStandardStatus($standard, $status);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return back()->withInput()->withErrors($e->errors());
            }
        }

        return redirect()->route('audit.standards.show', $standard)->with('status', 'Standar audit berhasil diperbarui.');
    }

    /**
     * Aksi cepat mengubah status master standar dari tabel/daftar.
     */
    public function updateStatus(Request $request, AuditStandard $standard): RedirectResponse
    {
        abort_unless($request->user()->belongsToOrganization($standard->organization_id), 403);

        $validated = $request->validate([
            'status' => ['required', Rule::in(AuditStandardService::STANDARD_STATUSES)],
        ]);

        try {
            $this->standardService->setStandardStatus($standard, $validated['status']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        (new AuditTrailService)->log(
            'audit.standard.status_changed',
            $standard,
            ['status' => $standard->getOriginal('status')],
            ['status' => $validated['status']],
            ['actor' => $request->user()->name]
        );

        return back()->with('status', 'Status standar diubah menjadi '.UiLabel::standardStatus($validated['status']).'.');
    }

    public function destroy(Request $request, AuditStandard $standard): RedirectResponse
    {
        abort_unless($request->user()->belongsToOrganization($standard->organization_id), 403);

        if ($standard->instrumentVersions()->count() > 0) {
            return back()->with('error', 'Standar tidak dapat dihapus karena sudah memiliki versi instrumen.');
        }

        $standard->delete();

        return redirect()->route('audit.standards.index')->with('status', 'Standar audit berhasil dihapus.');
    }

    /**
     * Menambah seksi pada versi instrumen. Perbaikan isi diizinkan meski versi
     * sudah dipakai eksekusi audit (tanpa wajib membuat versi baru).
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

    public function updateSection(Request $request, StandardSection $section): RedirectResponse
    {
        $version = $section->instrumentVersion;
        $standard = $version?->standard;
        abort_unless($request->user()->belongsToOrganization($standard?->organization_id), 403);

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:80',
                Rule::unique('standard_sections', 'code')
                    ->where('instrument_version_id', $section->instrument_version_id)
                    ->ignore($section->id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $before = $section->only(['code', 'title', 'description']);
        $this->standardService->updateSection($section, $validated, $request->user());

        (new AuditTrailService)->log(
            'audit.standard.section_updated',
            $section,
            $before,
            $section->only(['code', 'title', 'description']),
            ['actor' => $request->user()->name]
        );

        return back()->with('status', 'Seksi instrumen berhasil diperbarui.');
    }

    /**
     * Halaman formulir impor EP dari Excel/CSV.
     */
    public function importForm(Request $request, AuditStandard $standard): View
    {
        abort_unless($request->user()->belongsToOrganization($standard->organization_id), 403);

        return view('audit.standards.import', [
            'standard' => $standard,
            'rows' => [],
            'parseErrors' => [],
            'invalid' => [],
            'duplicates' => [],
            'fileName' => null,
        ]);
    }

    /**
     * Mengunduh template Excel impor EP yang sudah berisi contoh baris.
     */
    public function downloadImportTemplate(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $fileName = 'template-impor-ep-simmak.xlsx';

        return Excel::download(new InstrumentImportTemplate, $fileName);
    }

    /**
     * Preview impor EP dari Excel/CSV.
     */
    public function previewImport(Request $request, AuditStandard $standard): View
    {
        abort_unless($request->user()->belongsToOrganization($standard->organization_id), 403);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
        ]);

        $fileName = $request->file('file')->getClientOriginalName();
        $parsed = $this->importService->parse($request->file('file'));
        // Sengaja dinamai parseErrors (bukan $errors) agar tidak menimpa
        // ViewErrorBag bawaan Laravel pada view.
        $parseErrors = $parsed['errors'];

        $version = InstrumentVersion::where('audit_standard_id', $standard->id)
            ->whereIn('status', ['active', 'approved', 'draft'])
            ->orderByDesc('created_at')
            ->first();

        $result = $this->importService->validate($parsed['rows'], $standard, $version);
        $invalid = $result['invalid'];
        $duplicates = $result['duplicates'];
        $rows = $result['valid'];

        // Baris valid disimpan di session agar tidak perlu unggah ulang berkas.
        // Jika ada duplikat, impor diblokir sampai duplikat dibersihkan.
        if (empty($duplicates)) {
            $request->session()->put('instrument_import_rows', [
                'standard_id' => $standard->id,
                'rows' => $rows,
            ]);
        } else {
            $request->session()->forget('instrument_import_rows');
        }

        return view('audit.standards.import', compact('standard', 'rows', 'parseErrors', 'invalid', 'duplicates', 'fileName'));
    }

    /**
     * Menyimpan EP hasil impor ke master instrumen.
     */
    public function storeImport(Request $request, AuditStandard $standard): RedirectResponse
    {
        abort_unless($request->user()->belongsToOrganization($standard->organization_id), 403);

        $payload = $request->session()->get('instrument_import_rows');
        $request->session()->forget('instrument_import_rows');

        if (! $payload || $payload['standard_id'] !== $standard->id || empty($payload['rows'])) {
            return back()->with('error', 'Pratinjau impor tidak ditemukan atau mengandung duplikat. Silakan unggah berkas kembali.');
        }

        $rows = $payload['rows'];

        $version = InstrumentVersion::where('audit_standard_id', $standard->id)
            ->whereIn('status', ['active', 'approved', 'draft'])
            ->orderByDesc('created_at')
            ->first();

        $result = $this->importService->validate($rows, $standard, $version);

        if ($result['duplicates']) {
            return back()
                ->with('error', 'Impor dibatalkan karena ada duplikat: '.implode(' ', array_slice($result['duplicates'], 0, 5)));
        }

        if (! $result['valid']) {
            return back()->with('error', 'Tidak ada baris valid untuk disimpan.');
        }

        $stored = $this->importService->store($result['valid'], $standard, $version, $request->user());

        (new AuditTrailService)->log(
            'audit.standard.imported',
            $standard,
            null,
            ['sections' => $stored['seksi'], 'criteria' => $stored['ep']],
            ['actor' => $request->user()->name]
        );

        return redirect()->route('audit.standards.show', $standard)
            ->with('status', "Impor berhasil: {$stored['seksi']} seksi dan {$stored['ep']} EP ditambahkan.");
    }

    /**
     * Menambah kriteria pada seksi. Perbaikan isi diizinkan meski versi sudah
     * dipakai eksekusi audit.
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
            'max_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
        ]);

        $this->standardService->createCriterion(
            array_merge($validated, ['standard_section_id' => $section->id]),
            $request->user()
        );

        return back()->with('status', 'Elemen penilaian berhasil ditambahkan.');
    }

    public function updateCriterion(Request $request, StandardCriterion $criterion): RedirectResponse
    {
        $section = $criterion->section;
        $version = $section?->instrumentVersion;
        $standard = $version?->standard;
        abort_unless($request->user()->belongsToOrganization($standard?->organization_id), 403);

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:80',
                Rule::unique('standard_criteria', 'code')
                    ->where('standard_section_id', $criterion->standard_section_id)
                    ->ignore($criterion->id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'requirement_text' => ['nullable', 'string'],
            'assessment_guidance' => ['nullable', 'string'],
            'evidence_guidance' => ['nullable', 'string'],
            'max_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
        ]);

        $before = $criterion->only(['code', 'title', 'requirement_text', 'evidence_guidance', 'assessment_guidance', 'max_score']);
        $this->standardService->updateCriterion($criterion, $validated, $request->user());

        (new AuditTrailService)->log(
            'audit.standard.criterion_updated',
            $criterion,
            $before,
            $criterion->only(['code', 'title', 'requirement_text', 'evidence_guidance', 'assessment_guidance', 'max_score']),
            ['actor' => $request->user()->name]
        );

        return back()->with('status', 'Elemen penilaian berhasil diperbarui.');
    }
}
