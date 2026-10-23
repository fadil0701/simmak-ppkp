<?php

namespace App\Http\Controllers;

use App\Models\AuditAssignment;
use App\Models\AuditExecution;
use App\Models\AuditFinding;
use App\Models\ChecklistResponse;
use App\Models\InstrumentVersion;
use App\Models\StandardCriterion;
use App\Services\AuditCapaService;
use App\Services\AuditExecutionService;
use App\Support\AuditWorkScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuditExecutionController extends Controller
{
    public function __construct(
        private AuditExecutionService $executionService,
        private AuditCapaService $findingService
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $orgId = $user->organization_id;

        $baseQuery = AuditWorkScope::executions(AuditExecution::query(), $user);

        // Ringkasan status untuk kartu statistik: dihitung dari seluruh eksekusi
        // yang boleh dilihat pengguna, bukan hanya halaman yang sedang tampil.
        $statusCounts = (clone $baseQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $executionsQuery = (clone $baseQuery)
            ->with(['clinic', 'program', 'assignment', 'instrumentVersion.sections.criteria'])
            ->withCount([
                'findings',
                'responses as assessed_responses_count' => fn ($query) => $query->whereNotNull('response_value'),
            ])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $executionsQuery->where('status', $request->string('status'));
        }

        if ($request->filled('q')) {
            $term = '%'.trim((string) $request->string('q')).'%';
            $executionsQuery->where(function ($query) use ($term) {
                $query->where('execution_code', 'like', $term)
                    ->orWhere('title', 'like', $term);
            });
        }

        $executions = $executionsQuery->paginate(15)->withQueryString();

        $assignments = AuditWorkScope::assignments(AuditAssignment::query(), $user)
            ->where('status', 'active')
            ->with('clinic')
            ->orderBy('assignment_code')
            ->get();

        // Super admin melihat instrumen lintas organisasi; peran lain dibatasi
        // pada organisasinya sendiri.
        $instrumentVersions = InstrumentVersion::query()
            ->when(! $user->hasUnrestrictedAccess(), fn ($query) => $query->whereHas(
                'standard',
                fn ($standard) => $standard->where('organization_id', $orgId)
            ))
            ->with('standard')
            ->whereIn('status', ['active', 'approved'])
            ->orderBy('version_label')
            ->get();

        $executionTotal = (int) $statusCounts->sum();

        return view('audit.executions.index', compact(
            'executions',
            'assignments',
            'instrumentVersions',
            'statusCounts',
            'executionTotal'
        ));
    }

    public function store(Request $request, AuditAssignment $assignment): RedirectResponse
    {
        abort_unless($request->user()->belongsToOrganization($assignment->organization_id), 403);

        $validated = $request->validate([
            'instrument_version_id' => ['required', 'uuid', Rule::exists('instrument_versions', 'id')],
            'execution_code' => ['required', 'string', 'max:80', Rule::unique('audit_executions', 'execution_code')->where('organization_id', $assignment->organization_id)],
            'title' => ['required', 'string', 'max:255'],
        ]);

        return $this->createExecution($request, $assignment, $validated);
    }

    /**
     * Pembuatan eksekusi audit (dipakai oleh form di indeks & detail penugasan).
     */
    private function createExecution(Request $request, AuditAssignment $assignment, array $validated): RedirectResponse
    {
        // Instrumen harus milik organisasi yang sama dengan penugasan.
        $versionBelongsToOrg = InstrumentVersion::where('id', $validated['instrument_version_id'])
            ->whereHas('standard', fn ($query) => $query->where('organization_id', $assignment->organization_id))
            ->exists();

        abort_unless($versionBelongsToOrg, 403, 'Versi instrumen tidak tersedia untuk organisasi ini.');
        // Super admin boleh memulai pelaksanaan walau penugasan belum aktif.
        abort_unless($assignment->status === 'active' || $request->user()->hasUnrestrictedAccess(), 422, 'Hanya penugasan aktif yang dapat memulai pelaksanaan.');
        abort_unless($assignment->clinic_id, 422, 'Penugasan belum memiliki klinik sasaran.');

        $canStart = $assignment->teamMembers()
            ->where('user_id', $request->user()->id)
            ->where('active', true)
            ->exists();

        // Super admin boleh memulai eksekusi untuk penugasan mana pun.
        abort_unless($canStart || $request->user()->hasUnrestrictedAccess(), 403);

        $execution = AuditExecution::create([
            'organization_id' => $assignment->organization_id,
            'audit_program_id' => $assignment->audit_program_id,
            'audit_assignment_id' => $assignment->id,
            'clinic_id' => $assignment->clinic_id,
            'instrument_version_id' => $validated['instrument_version_id'],
            'execution_code' => $validated['execution_code'],
            'title' => $validated['title'],
            'status' => 'preparation',
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('audit.executions.checklist', $execution)->with('status', 'Eksekusi audit berhasil dibuat.');
    }

    /**
     * Membuat eksekusi dari halaman indeks (penugasan dipilih pada form).
     */
    public function storeFromSelection(Request $request): RedirectResponse
    {
        $user = $request->user();

        $assignment = AuditAssignment::query()
            ->when(! $user->hasUnrestrictedAccess(), fn ($query) => $query->where('organization_id', $user->organization_id))
            ->where('id', $request->validate([
                'audit_assignment_id' => ['required', 'uuid'],
            ])['audit_assignment_id'])
            ->firstOrFail();

        $validated = $request->validate([
            'audit_assignment_id' => ['required', 'uuid', Rule::exists('audit_assignments', 'id')],
            'instrument_version_id' => ['required', 'uuid', Rule::exists('instrument_versions', 'id')],
            'execution_code' => ['required', 'string', 'max:80', Rule::unique('audit_executions', 'execution_code')->where('organization_id', $assignment->organization_id)],
            'title' => ['required', 'string', 'max:255'],
        ]);

        return $this->createExecution($request, $assignment, $validated);
    }

    public function start(Request $request, AuditExecution $execution): RedirectResponse
    {
        $this->authorize('execute', $execution);

        $this->executionService->startExecution($execution, $request->user());

        return back()->with('status', 'Eksekusi audit telah dimulai.');
    }

    public function submit(Request $request, AuditExecution $execution): RedirectResponse
    {
        $this->authorize('submit', $execution);

        $this->executionService->submitExecution($execution, $request->user());

        return back()->with('status', 'Eksekusi audit telah diserahkan untuk tinjauan.');
    }

    public function finalize(Request $request, AuditExecution $execution): RedirectResponse
    {
        $this->authorize('finalize', $execution);

        $this->executionService->finalizeExecution($execution, $request->user());

        return back()->with('status', 'Eksekusi audit telah difinalisasi.');
    }

    public function checklist(Request $request, AuditExecution $execution): View
    {
        $this->authorize('view', $execution);

        $execution->load(['instrumentVersion.sections.criteria', 'responses.criterion', 'responses.revisions.assessedBy', 'clinic', 'assignment']);

        $sections = $execution->instrumentVersion?->sections ?? collect();
        $stats = $this->executionService->getCompletionStats($execution);
        $incompleteCriteria = $this->executionService->getIncompleteCriteria($execution);

        return view('audit.executions.checklist', compact('execution', 'sections', 'stats', 'incompleteCriteria'));
    }

    /**
     * Menyimpan seluruh baris tabel pemeriksaan sekaligus.
     *
     * Form mengikuti tata letak instrumen: EP, Elemen Penilaian, Kelengkapan
     * Bukti, SKOR, SKOR Maksimal, FAKTA DAN ANALISIS, dan REKOMENDASI.
     */
    public function saveTable(Request $request, AuditExecution $execution): RedirectResponse
    {
        $this->authorize('execute', $execution);

        $validated = $request->validate([
            'rows' => ['required', 'array'],
            'rows.*.standard_criterion_id' => ['required', 'uuid'],
            'rows.*.score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'rows.*.facts_analysis' => ['nullable', 'string', 'max:5000'],
            'rows.*.recommendation' => ['nullable', 'string', 'max:5000'],
            'rows.*.response_value' => ['nullable', 'string', 'max:40', Rule::in(['compliant', 'non_compliant', 'partial', 'not_applicable', 'not_assessable'])],
            'severity' => ['nullable', 'array'],
            'severity.*' => ['nullable', Rule::in(['minor', 'major', 'critical'])],
        ]);

        $saved = $this->executionService->saveTableRows($execution, $validated['rows'], $request->user());

        $findings = $this->syncFindingsFromTable($execution, $request, $validated);

        $message = $saved > 0
            ? "Tabel pemeriksaan tersimpan ({$saved} baris diperbarui)."
            : 'Tidak ada perubahan pada tabel pemeriksaan.';

        if ($findings > 0) {
            $message .= " {$findings} temuan audit dibuat dari tabel.";
        }

        return back()->with('status', $message);
    }

    /**
     * Temuan dan CAPA dibuat otomatis dari EP yang dinilai tidak sesuai / sesuai sebagian.
     * Temuan tidak ditutup sampai auditor memvalidasi tindak lanjut.
     */
    private function syncFindingsFromTable(AuditExecution $execution, Request $request, array $validated): int
    {
        $this->authorize('create', AuditFinding::class);

        $created = 0;

        foreach ($validated['rows'] as $row) {
            $criterionId = $row['standard_criterion_id'] ?? null;
            $responseValue = $row['response_value'] ?? null;
            if (! Str::isUuid((string) $criterionId)) {
                continue;
            }
            if (! in_array($responseValue, ['non_compliant', 'partial'], true)) {
                continue;
            }

            $criterion = StandardCriterion::find($criterionId);
            if (! $criterion) {
                continue;
            }

            $response = ChecklistResponse::where('audit_execution_id', $execution->id)
                ->where('standard_criterion_id', $criterion->id)
                ->first();

            if (! $response || ! filled($response->recommendation)) {
                continue;
            }

            $exists = AuditFinding::where('audit_execution_id', $execution->id)
                ->where('standard_criterion_id', $criterion->id)
                ->where('status', '!=', 'closed')
                ->exists();
            if ($exists) {
                continue;
            }

            $severity = $validated['severity'][$criterionId]
                ?? ($responseValue === 'non_compliant' ? 'major' : 'minor');

            $finding = $this->findingService->createFromCriterion(
                $execution,
                $criterion,
                $response,
                [
                    'severity' => $severity,
                    'finding_type' => 'non_conformity',
                    'status' => 'in_capa',
                    'created_by' => $request->user()->id,
                ]
            );

            $this->findingService->createCapaFromFinding($finding, $request->user());
            $created++;
        }

        return $created;
    }

    public function saveResponse(Request $request, AuditExecution $execution): RedirectResponse
    {
        $this->authorize('execute', $execution);

        $validated = $request->validate([
            'standard_criterion_id' => ['required', 'uuid', Rule::exists('standard_criteria', 'id')],
            'response_value' => ['nullable', 'string', 'max:40', Rule::in(['compliant', 'non_compliant', 'partial', 'not_applicable', 'not_assessable'])],
            'assessment_notes' => ['nullable', 'string'],
            'method_used' => ['nullable', 'string', 'max:80'],
            'sample_description' => ['nullable', 'string'],
            'not_applicable_reason' => ['required_if:response_value,not_applicable', 'nullable', 'string'],
            'not_assessable_reason' => ['required_if:response_value,not_assessable', 'nullable', 'string'],
        ]);

        $this->executionService->saveResponse(array_merge($validated, ['audit_execution_id' => $execution->id]), $request->user());

        return back()->with('status', 'Jawaban checklist berhasil disimpan.');
    }
}
