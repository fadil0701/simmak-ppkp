<?php

namespace App\Http\Controllers;

use App\Models\AuditFinding;
use App\Services\AuditCapaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditFindingController extends Controller
{
    public function __construct(private AuditCapaService $auditCapa) {}

    public function index(Request $request): View
    {
        $orgId = $request->user()->organization_id;

        $query = AuditFinding::forOrganization($orgId)
            ->with(['schedule.program', 'clinic', 'assignee'])
            ->orderByDesc('identified_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->string('severity'));
        }

        $findings = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => AuditFinding::forOrganization($orgId)->count(),
            'open' => AuditFinding::forOrganization($orgId)->where('status', 'open')->count(),
            'in_capa' => AuditFinding::forOrganization($orgId)->where('status', 'in_capa')->count(),
            'closed' => AuditFinding::forOrganization($orgId)->where('status', 'closed')->count(),
            'critical' => AuditFinding::forOrganization($orgId)->where('severity', 'critical')->whereIn('status', ['open', 'in_capa'])->count(),
        ];

        return view('audit.findings.index', compact('findings', 'stats'));
    }

    public function show(Request $request, AuditFinding $finding): View
    {
        abort_unless($finding->organization_id === $request->user()->organization_id, 403);

        $finding->load(['schedule.program', 'clinic', 'assignee', 'capaCase.owner']);

        return view('audit.findings.show', compact('finding'));
    }

    public function createCapa(Request $request, AuditFinding $finding): RedirectResponse
    {
        abort_unless($finding->organization_id === $request->user()->organization_id, 403);

        if ($finding->capaCase) {
            return redirect()->route('capa.show', $finding->capaCase);
        }

        $capa = $this->auditCapa->createCapaFromFinding($finding, $request->user());

        return redirect()->route('capa.show', $capa)->with('status', 'CAPA berhasil dibuat dari temuan audit.');
    }
}
