<?php

namespace App\Http\Controllers;

use App\Models\AuditAssignment;
use App\Models\AuditProgram;
use App\Models\AuditTeamMember;
use App\Models\Clinic;
use App\Models\InstrumentVersion;
use App\Models\User;
use App\Services\AuditAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuditAssignmentController extends Controller
{
    public function __construct(private AuditAssignmentService $assignmentService) {}

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

        return view('audit.assignments.create', compact('programs', 'clinics', 'auditors'));
    }

    public function store(Request $request): RedirectResponse
    {
        $orgId = $request->user()->organization_id;

        $validated = $request->validate([
            'audit_program_id' => ['required', 'uuid', Rule::exists('audit_programs', 'id')->where('organization_id', $orgId)],
            'clinic_id' => ['required', 'uuid', Rule::exists('clinics', 'id')->where(fn ($query) => $query->where('organization_id', $orgId)->where('is_auditable', true))],
            'assignment_code' => ['required', 'string', 'max:80', Rule::unique('audit_assignments', 'assignment_code')->where('organization_id', $orgId)],
            'title' => ['required', 'string', 'max:255'],
            'scope_notes' => ['nullable', 'string'],
            'lead_auditor_id' => ['required', 'uuid', Rule::exists('users', 'id')->where('organization_id', $orgId)],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['uuid', Rule::exists('users', 'id')->where('organization_id', $orgId)],
            'scheduled_start' => ['required', 'date'],
            'scheduled_end' => ['required', 'date', 'after_or_equal:scheduled_start'],
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

        $assignment->load(['program', 'clinic', 'leadAuditor', 'teamMembers.user']);

        $instrumentVersions = InstrumentVersion::whereHas('standard', function ($query) use ($assignment) {
            $query->where('organization_id', $assignment->organization_id);
        })
            ->whereIn('status', ['active', 'approved'])
            ->orderBy('version_label')
            ->get();

        $auditors = $this->auditorCandidates($assignment->organization_id)
            ->whereNotIn('id', $assignment->teamMembers->pluck('user_id'));

        return view('audit.assignments.show', compact('assignment', 'instrumentVersions', 'auditors'));
    }

    public function edit(Request $request, AuditAssignment $assignment): View
    {
        abort_unless($request->user()->belongsToOrganization($assignment->organization_id), 403);

        $orgId = $request->user()->organization_id;
        $clinics = Clinic::forUserClinics($request->user())
            ->auditable()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $auditors = $this->auditorCandidates($orgId);

        return view('audit.assignments.edit', compact('assignment', 'clinics', 'auditors'));
    }

    public function update(Request $request, AuditAssignment $assignment): RedirectResponse
    {
        $orgId = $request->user()->organization_id;
        abort_unless($request->user()->belongsToOrganization($assignment->organization_id), 403);

        $validated = $request->validate([
            'clinic_id' => ['required', 'uuid', Rule::exists('clinics', 'id')->where(fn ($query) => $query->where('organization_id', $orgId)->where('is_auditable', true))],
            'title' => ['required', 'string', 'max:255'],
            'scope_notes' => ['nullable', 'string'],
            'lead_auditor_id' => ['required', 'uuid', Rule::exists('users', 'id')->where('organization_id', $orgId)],
            'scheduled_start' => ['required', 'date'],
            'scheduled_end' => ['required', 'date', 'after_or_equal:scheduled_start'],
        ]);

        $this->assignmentService->updateAssignment($assignment, $validated, $request->user());

        return redirect()->route('audit.assignments.show', $assignment)->with('status', 'Penugasan audit berhasil diperbarui.');
    }

    public function addTeamMember(Request $request, AuditAssignment $assignment): RedirectResponse
    {
        abort_unless($request->user()->belongsToOrganization($assignment->organization_id), 403);

        $validated = $request->validate([
            'user_id' => ['required', 'uuid', Rule::exists('users', 'id')->where('organization_id', $assignment->organization_id)],
            'team_role' => ['required', 'string', 'max:80'],
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
        $this->assignmentService->submitAssignment($assignment, $request->user());

        return back()->with('status', 'Penugasan audit diajukan untuk aktivasi.');
    }

    public function activate(Request $request, AuditAssignment $assignment): RedirectResponse
    {
        $this->assignmentService->activateAssignment($assignment, $request->user());

        return back()->with('status', 'Penugasan audit telah diaktifkan.');
    }

    public function destroy(Request $request, AuditAssignment $assignment): RedirectResponse
    {
        $orgId = $request->user()->organization_id;
        abort_unless($request->user()->belongsToOrganization($assignment->organization_id), 403);

        if ($assignment->executions()->count() > 0) {
            return back()->with('error', 'Assignment tidak dapat dihapus karena sudah memiliki eksekusi audit.');
        }

        $assignment->delete();

        return redirect()->route('audit.assignments.index')->with('status', 'Penugasan audit berhasil dihapus.');
    }

    /**
     * Pegawai yang dapat ditugaskan sebagai auditor (akun aktif + peran audit).
     */
    private function auditorCandidates(string $organizationId)
    {
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

        return User::query()
            ->where('organization_id', $organizationId)
            ->where('is_active', true)
            ->with(['employee.unit', 'employee.clinic'])
            ->orderBy('name')
            ->get();
    }
}
