<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $orgId = $user->organization_id;

        $query = Employee::query()
            ->with(['clinic', 'unit', 'positionMaster'])
            ->orderBy('full_name');

        if (! $user->isSuperAdmin()) {
            $query->where('organization_id', $orgId);
        }

        if ($request->filled('clinic_id')) {
            $query->where('clinic_id', $request->string('clinic_id'));
        }

        if ($request->filled('q')) {
            $term = '%'.$request->string('q').'%';
            $query->where(function ($q) use ($term) {
                $q->where('full_name', 'like', $term)
                    ->orWhere('employee_number', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('position', 'like', $term);
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->string('status') === 'active');
        }

        $employees = $query->paginate(15)->withQueryString();

        $clinics = Clinic::forUserClinics($user)->orderBy('code')->get();

        return view('employees.index', compact('employees', 'clinics'));
    }

    public function create(Request $request): View
    {
        return view('employees.form', [
            'employee' => null,
            'clinics' => Clinic::forUserClinics($request->user())->orderBy('code')->get(),
            'units' => collect(),
            'positions' => $this->positions($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $orgId = $request->user()->organization_id;

        $validated = $this->validated($request, $orgId);

        Employee::create(array_merge($validated, [
            'organization_id' => $orgId,
        ]));

        return redirect()->route('employees.index')->with('status', 'Data pegawai berhasil ditambahkan.');
    }

    public function edit(Request $request, Employee $employee): View
    {
        abort_unless($request->user()->belongsToOrganization($employee->organization_id), 403);

        return view('employees.form', [
            'employee' => $employee,
            'clinics' => Clinic::forUserClinics($request->user())->orderBy('code')->get(),
            'units' => $employee->clinic_id
                ? Unit::where('clinic_id', $employee->clinic_id)->orderBy('name')->get()
                : collect(),
            'positions' => $this->positions($request),
        ]);
    }

    /**
     * Master jabatan aktif milik organisasi pengguna.
     */
    private function positions(Request $request)
    {
        return Position::where('organization_id', $request->user()->organization_id)
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get();
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        abort_unless($request->user()->belongsToOrganization($employee->organization_id), 403);

        $validated = $this->validated($request, $employee->organization_id, $employee->id);

        $employee->update($validated);

        return redirect()->route('employees.index')->with('status', 'Data pegawai berhasil diperbarui.');
    }

    public function destroy(Request $request, Employee $employee): RedirectResponse
    {
        abort_unless($request->user()->belongsToOrganization($employee->organization_id), 403);

        if ($employee->users()->exists()) {
            return back()->with('error', 'Pegawai tidak dapat dihapus karena sudah terhubung dengan akun pengguna.');
        }

        $employee->delete();

        return redirect()->route('employees.index')->with('status', 'Data pegawai berhasil dihapus.');
    }

    private function validated(Request $request, string $orgId, ?string $ignoreId = null): array
    {
        $validated = $request->validate([
            'employee_number' => [
                'required', 'string', 'max:50',
                Rule::unique('employees', 'employee_number')
                    ->where('organization_id', $orgId)
                    ->ignore($ignoreId),
            ],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:320'],
            'phone' => ['nullable', 'string', 'max:50'],
            'position_id' => ['nullable', 'uuid', Rule::exists('positions', 'id')->where('organization_id', $orgId)],
            'clinic_id' => ['nullable', 'uuid', Rule::exists('clinics', 'id')->where('organization_id', $orgId)],
            'unit_id' => ['nullable', 'uuid', Rule::exists('units', 'id')],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $positionName = $validated['position_id'] ?? null
            ? Position::where('organization_id', $orgId)->where('id', $validated['position_id'])->value('name')
            : null;

        return [
            'employee_number' => $validated['employee_number'],
            'full_name' => $validated['full_name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'position_id' => $validated['position_id'] ?? null,
            // Disalin ke kolom teks lama agar data historis tetap terbaca.
            'position' => $positionName,
            'clinic_id' => $validated['clinic_id'] ?? null,
            'unit_id' => $validated['unit_id'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ];
    }
}
