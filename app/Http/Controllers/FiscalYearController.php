<?php

namespace App\Http\Controllers;

use App\Models\FiscalYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FiscalYearController extends Controller
{
    public function index(Request $request): View
    {
        $orgId = $request->user()->organization_id;
        $years = FiscalYear::where('organization_id', $orgId)->orderByDesc('code')->get();

        return view('fiscal-years.index', compact('years'));
    }

    public function store(Request $request): RedirectResponse
    {
        $orgId = $request->user()->organization_id;

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        FiscalYear::firstOrCreate(
            ['organization_id' => $orgId, 'code' => $validated['code']],
            array_merge($validated, [
                'organization_id' => $orgId,
                'is_current' => false,
                'is_active' => true,
            ])
        );

        return back()->with('status', 'Tahun anggaran berhasil disimpan.');
    }

    public function destroy(Request $request, FiscalYear $fiscalYear): RedirectResponse
    {
        abort_unless($request->user()->belongsToOrganization($fiscalYear->organization_id), 403);

        if ($fiscalYear->auditPrograms()->exists()) {
            return back()->with('error', 'Tahun anggaran tidak dapat dihapus karena sudah dipakai program audit.');
        }

        $fiscalYear->delete();

        return back()->with('status', 'Tahun anggaran berhasil dihapus.');
    }
}
