<?php

namespace App\Http\Controllers;

use App\Models\ApprovalEvent;
use App\Models\AuditProgram;
use App\Models\FiscalYear;
use App\Services\AuditProgramService;
use App\Services\AuditTrailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuditProgramController extends Controller
{
    public function __construct(private AuditProgramService $programService) {}

    public function index(Request $request): View
    {
        $orgId = $request->user()->organization_id;

        $programs = $this->programService->getProgramsForOrganization($orgId);

        return view('audit.programs.index', compact('programs'));
    }

    public function create(Request $request): View
    {
        $fiscalYears = FiscalYear::where('organization_id', $request->user()->organization_id)
            ->where('is_active', true)
            ->orderByDesc('code')
            ->get();

        return view('audit.programs.create', compact('fiscalYears'));
    }

    public function store(Request $request): RedirectResponse
    {
        $orgId = $request->user()->organization_id;

        $validated = $request->validate([
            'program_code' => ['required', 'string', 'max:80', Rule::unique('audit_programs', 'program_code')->where('organization_id', $orgId)],
            'audit_type' => ['nullable', 'string', 'max:40', Rule::in(['periodic', 'thematic', 'special', 'follow_up'])],
            'scope_notes' => ['nullable', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'objective' => ['nullable', 'string'],
            'fiscal_year_id' => ['nullable', 'uuid', Rule::exists('fiscal_years', 'id')->where('organization_id', $orgId)],
        ]);

        $program = $this->programService->createProgram($validated, $request->user());

        (new AuditTrailService)->log(
            'audit.program.created',
            $program,
            null,
            ['program_code' => $program->program_code, 'name' => $program->name],
            ['actor' => $request->user()->name]
        );

        return redirect()->route('audit.programs.index')->with('status', 'Program audit berhasil dibuat.');
    }

    public function show(Request $request, AuditProgram $program): View
    {
        abort_unless($request->user()->belongsToOrganization($program->organization_id), 403);

        $program->load(['schedules.clinic', 'creator']);

        return view('audit.programs.show', compact('program'));
    }

    public function edit(Request $request, AuditProgram $program): View
    {
        abort_unless($request->user()->belongsToOrganization($program->organization_id), 403);

        $fiscalYears = FiscalYear::where('organization_id', $request->user()->organization_id)
            ->where('is_active', true)
            ->orderByDesc('code')
            ->get();

        return view('audit.programs.edit', compact('program', 'fiscalYears'));
    }

    public function update(Request $request, AuditProgram $program): RedirectResponse
    {
        $orgId = $request->user()->organization_id;
        abort_unless($request->user()->belongsToOrganization($program->organization_id), 403);

        $validated = $request->validate([
            'program_code' => ['required', 'string', 'max:80', Rule::unique('audit_programs', 'program_code')->where('organization_id', $orgId)->ignore($program->id)],
            'audit_type' => ['nullable', 'string', 'max:40', Rule::in(['periodic', 'thematic', 'special', 'follow_up'])],
            'scope_notes' => ['nullable', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'objective' => ['nullable', 'string'],
            'fiscal_year_id' => ['nullable', 'uuid', Rule::exists('fiscal_years', 'id')->where('organization_id', $orgId)],
        ]);

        $program->update(array_merge($validated, ['updated_by' => $request->user()->id]));

        return redirect()->route('audit.programs.show', $program)->with('status', 'Program audit berhasil diperbarui.');
    }

    public function submit(Request $request, AuditProgram $program): RedirectResponse
    {
        $this->programService->submitProgram($program, $request->user());

        ApprovalEvent::create([
            'organization_id' => $program->organization_id,
            'entity_type' => 'audit_program',
            'entity_id' => $program->id,
            'step_key' => 'submit',
            'actor_user_id' => $request->user()->id,
            'decision' => 'submitted',
            'reason' => 'Program diajukan untuk tinjauan.',
            'metadata' => ['status' => 'submitted_for_review', 'submitted_at' => now()->toISOString()],
            'acted_at' => now(),
        ]);

        (new AuditTrailService)->log(
            'audit.program.submitted',
            $program,
            null,
            ['status' => 'submitted_for_review', 'submitted_at' => now()->toISOString()],
            ['actor' => $request->user()->name]
        );

        return back()->with('status', 'Program audit diajukan untuk tinjauan.');
    }

    public function approve(Request $request, AuditProgram $program): RedirectResponse
    {
        $this->programService->approveProgram($program, $request->user());

        ApprovalEvent::create([
            'organization_id' => $program->organization_id,
            'entity_type' => 'audit_program',
            'entity_id' => $program->id,
            'step_key' => 'approve',
            'actor_user_id' => $request->user()->id,
            'decision' => 'approved',
            'reason' => 'Program audit disetujui.',
            'metadata' => ['status' => 'approved', 'approved_by' => $request->user()->id, 'approved_at' => now()->toISOString()],
            'acted_at' => now(),
        ]);

        (new AuditTrailService)->log(
            'audit.program.approved',
            $program,
            null,
            ['status' => 'approved', 'approved_by' => $request->user()->id, 'approved_at' => now()->toISOString()],
            ['actor' => $request->user()->name]
        );

        return back()->with('status', 'Program audit telah disetujui.');
    }

    public function returnRevision(Request $request, AuditProgram $program): RedirectResponse
    {
        $request->validate([
            'reason' => ['required', 'string'],
        ]);

        $this->programService->returnForRevision($program, $request->user(), $request->string('reason'));

        ApprovalEvent::create([
            'organization_id' => $program->organization_id,
            'entity_type' => 'audit_program',
            'entity_id' => $program->id,
            'step_key' => 'return',
            'actor_user_id' => $request->user()->id,
            'decision' => 'returned',
            'reason' => $request->string('reason'),
            'metadata' => ['status' => 'returned_for_revision'],
            'acted_at' => now(),
        ]);

        (new AuditTrailService)->log(
            'audit.program.returned',
            $program,
            null,
            ['status' => 'returned_for_revision', 'reason' => $request->string('reason')],
            ['actor' => $request->user()->name]
        );

        return back()->with('status', 'Program audit dikembalikan untuk revisi.');
    }

    public function destroy(Request $request, AuditProgram $program): RedirectResponse
    {
        $orgId = $request->user()->organization_id;
        abort_unless($request->user()->belongsToOrganization($program->organization_id), 403);

        if ($program->schedules()->count() > 0) {
            return back()->with('error', 'Program tidak dapat dihapus karena sudah memiliki jadwal audit.');
        }

        (new AuditTrailService)->log(
            'audit.program.deleted',
            $program,
            ['program_code' => $program->program_code, 'name' => $program->name],
            null,
            ['actor' => $request->user()->name]
        );

        $program->delete();

        return redirect()->route('audit.programs.index')->with('status', 'Program audit berhasil dihapus.');
    }
}
