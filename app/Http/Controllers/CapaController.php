<?php

namespace App\Http\Controllers;

use App\Models\ActionEvidence;
use App\Models\AuditReport;
use App\Models\CapaAction;
use App\Models\CapaCase;
use App\Models\CapaRootCauseAnalysis;
use App\Models\User;
use App\Services\AuditCapaService;
use App\Services\AuditEvidenceService;
use App\Services\AuditReportService;
use App\Services\AuditTrailService;
use App\Support\AuditWorkScope;
use App\Support\SpreadsheetCell;
use App\Support\UiLabel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CapaController extends Controller
{
    public function __construct(
        private AuditCapaService $capaService,
        private AuditEvidenceService $evidenceService,
        private AuditReportService $reportService,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $query = AuditWorkScope::capaCases(CapaCase::query(), $user)
            ->with(['clinic', 'owner', 'sourceFinding'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->boolean('overdue')) {
            $query->whereNotIn('status', ['closed'])
                ->where('target_close_date', '<', now()->toDateString());
        }

        $cases = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => AuditWorkScope::capaCases(CapaCase::query(), $user)->count(),
            'open' => AuditWorkScope::capaCases(CapaCase::query(), $user)->whereIn('status', ['open', 'analysis'])->count(),
            'action' => AuditWorkScope::capaCases(CapaCase::query(), $user)->where('status', 'action')->count(),
            'closed' => AuditWorkScope::capaCases(CapaCase::query(), $user)->where('status', 'closed')->count(),
            'overdue' => AuditWorkScope::capaCases(CapaCase::query(), $user)
                ->whereNotIn('status', ['closed'])
                ->where('target_close_date', '<', now()->toDateString())
                ->count(),
        ];

        return view('capa.index', compact('cases', 'stats'));
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless($request->user()->can('capa.view') || $request->user()->can('audit.action.view'), 403);

        $query = AuditWorkScope::capaCases(CapaCase::query(), $request->user())
            ->with(['clinic', 'owner', 'sourceFinding'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->boolean('overdue')) {
            $query->whereNotIn('status', ['closed'])
                ->where('target_close_date', '<', now()->toDateString());
        }

        $cases = $query->get();
        $filename = 'tindak-lanjut-'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($cases) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Nomor', 'Masalah', 'Klinik', 'Prioritas', 'Status', 'PIC', 'Target'], ',', '"', '\\');
            foreach ($cases as $case) {
                fputcsv($handle, array_map([SpreadsheetCell::class, 'sanitize'], [
                    $case->case_number,
                    $case->problem_statement,
                    $case->clinic?->name ?? '—',
                    UiLabel::capaPriority($case->priority),
                    UiLabel::capaStatus($case->status),
                    $case->owner?->name ?? '—',
                    $case->target_close_date?->format('Y-m-d') ?? '—',
                ]), ',', '"', '\\');
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function show(Request $request, CapaCase $capa): View
    {
        $this->authorize('view', $capa);

        $capa->load([
            'clinic', 'owner', 'sourceFinding.clinicalAudit', 'sourceFinding.execution.assignment',
            'actions.assignee', 'actions.evidenceLinks.evidence', 'actions.reports',
            'rca.creator', 'verifications.verifier',
        ]);

        $users = User::where('organization_id', $capa->organization_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('capa.show', compact('capa', 'users'));
    }

    public function storeRca(Request $request, CapaCase $capa): RedirectResponse
    {
        $this->authorize('update', $capa);

        $validated = $request->validate([
            'method' => ['required', 'in:five_why,fishbone'],
            'root_cause' => ['required', 'string'],
            'whys' => ['required_if:method,five_why', 'array', 'min:1'],
            'whys.*.question' => ['required_with:whys', 'string'],
            'whys.*.answer' => ['required_with:whys', 'string'],
            'categories' => ['required_if:method,fishbone', 'array', 'min:1'],
            'categories.*.name' => ['required_with:categories', 'string'],
            'categories.*.causes' => ['required_with:categories', 'string'],
        ]);

        $content = match ($validated['method']) {
            'five_why' => [
                'whys' => $validated['whys'],
                'root_cause' => $validated['root_cause'],
            ],
            'fishbone' => [
                'categories' => collect($validated['categories'])->map(fn ($cat) => [
                    'name' => $cat['name'],
                    'causes' => array_filter(array_map('trim', explode("\n", $cat['causes']))),
                ])->all(),
                'root_cause' => $validated['root_cause'],
            ],
        };

        CapaRootCauseAnalysis::updateOrCreate(
            ['capa_case_id' => $capa->id, 'method' => $validated['method']],
            [
                'organization_id' => $capa->organization_id,
                'content' => $content,
                'created_by' => $request->user()->id,
            ]
        );

        if ($capa->status === 'open') {
            $capa->update(['status' => 'analysis', 'updated_by' => $request->user()->id]);
        }

        (new AuditTrailService)->log(
            'capa.rca_created',
            $capa,
            null,
            ['method' => $validated['method'], 'status' => 'analysis'],
            ['actor' => $request->user()->name]
        );

        return back()->with('status', 'Analisis akar masalah berhasil disimpan.');
    }

    public function storeAction(Request $request, CapaCase $capa): RedirectResponse
    {
        $this->authorize('update', $capa);

        $validated = $request->validate([
            'action_type' => ['required', 'in:corrective,preventive'],
            'description' => ['required', 'string'],
            'assigned_to' => ['nullable', 'uuid', Rule::exists('users', 'id')->where('organization_id', $capa->organization_id)],
            'due_date' => ['nullable', 'date'],
        ]);

        $this->capaService->addAction($capa, $validated, $request->user());

        return back()->with('status', 'Tindakan tindak lanjut berhasil ditambahkan.');
    }

    public function submitEvidence(Request $request, CapaAction $action): RedirectResponse
    {
        $this->authorize('update', $action);

        $validated = $request->validate([
            'evidence_notes' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx'],
        ]);

        if (! filled($validated['evidence_notes'] ?? null) && ! $request->hasFile('file')) {
            throw ValidationException::withMessages([
                'evidence_notes' => 'Uraian atau berkas bukti tindak lanjut wajib diisi.',
            ]);
        }

        $capa = $action->capaCase;
        if ($request->hasFile('file') && $capa) {
            $evidence = $this->evidenceService->store($request->file('file'), $request->user(), [
                'clinic_id' => $capa->clinic_id,
                'audit_execution_id' => $capa->sourceFinding?->audit_execution_id,
                'evidence_type' => 'capa_follow_up',
                // Tidak menaut ke clinical_audit_id agar bukti penyelesaian
                // tidak tercampur dengan dokumen temuan awal audit klinis.
                'description' => $validated['evidence_notes'] ?? 'Bukti tindak lanjut',
            ]);
            ActionEvidence::create([
                'corrective_action_id' => $action->id,
                'evidence_id' => $evidence->id,
                'description' => $validated['evidence_notes'] ?? $evidence->original_filename,
            ]);
            if (! filled($validated['evidence_notes'] ?? null)) {
                $validated['evidence_notes'] = 'Berkas: '.$evidence->original_filename;
            }
        }

        $this->capaService->submitActionEvidence($action, $validated, $request->user());

        return back()->with('status', 'Bukti tindak lanjut telah dikirim. Temuan belum tertutup sampai auditor memvalidasi kesesuaiannya.');
    }

    /**
     * Lampirkan bukti arsip tambahan pada satu tindakan tindak lanjut.
     *
     * Berbeda dari `submitEvidence()` yang menandai pengiriman bukti dan
     * mengubah status alur, method ini hanya menambah dokumen sehingga bukti
     * untuk tindakan tertentu tetap terkelompok pada tindakan tersebut.
     */
    public function attachEvidence(Request $request, CapaAction $action): RedirectResponse
    {
        $this->authorize('update', $action);

        $capa = $action->capaCase;
        abort_if($capa === null, 404);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,csv,txt'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $evidence = $this->evidenceService->store($request->file('file'), $request->user(), [
            'clinic_id' => $capa->clinic_id,
            'audit_execution_id' => $capa->sourceFinding?->audit_execution_id,
            'evidence_type' => 'capa_follow_up',
            'description' => $validated['description'] ?? 'Bukti tindak lanjut',
        ]);

        ActionEvidence::firstOrCreate(
            ['corrective_action_id' => $action->id, 'evidence_id' => $evidence->id],
            ['description' => $validated['description'] ?? $evidence->original_filename]
        );

        (new AuditTrailService)->log(
            'capa.evidence_attached',
            $capa,
            null,
            ['action_id' => $action->id, 'evidence_id' => $evidence->id, 'original_filename' => $evidence->original_filename],
            ['actor' => $request->user()->name]
        );

        return back()->with('status', 'Bukti arsip tindak lanjut berhasil dilampirkan.');
    }

    /**
     * Lepas tautan bukti dari satu tindakan tanpa menghapus berkas arsipnya.
     */
    public function detachEvidence(Request $request, CapaAction $action, ActionEvidence $link): RedirectResponse
    {
        $this->authorize('update', $action);

        abort_unless($link->corrective_action_id === $action->id, 404);

        $link->delete();

        (new AuditTrailService)->log(
            'capa.evidence_detached',
            $action->capaCase,
            null,
            ['action_id' => $action->id, 'evidence_id' => $link->evidence_id],
            ['actor' => $request->user()->name]
        );

        return back()->with('status', 'Tautan bukti dilepas dari tindakan ini. Berkas tetap tersimpan di Arsip Bukti.');
    }

    /**
     * Unggah laporan audit untuk satu tindakan tindak lanjut.
     */
    public function storeReport(Request $request, CapaAction $action): RedirectResponse
    {
        $this->authorize('update', $action);
        abort_unless($request->user()->can('audit.report.view'), 403, 'Tidak berwenang mengunggah laporan audit.');

        abort_if($action->capaCase === null, 404);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'report_type' => ['nullable', 'string', Rule::in(['capa', 'findings', 'execution', 'summary'])],
            'notes' => ['nullable', 'string', 'max:1000'],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx'],
        ]);

        $report = $this->reportService->storeFollowUpReport($action, $validated, $request->user());

        return back()->with('status', 'Laporan "'.$report->title.'" tersimpan pada tindakan ini.');
    }

    public function destroyReport(Request $request, CapaAction $action, AuditReport $report): RedirectResponse
    {
        $this->authorize('delete', $report);

        abort_unless($report->capa_action_id === $action->id, 404);

        $this->reportService->deleteReport($report, $request->user());

        return back()->with('status', 'Laporan tindak lanjut berhasil dihapus.');
    }

    public function storeVerification(Request $request, CapaCase $capa): RedirectResponse
    {
        $capa->load(['sourceFinding', 'actions']);
        $this->authorize('verify', $capa);

        $validated = $request->validate([
            'verification_date' => ['required', 'date'],
            'result' => ['required', 'in:effective,needs_improvement,not_effective'],
            'notes' => ['required', 'string'],
            'new_target_close_date' => ['required_unless:result,effective', 'nullable', 'date', 'after_or_equal:today'],
        ]);

        $this->capaService->recordVerification($capa, $validated, $request->user());

        $message = match ($validated['result']) {
            'effective' => 'Auditor menerima tindak lanjut. Temuan dan tindak lanjut ditutup.',
            'needs_improvement' => 'Hasil: perlu perbaikan. Kasus dikembalikan ke tindak lanjut.',
            default => 'Hasil: tidak efektif. Kasus dikembalikan ke tindak lanjut dengan tenggat baru.',
        };

        return back()->with('status', $message);
    }

    /**
     * Kirim uraian pelaksanaan untuk satu tindakan pada kasus ini.
     */
    public function submitCaseEvidence(Request $request, CapaCase $capa): RedirectResponse
    {
        return $this->submitEvidence($request, $this->resolveCaseAction($request, $capa));
    }

    /**
     * Unggah bukti arsip yang terikat pada satu tindakan dalam kasus ini.
     */
    public function attachCaseEvidence(Request $request, CapaCase $capa): RedirectResponse
    {
        return $this->attachEvidence($request, $this->resolveCaseAction($request, $capa));
    }

    /**
     * Unggah laporan pelaksanaan yang terikat pada satu tindakan dalam kasus ini.
     */
    public function storeCaseReport(Request $request, CapaCase $capa): RedirectResponse
    {
        return $this->storeReport($request, $this->resolveCaseAction($request, $capa));
    }

    private function resolveCaseAction(Request $request, CapaCase $capa): CapaAction
    {
        $this->authorize('view', $capa);

        $capa->loadMissing('actions');

        $actionId = $request->input('capa_action_id');
        if (! filled($actionId) && $capa->actions->count() === 1) {
            return $capa->actions->first();
        }

        $action = $capa->actions->firstWhere('id', $actionId);
        if ($action === null) {
            throw ValidationException::withMessages([
                'capa_action_id' => 'Pilih tindakan tindak lanjut yang terkait.',
            ]);
        }

        return $action;
    }
}
