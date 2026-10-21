<?php

namespace App\Http\Controllers;

use App\Models\AuditExecution;
use App\Services\AuditExecutionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuditExecutionController extends Controller
{
    public function __construct(private AuditExecutionService $executionService) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $orgId = $user->organization_id;

        $executions = AuditExecution::forOrganization($orgId)
            ->with(['clinic', 'program', 'assignment'])
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('audit.executions.index', compact('executions'));
    }

    public function store(Request $request, AuditAssignment $assignment): RedirectResponse
    {
        abort_unless($request->user()->belongsToOrganization($assignment->organization_id), 403);

        $validated = $request->validate([
            'instrument_version_id' => ['required', 'uuid', Rule::exists('instrument_versions', 'id')],
            'execution_code' => ['required', 'string', 'max:80', Rule::unique('audit_executions', 'execution_code')->where('organization_id', $assignment->organization_id)],
            'title' => ['required', 'string', 'max:255'],
        ]);

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

        return redirect()->route('audit.executions.checklist', $execution)->with('status', 'Eksekusi audit telah dibuat.');
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
