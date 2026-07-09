<?php

namespace App\Http\Controllers;

use App\Models\AccreditationChapter;
use App\Models\AccreditationEvidence;
use App\Models\AccreditationGapItem;
use App\Models\AccreditationProgram;
use App\Models\AccreditationSelfAssessment;
use App\Models\Document;
use App\Services\AccreditationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccreditationController extends Controller
{
    public function __construct(private AccreditationService $accreditationService) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $clinic = $request->attributes->get('active_clinic');
        $data = $this->accreditationService->dashboardData($user->organization_id, $clinic);

        return view('accreditation.index', array_merge($data, ['user' => $user]));
    }

    public function gaps(Request $request): View
    {
        $user = $request->user();
        $clinic = $request->attributes->get('active_clinic');
        $orgId = $user->organization_id;

        $program = AccreditationProgram::forOrganization($orgId)
            ->when($clinic, fn ($q) => $q->where('clinic_id', $clinic->id))
            ->latest()
            ->first();

        $baseQuery = AccreditationGapItem::forOrganization($orgId)
            ->when($program, fn ($q) => $q->where('program_id', $program->id));

        $stats = [
            'critical' => (clone $baseQuery)->where('severity', 'critical')->where('status', '!=', 'closed')->count(),
            'major' => (clone $baseQuery)->where('severity', 'major')->where('status', '!=', 'closed')->count(),
            'minor' => (clone $baseQuery)->where('severity', 'minor')->where('status', '!=', 'closed')->count(),
        ];
        $stats['total'] = $stats['critical'] + $stats['major'] + $stats['minor'];

        $query = (clone $baseQuery)->with(['standard.chapter'])->orderByRaw("FIELD(severity, 'critical', 'major', 'minor')");

        if ($request->filled('severity')) {
            $query->where('severity', $request->string('severity'));
        }

        $gaps = $query->paginate(20)->withQueryString();

        return view('accreditation.gaps', compact('gaps', 'stats', 'program', 'clinic'));
    }

    public function selfAssessment(Request $request): View
    {
        $user = $request->user();
        $clinic = $request->attributes->get('active_clinic');
        $orgId = $user->organization_id;

        $program = AccreditationProgram::forOrganization($orgId)
            ->when($clinic, fn ($q) => $q->where('clinic_id', $clinic->id))
            ->with('type')
            ->latest()
            ->first();

        $assessment = $program
            ? AccreditationSelfAssessment::forOrganization($orgId)
                ->where('program_id', $program->id)
                ->with(['items.standard.chapter', 'assessor'])
                ->latest()
                ->first()
            : null;

        $itemsByChapter = $assessment
            ? $assessment->items->groupBy(fn ($item) => $item->standard?->chapter?->chapter_number ?? '—')
            : collect();

        return view('accreditation.self-assessment', compact('program', 'assessment', 'itemsByChapter', 'clinic'));
    }

    public function mockSurvey(Request $request): View
    {
        $user = $request->user();
        $clinic = $request->attributes->get('active_clinic');
        $orgId = $user->organization_id;

        $program = AccreditationProgram::forOrganization($orgId)
            ->when($clinic, fn ($q) => $q->where('clinic_id', $clinic->id))
            ->with(['type', 'clinic', 'mockSurveys'])
            ->latest()
            ->first();

        $surveys = $program
            ? $program->mockSurveys()->orderByDesc('survey_date')->get()
            : collect();

        return view('accreditation.mock-survey', compact('program', 'surveys', 'clinic'));
    }

    public function evidenceIndex(Request $request): View
    {
        $user = $request->user();
        $clinic = $request->attributes->get('active_clinic');
        $orgId = $user->organization_id;

        $program = AccreditationProgram::forOrganization($orgId)
            ->when($clinic, fn ($q) => $q->where('clinic_id', $clinic->id))
            ->latest()
            ->first();

        $evidence = $program
            ? AccreditationEvidence::forOrganization($orgId)
                ->where('program_id', $program->id)
                ->with(['document', 'program'])
                ->orderBy('title')
                ->paginate(20)
            : null;

        $documents = Document::forOrganization($orgId)
            ->whereIn('status', ['published', 'approved'])
            ->orderBy('title')
            ->get(['id', 'title', 'document_number']);

        $stats = $program && $evidence
            ? [
                'total' => AccreditationEvidence::forOrganization($orgId)->where('program_id', $program->id)->count(),
                'uploaded' => AccreditationEvidence::forOrganization($orgId)->where('program_id', $program->id)->where('status', 'uploaded')->count(),
            ]
            : ['total' => 0, 'uploaded' => 0];

        return view('accreditation.evidence', compact('program', 'evidence', 'documents', 'stats', 'clinic'));
    }

    public function linkEvidence(Request $request, AccreditationEvidence $evidence): RedirectResponse
    {
        abort_unless($evidence->organization_id === $request->user()->organization_id, 403);

        $validated = $request->validate([
            'document_id' => [
                'required',
                'uuid',
                Rule::exists('documents', 'id')->where('organization_id', $request->user()->organization_id),
            ],
        ]);

        $evidence->update([
            'document_id' => $validated['document_id'],
            'status' => 'uploaded',
        ]);

        return back()->with('status', 'Bukti akreditasi berhasil ditautkan ke dokumen.');
    }

    public function standardsIndex(Request $request): View
    {
        $user = $request->user();
        $clinic = $request->attributes->get('active_clinic');
        $orgId = $user->organization_id;

        $program = AccreditationProgram::forOrganization($orgId)
            ->when($clinic, fn ($q) => $q->where('clinic_id', $clinic->id))
            ->with('type')
            ->latest()
            ->first();

        $chapters = $program
            ? AccreditationChapter::query()
                ->where('accreditation_type_id', $program->accreditation_type_id)
                ->where('is_active', true)
                ->with(['standards' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
                ->orderBy('sort_order')
                ->get()
            : collect();

        return view('accreditation.standards', compact('program', 'chapters', 'clinic'));
    }
}
