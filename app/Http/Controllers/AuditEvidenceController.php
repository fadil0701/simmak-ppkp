<?php

namespace App\Http\Controllers;

use App\Models\AuditExecution;
use App\Models\Clinic;
use App\Models\Evidence;
use App\Services\AuditEvidenceService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\StreamedResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuditEvidenceController extends Controller
{
    public function __construct(private AuditEvidenceService $evidenceService) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $evidences = $this->evidenceService->getEvidenceForUser($user);

        return view('audit.evidence.index', compact('evidences'));
    }

    public function create(Request $request): View
    {
        $executions = AuditExecution::where('organization_id', $request->user()->organization_id)
            ->where('status', 'in_progress')
            ->with('clinic')
            ->get();

        $clinics = Clinic::where('organization_id', $request->user()->organization_id)
            ->auditable()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('audit.evidence.create', compact('executions', 'clinics'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Evidence::class);

        $clinicIds = $request->user()->clinicScopeIds();

        $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,csv,txt'],
            'clinic_id' => ['nullable', 'uuid', Rule::in($clinicIds->all())],
            'audit_execution_id' => ['nullable', 'uuid'],
            'evidence_type' => ['nullable', 'string', 'max:80'],
            'access_classification' => ['nullable', 'string', 'max:40', Rule::in(['internal', 'confidential', 'restricted'])],
            'description' => ['nullable', 'string'],
        ]);

        $this->evidenceService->store($request->file('file'), $request->user(), $request->only([
            'clinic_id', 'audit_execution_id', 'evidence_type', 'access_classification', 'description',
        ]));

        return redirect()->route('audit.evidence.index')->with('status', 'Bukti berhasil diunggah.');
    }

    public function show(Request $request, Evidence $evidence): View
    {
        $this->authorize('view', $evidence);

        $evidence->load(['clinic', 'execution', 'uploader']);

        return view('audit.evidence.show', compact('evidence'));
    }

    public function download(Request $request, Evidence $evidence): StreamedResponse
    {
        $this->authorize('download', $evidence);

        $path = $this->evidenceService->download($evidence);

        return Storage::disk($evidence->storage_disk)->download(
            $path,
            $evidence->original_filename
        );
    }

    public function destroy(Request $request, Evidence $evidence): RedirectResponse
    {
        abort_unless($request->user()->belongsToOrganization($evidence->organization_id), 403);

        try {
            $this->evidenceService->delete($evidence, $request->user());
        } catch (AuthorizationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('audit.evidence.index')->with('status', 'Bukti berhasil dihapus.');
    }
}
