<?php

namespace App\Http\Controllers;

use App\Models\AuditAssignment;
use App\Models\AuditProgram;
use App\Models\AuditTeamMember;
use App\Models\Clinic;
use App\Models\InstrumentVersion;
use App\Models\User;
use App\Services\AuditAssignmentService;
use App\Services\DocumentNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;

class AuditAssignmentController extends Controller
{
    public function __construct(
        private AuditAssignmentService $assignmentService,
        private DocumentNumberService $numbers,
    ) {}

    public function index(Request $request): View
    {
        $assignments = $this->assignmentService->getAssignmentsForUser(
            $request->user(),
            $request->boolean('upcoming')
        );

        return view('audit.assignments.index', compact('assignments'));
    }

    public function create(Request $request): View
    {
        $orgId = $request->user()->organization_id;

        $programs = AuditProgram::where('organization_id', $orgId)
            ->where('status', 'approved')
            ->orderBy('name')
            ->get();

        $clinics = Clinic::forUserClinics($request->user())
            ->auditable()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $auditors = $this->auditorCandidates($orgId);

        $instrumentVersions = $this->availableInstrumentVersions($orgId);

        $suggestedCode = $this->numbers->peek($orgId, DocumentNumberService::ASSIGNMENT);

        return view('audit.assignments.create', compact('programs', 'clinics', 'auditors', 'instrumentVersions', 'suggestedCode'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', AuditAssignment::class);
        $orgId = $request->user()->organization_id;

        $validated = $request->validate([
            'audit_program_id' => ['required', 'uuid', Rule::exists('audit_programs', 'id')->where('organization_id', $orgId)],
            'clinic_id' => ['required', 'uuid', Rule::exists('clinics', 'id')->where(fn ($query) => $query->where('organization_id', $orgId)->where('is_auditable', true))],
            'title' => ['required', 'string', 'max:255'],
            'scope_notes' => ['nullable', 'string'],
            'lead_auditor_id' => ['required', 'uuid', Rule::exists('users', 'id')->where('organization_id', $orgId)],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['uuid', Rule::exists('users', 'id')->where('organization_id', $orgId)],
            'scheduled_start' => ['required', 'date'],
            'scheduled_end' => ['required', 'date', 'after_or_equal:scheduled_start'],
            'instrument_version_id' => ['nullable', 'uuid', Rule::exists('instrument_versions', 'id')],
        ]);

        $assignment = $this->assignmentService->createAssignment($validated, $request->user());

        foreach (array_unique($validated['member_ids'] ?? []) as $memberId) {
            if ($memberId === $validated['lead_auditor_id']) {
                continue;
            }
            $this->assignmentService->addTeamMember($assignment, [
                'user_id' => $memberId,
                'team_role' => 'auditor',
            ], $request->user());
        }

        return redirect()->route('audit.assignments.index')->with('status', 'Penugasan audit berhasil dibuat.');
    }

    public function show(Request $request, AuditAssignment $assignment): View
    {
        $this->authorize('view', $assignment);

        $assignment->load([
            'program.documents.uploader',
            'clinic',
            'leadAuditor.employee',
            'teamMembers.user.employee',
            'instrumentVersion.standard',
            'executions.instrumentVersion.standard',
            'approver:id,name',
            'rejecter:id,name',
        ]);

        $instrumentVersions = $this->availableInstrumentVersions($assignment->organization_id);

        $auditors = $this->auditorCandidates($assignment->organization_id)
            ->whereNotIn('id', $assignment->teamMembers->pluck('user_id'));

        $suggestedExecutionCode = $this->numbers->peek($assignment->organization_id, DocumentNumberService::EXECUTION);

        return view('audit.assignments.show', compact('assignment', 'instrumentVersions', 'auditors', 'suggestedExecutionCode'));
    }

    public function edit(Request $request, AuditAssignment $assignment): View
    {
        $this->authorize('update', $assignment);

        $orgId = $request->user()->organization_id;
        $clinics = Clinic::forUserClinics($request->user())
            ->auditable()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $auditors = $this->auditorCandidates($orgId);

        $instrumentVersions = $this->availableInstrumentVersions($orgId);

        $assignment->loadMissing(['program', 'teamMembers.user.employee.unit', 'teamMembers.user.employee.clinic']);

        return view('audit.assignments.edit', compact('assignment', 'clinics', 'auditors', 'instrumentVersions'));
    }

    public function update(Request $request, AuditAssignment $assignment): RedirectResponse
    {
        $this->authorize('update', $assignment);
        $orgId = $request->user()->organization_id;

        $validated = $request->validate([
            'clinic_id' => ['required', 'uuid', Rule::exists('clinics', 'id')->where(fn ($query) => $query->where('organization_id', $orgId)->where('is_auditable', true))],
            'title' => ['required', 'string', 'max:255'],
            'scope_notes' => ['nullable', 'string'],
            'lead_auditor_id' => ['required', 'uuid', Rule::exists('users', 'id')->where('organization_id', $orgId)],
            'scheduled_start' => ['required', 'date'],
            'scheduled_end' => ['required', 'date', 'after_or_equal:scheduled_start'],
            'instrument_version_id' => ['nullable', 'uuid', Rule::exists('instrument_versions', 'id')],
        ]);

        $this->assignmentService->updateAssignment($assignment, $validated, $request->user());

        return redirect()->route('audit.assignments.show', $assignment)->with('status', 'Penugasan audit berhasil diperbarui.');
    }

    public function addTeamMember(Request $request, AuditAssignment $assignment): RedirectResponse
    {
        $this->authorize('update', $assignment);

        $validated = $request->validate([
            'user_id' => ['required', 'uuid', Rule::exists('users', 'id')->where('organization_id', $assignment->organization_id)],
            'team_role' => ['required', 'string', Rule::in(['lead_auditor', 'auditor', 'observer'])],
            'independence_declared' => ['nullable', 'boolean'],
            'conflict_declared' => ['nullable', 'boolean'],
            'conflict_notes' => ['nullable', 'string'],
        ]);

        $this->assignmentService->addTeamMember($assignment, $validated, $request->user());

        return back()->with('status', 'Anggota tim auditor berhasil ditambahkan.');
    }

    public function declareConflict(Request $request, AuditTeamMember $member): RedirectResponse
    {
        abort_unless($request->user()->belongsToOrganization($member->organization_id), 403);

        $validated = $request->validate([
            'conflict_declared' => ['required', 'in:0,1'],
            'conflict_notes' => ['required_if:conflict_declared,1', 'nullable', 'string'],
        ]);

        $validated['conflict_declared'] = $validated['conflict_declared'] === '1';

        $this->assignmentService->declareConflict($member, $validated, $request->user());

        return back()->with('status', 'Deklarasi independensi dan konflik kepentingan berhasil dicatat.');
    }

    public function submit(Request $request, AuditAssignment $assignment): RedirectResponse
    {
        $this->authorize('submit', $assignment);
        $this->assignmentService->submitAssignment($assignment, $request->user());

        return back()->with('status', 'Jadwal penugasan diajukan untuk persetujuan Kepala Pusat.');
    }

    public function approve(Request $request, AuditAssignment $assignment): RedirectResponse
    {
        $this->authorize('approve', $assignment);
        $this->assignmentService->approveAssignment($assignment, $request->user());

        return back()->with('status', 'Jadwal penugasan disetujui dan dipublikasikan.');
    }

    public function reject(Request $request, AuditAssignment $assignment): RedirectResponse
    {
        $this->authorize('reject', $assignment);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $this->assignmentService->rejectAssignment($assignment, $request->user(), $validated['reason']);

        return back()->with('status', 'Jadwal penugasan ditolak. Pengelola dapat memperbaiki dan mengajukan ulang.');
    }

    public function activate(Request $request, AuditAssignment $assignment): RedirectResponse
    {
        return $this->approve($request, $assignment);
    }

    public function destroy(Request $request, AuditAssignment $assignment): RedirectResponse
    {
        $this->authorize('delete', $assignment);

        if ($assignment->executions()->count() > 0) {
            return back()->with('error', 'Assignment tidak dapat dihapus karena sudah memiliki eksekusi audit.');
        }

        $assignment->delete();

        return redirect()->route('audit.assignments.index')->with('status', 'Penugasan audit berhasil dihapus.');
    }

    /**
     * Versi instrumen yang boleh direncanakan pada penugasan.
     */
    private function availableInstrumentVersions(string $organizationId)
    {
        return InstrumentVersion::whereHas('standard', function ($query) use ($organizationId) {
            $query->where('organization_id', $organizationId);
        })
            ->with('standard:id,title')
            ->whereIn('status', ['active', 'approved'])
            ->orderBy('version_label')
            ->get();
    }

    /**
     * Pegawai yang dapat ditugaskan sebagai auditor (akun aktif + peran audit).
     */
    private function auditorCandidates(string $organizationId)
    {
        // Izin bisa belum ter-seed (instalasi baru/seeder parsial); jangan sampai
        // form penugasan gagal 500 hanya karena permission belum ada.
        $canExecute = Permission::where('name', 'audit.execution.execute')
            ->where('guard_name', 'web')
            ->exists();

        if ($canExecute) {
            $candidates = User::query()
                ->where('organization_id', $organizationId)
                ->where('is_active', true)
                ->permission('audit.execution.execute')
                ->with(['employee.unit', 'employee.clinic'])
                ->orderBy('name')
                ->get();

            if ($candidates->isNotEmpty()) {
                return $candidates;
            }
        }

        return User::query()
            ->where('organization_id', $organizationId)
            ->where('is_active', true)
            ->with(['employee.unit', 'employee.clinic'])
            ->orderBy('name')
            ->get();
    }
}
