<?php

namespace App\Http\Controllers;

use App\Models\RiskCategory;
use App\Models\RiskItem;
use App\Models\RiskMitigationPlan;
use App\Models\RiskRegister;
use App\Services\RiskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RiskController extends Controller
{
    public function __construct(private RiskService $riskService) {}

    public function index(Request $request): View
    {
        $orgId = $request->user()->organization_id;

        $registers = RiskRegister::forOrganization($orgId)
            ->with(['clinic', 'owner'])
            ->withCount('items')
            ->orderByDesc('created_at')
            ->get();

        $query = RiskItem::forOrganization($orgId)
            ->with(['category', 'clinic', 'register', 'owner'])
            ->orderByDesc('inherent_score');

        if ($request->filled('level')) {
            $query->where('risk_level', $request->string('level'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $items = $query->paginate(15)->withQueryString();
        $allItems = RiskItem::forOrganization($orgId)->get(['inherent_likelihood', 'inherent_impact']);
        $heatmap = $this->riskService->buildHeatmap($allItems);

        $stats = [
            'total' => RiskItem::forOrganization($orgId)->count(),
            'extreme' => RiskItem::forOrganization($orgId)->where('risk_level', 'extreme')->count(),
            'high' => RiskItem::forOrganization($orgId)->where('risk_level', 'high')->count(),
            'medium' => RiskItem::forOrganization($orgId)->where('risk_level', 'medium')->count(),
            'low' => RiskItem::forOrganization($orgId)->where('risk_level', 'low')->count(),
            'active_registers' => $registers->where('status', 'active')->count(),
        ];

        return view('risk.index', compact('registers', 'items', 'heatmap', 'stats'));
    }

    public function show(Request $request, RiskItem $risk): View
    {
        abort_unless($request->user()->belongsToOrganization($risk->organization_id), 403);
        $risk->load(['category', 'clinic', 'register', 'owner', 'mitigationPlans.owner']);

        return view('risk.show', compact('risk'));
    }

    public function storeMitigation(Request $request, RiskItem $risk): RedirectResponse
    {
        abort_unless($request->user()->belongsToOrganization($risk->organization_id), 403);

        $validated = $request->validate([
            'plan_description' => ['required', 'string'],
            'target_date' => ['nullable', 'date'],
            'status' => ['required', 'in:planned,in_progress,completed,cancelled'],
        ]);

        RiskMitigationPlan::create([
            'organization_id' => $risk->organization_id,
            'risk_item_id' => $risk->id,
            'plan_description' => $validated['plan_description'],
            'status' => $validated['status'],
            'target_date' => $validated['target_date'] ?? null,
            'owner_id' => $request->user()->id,
        ]);

        if (in_array($risk->status, ['identified', 'assessed'])) {
            $risk->update(['status' => 'mitigated']);
        }

        return back()->with('status', 'Rencana mitigasi risiko berhasil ditambahkan.');
    }

    public function create(Request $request): View
    {
        $orgId = $request->user()->organization_id;

        return view('risk.create', [
            'registers' => RiskRegister::forOrganization($orgId)->where('status', 'active')->get(),
            'categories' => RiskCategory::forOrganization($orgId)->where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $orgId = $user->organization_id;

        $validated = $request->validate([
            'risk_register_id' => ['required', 'uuid', Rule::exists('risk_registers', 'id')->where('organization_id', $orgId)],
            'risk_category_id' => ['required', 'uuid', Rule::exists('risk_categories', 'id')->where('organization_id', $orgId)],
            'title' => ['required', 'string', 'max:255'],
            'risk_description' => ['nullable', 'string'],
            'inherent_likelihood' => ['required', 'integer', 'min:1', 'max:5'],
            'inherent_impact' => ['required', 'integer', 'min:1', 'max:5'],
            'residual_likelihood' => ['required', 'integer', 'min:1', 'max:5'],
            'residual_impact' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        $register = RiskRegister::findOrFail($validated['risk_register_id']);
        $inherentScore = $this->riskService->calculateScore($validated['inherent_likelihood'], $validated['inherent_impact']);
        $residualScore = $this->riskService->calculateScore($validated['residual_likelihood'], $validated['residual_impact']);

        $seq = RiskItem::forOrganization($orgId)->count() + 1;
        $risk = RiskItem::create([
            'organization_id' => $orgId,
            'risk_register_id' => $register->id,
            'risk_category_id' => $validated['risk_category_id'],
            'clinic_id' => $register->clinic_id,
            'risk_code' => sprintf('RSK-%s-%04d', now()->format('Y'), $seq),
            'title' => $validated['title'],
            'risk_description' => $validated['risk_description'] ?? null,
            'inherent_likelihood' => $validated['inherent_likelihood'],
            'inherent_impact' => $validated['inherent_impact'],
            'inherent_score' => $inherentScore,
            'residual_likelihood' => $validated['residual_likelihood'],
            'residual_impact' => $validated['residual_impact'],
            'residual_score' => $residualScore,
            'risk_level' => $this->riskService->riskLevel($inherentScore),
            'status' => 'identified',
            'owner_id' => $user->id,
            'review_date' => now()->addMonths(3)->toDateString(),
            'created_by' => $user->id,
        ]);

        return redirect()->route('risk.show', $risk)->with('status', 'Risiko berhasil dicatat.');
    }
}
