<?php

namespace App\Http\Controllers;

use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PositionController extends Controller
{
    public const CATEGORIES = [
        'struktural' => 'Jabatan Struktural',
        'fungsional' => 'Jabatan Fungsional',
        'teknis' => 'Jabatan Teknis',
        'administrasi' => 'Jabatan Administrasi',
    ];

    public function index(Request $request): View
    {
        $user = $request->user();
        $orgId = $user->organization_id;

        $query = Position::query()->withCount('employees');

        if (! $user->isSuperAdmin()) {
            $query->where('organization_id', $orgId);
        }

        if ($request->filled('q')) {
            $term = '%'.$request->string('q').'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)->orWhere('code', 'like', $term);
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->string('status') === 'active');
        }

        $positions = $query->orderBy('category')->orderBy('name')->paginate(15)->withQueryString();

        return view('positions.index', compact('positions'));
    }

    public function create(Request $request): View
    {
        return view('positions.form', ['position' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $orgId = $request->user()->organization_id;

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('positions', 'code')->where('organization_id', $orgId)],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::in(array_keys(self::CATEGORIES))],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Position::create(array_merge($validated, [
            'organization_id' => $orgId,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]));

        return redirect()->route('positions.index')->with('status', 'Master jabatan berhasil ditambahkan.');
    }

    public function edit(Request $request, Position $position): View
    {
        abort_unless($request->user()->belongsToOrganization($position->organization_id), 403);

        return view('positions.form', compact('position'));
    }

    public function update(Request $request, Position $position): RedirectResponse
    {
        abort_unless($request->user()->belongsToOrganization($position->organization_id), 403);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('positions', 'code')->where('organization_id', $position->organization_id)->ignore($position->id)],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::in(array_keys(self::CATEGORIES))],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $position->update(array_merge($validated, [
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]));

        return redirect()->route('positions.index')->with('status', 'Master jabatan berhasil diperbarui.');
    }

    public function destroy(Request $request, Position $position): RedirectResponse
    {
        abort_unless($request->user()->belongsToOrganization($position->organization_id), 403);

        if ($position->employees()->exists()) {
            return back()->with('error', 'Jabatan tidak dapat dihapus karena masih dipakai pegawai.');
        }

        $position->delete();

        return redirect()->route('positions.index')->with('status', 'Master jabatan berhasil dihapus.');
    }
}
