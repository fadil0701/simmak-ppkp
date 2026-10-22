<?php

namespace App\Http\Controllers;

use App\Models\AuditExecution;
use App\Models\AuditFinding;
use App\Models\Clinic;
use App\Models\StandardCriterion;
use App\Models\User;
use App\Services\AuditCapaService;
use App\Services\AuditTrailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuditFindingController extends Controller
{
    public function __construct(private AuditCapaService $auditCapa) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $orgId = $user->organization_id;

        $query = AuditFinding::forUserClinics($user)
            ->with(['execution.clinic', 'clinic', 'assignee', 'owner', 'criterion', 'capaCase', 'escalatedBy'])
            ->orderByDesc('identified_at');

        if ($request->filled('status')) {
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

        $findings = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => AuditFinding::forUserClinics($user)->count(),
            'draft' => AuditFinding::forUserClinics($user)->where('status', 'draft')->count(),
            'under_review' => AuditFinding::forUserClinics($user)->where('status', 'under_review')->count(),
            'confirmed' => AuditFinding::forUserClinics($user)->where('status', 'confirmed')->count(),
            'action_required' => AuditFinding::forUserClinics($user)->where('status', 'action_required')->count(),
            'pending_verification' => AuditFinding::forUserClinics($user)->where('status', 'pending_verification')->count(),
            'closed' => AuditFinding::forUserClinics($user)->where('status', 'closed')->count(),
            'critical' => AuditFinding::forUserClinics($user)->where('severity', 'critical')->whereIn('status', ['open', 'in_capa', 'action_required', 'pending_verification'])->count(),
            'escalated' => AuditFinding::forUserClinics($user)->where('is_escalated', true)->where('status', '!=', 'closed')->count(),
        ];

        $clinics = Clinic::forUserClinics($user)->auditable()->where('is_active', true)->orderBy('name')->get();

        return view('audit.findings.index', compact('findings', 'stats', 'clinics'));
    }

    public function create(Request $request): View
    {
        $orgId = $request->user()->organization_id;

        $executions = AuditExecution::where('organization_id', $orgId)
            ->where('status', 'in_progress')
            ->with('clinic')
            ->get();

        $clinics = Clinic::forUserClinics($request->user())
            ->auditable()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $users = User::where('organization_id', $orgId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('audit.findings.create', compact('executions', 'clinics', 'users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $orgId = $request->user()->organization_id;

        $clinicIds = $request->user()->clinicScopeIds();

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

        if (! in_array($finding->status, ['draft', 'under_review', 'returned_for_revision'], true)) {
            abort(403, 'Temuan tidak dapat diedit pada status ini.');
        }

        $orgId = $request->user()->organization_id;
        $clinics = Clinic::forUserClinics($request->user())->auditable()->where('is_active', true)->orderBy('name')->get();
        $users = User::where('organization_id', $orgId)->where('is_active', true)->orderBy('name')->get();
        $criteria = StandardCriterion::where('active', true)->get();

        return view('audit.findings.edit', compact('finding', 'clinics', 'users', 'criteria'));
    }

    public function update(Request $request, AuditFinding $finding): RedirectResponse
    {
        $this->authorize('update', $finding);

        abort_unless($request->user()->belongsToOrganization($finding->organization_id), 403);

        $orgId = $request->user()->organization_id;

        if (! in_array($finding->status, ['draft', 'under_review', 'returned_for_revision'], true)) {
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
        $this->authorize('close', $finding);

        $request->validate([
            'close_reason' => ['required', 'string'],
        ]);

        $finding->update([
            'status' => 'closed',
            'closed_at' => now(),
            'updated_by' => $request->user()->id,
        ]);

        (new AuditTrailService)->log(
            'audit.finding.closed',
            $finding,
            null,
            ['status' => 'closed', 'closed_at' => now()->toISOString(), 'reason' => $request->string('close_reason')],
            ['actor' => $request->user()->name]
        );

        return back()->with('status', 'Temuan telah ditutup.');
    }

    public function reopen(Request $request, AuditFinding $finding): RedirectResponse
    {
        $this->authorize('reopen', $finding);

        $request->validate([
            'reopen_reason' => ['required', 'string'],
        ]);

        $finding->update([
            'status' => 'under_review',
            'closed_at' => null,
            'updated_by' => $request->user()->id,
        ]);

        (new AuditTrailService)->log(
            'audit.finding.reopened',
            $finding,
            null,
            ['status' => 'under_review', 'reason' => $request->string('reopen_reason')],
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
