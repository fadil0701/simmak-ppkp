<?php

namespace App\Http\Controllers;

use App\Models\ActionEvidence;
use App\Models\CapaAction;
use App\Models\CapaCase;
use App\Models\CapaRootCauseAnalysis;
use App\Models\User;
use App\Services\AuditCapaService;
use App\Services\AuditEvidenceService;
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

        $capa->load(['clinic', 'owner', 'sourceFinding.execution.assignment', 'actions.assignee', 'actions.evidenceLinks.evidence', 'rca.creator', 'verifications.verifier']);

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
}
