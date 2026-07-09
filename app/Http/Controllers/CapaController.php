<?php

namespace App\Http\Controllers;

use App\Models\CapaCase;
use App\Models\CapaRootCauseAnalysis;
use App\Models\CapaVerification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CapaController extends Controller
{
    public function index(Request $request): View
    {
        $orgId = $request->user()->organization_id;

        $query = CapaCase::forOrganization($orgId)
            ->with(['clinic', 'owner', 'sourceFinding'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $cases = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => CapaCase::forOrganization($orgId)->count(),
            'open' => CapaCase::forOrganization($orgId)->whereIn('status', ['open', 'analysis'])->count(),
            'action' => CapaCase::forOrganization($orgId)->where('status', 'action')->count(),
            'closed' => CapaCase::forOrganization($orgId)->where('status', 'closed')->count(),
            'overdue' => CapaCase::forOrganization($orgId)
                ->whereNotIn('status', ['closed'])
                ->where('target_close_date', '<', now()->toDateString())
                ->count(),
        ];

        return view('capa.index', compact('cases', 'stats'));
    }

    public function show(Request $request, CapaCase $capa): View
    {
        abort_unless($capa->organization_id === $request->user()->organization_id, 403);

        $capa->load(['clinic', 'owner', 'sourceFinding.schedule', 'actions.assignee', 'rca.creator', 'verifications.verifier']);

        return view('capa.show', compact('capa'));
    }

    public function storeRca(Request $request, CapaCase $capa): RedirectResponse
    {
        abort_unless($capa->organization_id === $request->user()->organization_id, 403);

        $validated = $request->validate([
            'method' => ['required', 'in:five_why,fishbone'],
            'root_cause' => ['required', 'string'],
            'whys' => ['required_if:method,five_why', 'array', 'min:1'],
            'whys.*.question' => ['required_with:whys', 'string'],
            'whys.*.answer' => ['required_with:whys', 'string'],
            'categories' => ['required_if:method,fishbone', 'array', 'min:1'],
            'categories.*.name' => ['required_with:categories', 'string'],
            'categories.*.causes' => ['required_with:categories', 'string'],
        ]);

        $content = match ($validated['method']) {
            'five_why' => [
                'whys' => $validated['whys'],
                'root_cause' => $validated['root_cause'],
            ],
            'fishbone' => [
                'categories' => collect($validated['categories'])->map(fn ($cat) => [
                    'name' => $cat['name'],
                    'causes' => array_filter(array_map('trim', explode("\n", $cat['causes']))),
                ])->all(),
                'root_cause' => $validated['root_cause'],
            ],
        };

        CapaRootCauseAnalysis::updateOrCreate(
            ['capa_case_id' => $capa->id, 'method' => $validated['method']],
            [
                'organization_id' => $capa->organization_id,
                'content' => $content,
                'created_by' => $request->user()->id,
            ]
        );

        if ($capa->status === 'open') {
            $capa->update(['status' => 'analysis', 'updated_by' => $request->user()->id]);
        }

        return back()->with('status', 'Analisis akar masalah berhasil disimpan.');
    }

    public function storeVerification(Request $request, CapaCase $capa): RedirectResponse
    {
        abort_unless($capa->organization_id === $request->user()->organization_id, 403);

        $validated = $request->validate([
            'verification_date' => ['required', 'date'],
            'result' => ['required', 'in:effective,not_effective,pending'],
            'notes' => ['nullable', 'string'],
            'is_effective' => ['nullable', 'boolean'],
        ]);

        CapaVerification::create([
            'organization_id' => $capa->organization_id,
            'capa_case_id' => $capa->id,
            'verification_date' => $validated['verification_date'],
            'result' => $validated['result'],
            'notes' => $validated['notes'] ?? null,
            'is_effective' => $validated['result'] === 'effective',
            'verified_by' => $request->user()->id,
        ]);

        if ($validated['result'] === 'effective') {
            $capa->update([
                'status' => 'closed',
                'closed_at' => now()->toDateString(),
                'updated_by' => $request->user()->id,
            ]);
        } elseif ($capa->status !== 'verification') {
            $capa->update(['status' => 'verification', 'updated_by' => $request->user()->id]);
        }

        return back()->with('status', 'Verifikasi CAPA berhasil dicatat.');
    }
}
