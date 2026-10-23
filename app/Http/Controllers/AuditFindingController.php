<?php

namespace App\Http\Controllers;

use App\Models\AuditExecution;
use App\Models\AuditFinding;
use App\Models\Clinic;
use App\Models\StandardCriterion;
use App\Models\User;
use App\Services\AuditCapaService;
use App\Services\AuditTrailService;
use App\Support\AuditWorkScope;
use App\Support\SpreadsheetCell;
use App\Support\UiLabel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditFindingController extends Controller
{
    public function __construct(private AuditCapaService $auditCapa) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $query = $this->filteredFindingsQuery($request);
        $findings = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => AuditWorkScope::findings(AuditFinding::query(), $user)->count(),
            'draft' => AuditWorkScope::findings(AuditFinding::query(), $user)->where('status', 'draft')->count(),
            'under_review' => AuditWorkScope::findings(AuditFinding::query(), $user)->where('status', 'under_review')->count(),
            'confirmed' => AuditWorkScope::findings(AuditFinding::query(), $user)->where('status', 'confirmed')->count(),
            'action_required' => AuditWorkScope::findings(AuditFinding::query(), $user)->where('status', 'action_required')->count(),
            'pending_verification' => AuditWorkScope::findings(AuditFinding::query(), $user)->where('status', 'pending_verification')->count(),
            'closed' => AuditWorkScope::findings(AuditFinding::query(), $user)->where('status', 'closed')->count(),
            'critical' => AuditWorkScope::findings(AuditFinding::query(), $user)->where('severity', 'critical')->whereIn('status', AuditFinding::OPEN_STATUSES)->count(),
            'escalated' => AuditWorkScope::findings(AuditFinding::query(), $user)->where('is_escalated', true)->where('status', '!=', 'closed')->count(),
        ];

        $clinics = Clinic::forUserClinics($user)->auditable()->where('is_active', true)->orderBy('name')->get();

        return view('audit.findings.index', compact('findings', 'stats', 'clinics'));
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless($request->user()->can('audit.finding.view'), 403);

        $findings = $this->filteredFindingsQuery($request)->get();
        $filename = 'temuan-audit-'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($findings) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Kode', 'Judul', 'Klinik', 'Severity', 'Status', 'PIC', 'Tenggat', 'Rekomendasi'], ',', '"', '\\');
            foreach ($findings as $finding) {
                fputcsv($handle, array_map([SpreadsheetCell::class, 'sanitize'], [
                    $finding->finding_code,
                    $finding->title,
                    $finding->clinic?->name ?? '—',
                    UiLabel::findingSeverity($finding->severity),
                    UiLabel::findingStatus($finding->status),
                    $finding->owner?->name ?? $finding->assignee?->name ?? '—',
                    $finding->due_at?->format('Y-m-d') ?? '—',
                    $finding->recommendation ?? '—',
                ]), ',', '"', '\\');
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function filteredFindingsQuery(Request $request): Builder
    {
        $query = AuditWorkScope::findings(AuditFinding::query(), $request->user())
            ->with(['execution.clinic', 'clinic', 'assignee', 'owner', 'criterion', 'capaCase', 'escalatedBy'])
            ->orderByDesc('identified_at');

        if ($request->boolean('open') && ! $request->filled('status')) {
            $query->whereIn('status', AuditFinding::OPEN_STATUSES);
        } elseif ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->string('severity'));
        }

        if ($request->filled('clinic_id')) {
            $query->where('clinic_id', $request->string('clinic_id'));
        }

        if ($request->boolean('escalated')) {
            $query->where('is_escalated', true);
        }

        return $query;
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $orgId = $user->organization_id;

        // Super admin: seluruh eksekusi lintas organisasi, termasuk yang sudah
        // dikirim/difinalisasi (koreksi pasca-audit tetap tercatat).
        $executions = AuditExecution::query()
            ->when(! $user->hasUnrestrictedAccess(), fn ($query) => $query
                ->where('organization_id', $orgId)
                ->where('status', 'in_progress'))
            ->with('clinic')
            ->get();

        $clinics = Clinic::forUserClinics($user)
            ->auditable()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $users = User::query()
            ->when(! $user->hasUnrestrictedAccess(), fn ($query) => $query->where('organization_id', $orgId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('audit.findings.create', compact('executions', 'clinics', 'users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Super admin: organisasi temuan mengikuti eksekusi audit yang dipilih,
        // bukan organisasi user (koreksi lintas organisasi).
        $orgId = $user->hasUnrestrictedAccess()
            ? (AuditExecution::whereKey($request->input('audit_execution_id'))->value('organization_id') ?? $user->organization_id)
            : $user->organization_id;

        $clinicIds = $user->clinicScopeIds();

        $validated = $request->validate([
            'audit_execution_id' => ['required', 'uuid', Rule::exists('audit_executions', 'id')->where('organization_id', $orgId)],
            'clinic_id' => ['required', 'uuid', Rule::in($clinicIds->all())],
            'finding_code' => ['required', 'string', 'max:80', Rule::unique('audit_findings', 'finding_code')->where('organization_id', $orgId)],
            'title' => ['required', 'string', 'max:255'],
            'severity' => ['required', 'string', 'max:50', Rule::in(['minor', 'major', 'critical'])],
            'finding_type' => ['nullable', 'string', 'max:40', Rule::in(['non_conformity', 'observation', 'improvement'])],
            'description' => ['nullable', 'string'],
            'condition' => ['nullable', 'string'],
            'requirement' => ['nullable', 'string'],
            'objective_evidence_summary' => ['nullable', 'string'],
            'impact' => ['nullable', 'string'],
            'recommendation' => ['nullable', 'string'],
            'auditee_response' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'uuid', Rule::exists('users', 'id')->where('organization_id', $orgId)],
            'owner_user_id' => ['nullable', 'uuid', Rule::exists('users', 'id')->where('organization_id', $orgId)],
            'due_at' => ['nullable', 'date'],
            'standard_criterion_id' => ['nullable', 'uuid', Rule::exists('standard_criteria', 'id')],
            'status' => ['required', 'string', 'max:40', Rule::in(['draft', 'under_review', 'confirmed'])],
        ]);

        $finding = AuditFinding::create(array_merge($validated, [
            'organization_id' => $orgId,
            'identified_at' => now(),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]));

        return redirect()->route('findings.show', $finding)->with('status', 'Temuan audit berhasil dibuat.');
    }

    public function show(Request $request, AuditFinding $finding): View
    {
        $this->authorize('view', $finding);

        $finding->load(['execution.clinic', 'clinic', 'assignee', 'owner', 'criterion', 'capaCase.owner', 'evidenceLinks.evidence', 'reviewer', 'escalatedBy']);

        return view('audit.findings.show', compact('finding'));
    }

    public function edit(Request $request, AuditFinding $finding): View
    {
        $this->authorize('update', $finding);

        // Super admin boleh mengoreksi temuan pada status apa pun.
        if (! $request->user()->hasUnrestrictedAccess()
            && ! in_array($finding->status, ['draft', 'under_review', 'returned_for_revision'], true)) {
            abort(403, 'Temuan tidak dapat diedit pada status ini.');
        }

        // Super admin lintas organisasi: validasi mengikuti organisasi temuan.
        $orgId = $request->user()->hasUnrestrictedAccess()
            ? $finding->organization_id
            : $request->user()->organization_id;

        $clinics = Clinic::forUserClinics($request->user())->auditable()->where('is_active', true)->orderBy('name')->get();
        $users = User::query()
            ->when(! $request->user()->hasUnrestrictedAccess(), fn ($query) => $query->where('organization_id', $orgId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $criteria = StandardCriterion::where('active', true)->get();

        return view('audit.findings.edit', compact('finding', 'clinics', 'users', 'criteria'));
    }

    public function update(Request $request, AuditFinding $finding): RedirectResponse
    {
        $this->authorize('update', $finding);

        abort_unless($request->user()->belongsToOrganization($finding->organization_id), 403);

        // Super admin lintas organisasi: validasi mengikuti organisasi temuan.
        $orgId = $request->user()->hasUnrestrictedAccess()
            ? $finding->organization_id
            : $request->user()->organization_id;

        // Super admin boleh mengoreksi temuan pada status apa pun.
        if (! $request->user()->hasUnrestrictedAccess()
            && ! in_array($finding->status, ['draft', 'under_review', 'returned_for_revision'], true)) {
            return back()->with('error', 'Temuan tidak dapat diperbarui pada status ini.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'severity' => ['required', 'string', 'max:50', Rule::in(['minor', 'major', 'critical'])],
            'finding_type' => ['nullable', 'string', 'max:40', Rule::in(['non_conformity', 'observation', 'improvement'])],
            'description' => ['nullable', 'string'],
            'condition' => ['nullable', 'string'],
            'requirement' => ['nullable', 'string'],
            'objective_evidence_summary' => ['nullable', 'string'],
            'impact' => ['nullable', 'string'],
            'recommendation' => ['nullable', 'string'],
            'auditee_response' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'uuid', Rule::exists('users', 'id')->where('organization_id', $orgId)],
            'owner_user_id' => ['nullable', 'uuid', Rule::exists('users', 'id')->where('organization_id', $orgId)],
            'due_at' => ['nullable', 'date'],
            'standard_criterion_id' => ['nullable', 'uuid', Rule::exists('standard_criteria', 'id')],
        ]);

        $finding->update(array_merge($validated, ['updated_by' => $request->user()->id]));

        return redirect()->route('findings.show', $finding)->with('status', 'Temuan audit berhasil diperbarui.');
    }

    public function review(Request $request, AuditFinding $finding): RedirectResponse
    {
        $this->authorize('review', $finding);

        $request->validate([
            'review_decision' => ['required', 'string', Rule::in(['confirm', 'return'])],
            'review_reason' => ['required_if:review_decision,return', 'nullable', 'string'],
        ]);

        if ($request->string('review_decision') === 'confirm') {
            $finding->update([
                'status' => 'confirmed',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'updated_by' => $request->user()->id,
            ]);

            (new AuditTrailService)->log(
                'audit.finding.confirmed',
                $finding,
                null,
                ['status' => 'confirmed', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()->toISOString()],
                ['actor' => $request->user()->name]
            );

            return back()->with('status', 'Temuan telah dikonfirmasi.');
        }

        $finding->update([
            'status' => 'returned_for_revision',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'updated_by' => $request->user()->id,
        ]);

        (new AuditTrailService)->log(
            'audit.finding.returned',
            $finding,
            null,
            ['status' => 'returned_for_revision', 'reason' => $request->string('review_reason')],
            ['actor' => $request->user()->name]
        );

        return back()->with('status', 'Temuan dikembalikan untuk revisi.');
    }

    public function close(Request $request, AuditFinding $finding): RedirectResponse
    {
        $finding->load('capaCase.verifications');
        $this->authorize('close', $finding);

        $request->validate([
            'close_reason' => ['required', 'string'],
        ]);

        $oldValues = [
            'status' => $finding->status,
            'closed_at' => $finding->closed_at?->toISOString(),
        ];

        $finding->update([
            'status' => 'closed',
            'closed_at' => now(),
            'updated_by' => $request->user()->id,
        ]);

        (new AuditTrailService)->log(
            'audit.finding.closed',
            $finding,
            $oldValues,
            ['status' => 'closed', 'closed_at' => now()->toISOString(), 'reason' => $request->string('close_reason')],
            ['actor' => $request->user()->name]
        );

        return back()->with('status', 'Temuan telah ditutup setelah verifikasi efektif.');
    }

    public function reopen(Request $request, AuditFinding $finding): RedirectResponse
    {
        $this->authorize('reopen', $finding);

        $request->validate([
            'reopen_reason' => ['required', 'string'],
        ]);

        $oldValues = [
            'status' => $finding->status,
            'closed_at' => $finding->closed_at?->toISOString(),
        ];

        $newStatus = $finding->capaCase ? 'in_capa' : 'under_review';

        $finding->update([
            'status' => $newStatus,
            'closed_at' => null,
            'updated_by' => $request->user()->id,
        ]);

        (new AuditTrailService)->log(
            'audit.finding.reopened',
            $finding,
            $oldValues,
            ['status' => $newStatus, 'reason' => $request->string('reopen_reason')],
            ['actor' => $request->user()->name]
        );

        return back()->with('status', 'Temuan telah dibuka kembali.');
    }

    /**
     * Eskalasi temuan kritis — menandai temuan sebagai prioritas tinggi
     * yang memerlukan perhatian segera tanpa menunggu laporan final.
     */
    public function escalate(Request $request, AuditFinding $finding): RedirectResponse
    {
        $this->authorize('escalate', $finding);

        $validated = $request->validate([
            'escalation_reason' => ['required', 'string', 'max:1000'],
        ]);

        $oldValues = [
            'is_escalated' => $finding->is_escalated,
            'escalated_at' => $finding->escalated_at?->toISOString(),
        ];

        $finding->update([
            'is_escalated' => true,
            'escalated_at' => now(),
            'escalated_by' => $request->user()->id,
            'escalation_reason' => $validated['escalation_reason'],
            'updated_by' => $request->user()->id,
        ]);

        (new AuditTrailService)->log(
            'audit.finding.escalated',
            $finding,
            $oldValues,
            [
                'is_escalated' => true,
                'escalated_at' => now()->toISOString(),
                'reason' => $validated['escalation_reason'],
            ],
            ['actor' => $request->user()->name]
        );

        return back()->with('status', 'Temuan kritis telah dieskalasi untuk prioritas tinggi.');
    }

    public function createCapa(Request $request, AuditFinding $finding): RedirectResponse
    {
        abort_unless($request->user()->belongsToOrganization($finding->organization_id), 403);
        abort_unless($request->user()->can('audit.action.update') || $request->user()->can('capa.manage'), 403);

        if (! in_array($finding->status, ['confirmed', 'in_capa', 'action_required', 'pending_verification'], true)) {
            return back()->with('error', 'CAPA hanya dapat dibuat setelah temuan dikonfirmasi.');
        }

        if ($finding->capaCase) {
            return redirect()->route('capa.show', $finding->capaCase);
        }

        $capa = $this->auditCapa->createCapaFromFinding($finding, $request->user());

        (new AuditTrailService)->log(
            'audit.finding.capa_created',
            $finding,
            null,
            ['capa_case_id' => $capa->id, 'capa_case_number' => $capa->case_number],
            ['actor' => $request->user()->name]
        );

        return redirect()->route('capa.show', $capa)->with('status', 'CAPA berhasil dibuat dari temuan audit.');
    }

    public function destroy(Request $request, AuditFinding $finding): RedirectResponse
    {
        $this->authorize('delete', $finding);

        if ($finding->capaCase) {
            return back()->with('error', 'Temuan tidak dapat dihapus karena sudah memiliki kasus CAPA.');
        }

        $finding->delete();

        return redirect()->route('findings.index')->with('status', 'Temuan audit berhasil dihapus.');
    }
}
