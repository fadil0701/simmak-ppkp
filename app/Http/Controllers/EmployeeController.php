<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\Employee;
use App\Models\Position;
use App\Services\UserClinicScopeSynchronizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function __construct(private readonly UserClinicScopeSynchronizer $clinicScopes) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $orgId = $user->organization_id;

        $query = Employee::query()
            ->with(['clinic', 'unit', 'positionMaster', 'clinicAssignments.clinic'])
            ->orderBy('full_name');

        if (! $user->isSuperAdmin()) {
            $query->where('organization_id', $orgId);
        }

        if ($request->filled('clinic_id')) {
            $clinicId = $request->string('clinic_id')->toString();
            $query->where(function ($q) use ($clinicId) {
                $q->where('clinic_id', $clinicId)
                    ->orWhereHas('clinicAssignments', fn ($assignment) => $assignment->where('clinic_id', $clinicId));
            });
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
            'positions' => $this->positions($request),
            'scopedClinicIds' => array_values(array_filter((array) old('clinic_ids', []))),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $orgId = $request->user()->organization_id;

        $validated = $this->validated($request, $orgId);

        $employee = Employee::create(array_merge($validated['attributes'], [
            'organization_id' => $orgId,
        ]));

        $employee->syncClinicAssignments(
            $validated['clinic_ids'],
            $validated['primary_clinic_id'],
        );

        $this->clinicScopes->syncForEmployee($employee);

        return redirect()->route('employees.index')->with('status', 'Data pegawai berhasil ditambahkan.');
    }

    public function edit(Request $request, Employee $employee): View
    {
        abort_unless($request->user()->belongsToOrganization($employee->organization_id), 403);

        $employee->load('users:id,employee_id,name,username');

        return view('employees.form', [
            'employee' => $employee,
            'clinics' => Clinic::forUserClinics($request->user())->orderBy('code')->get(),
            'positions' => $this->positions($request),
            'scopedClinicIds' => $request->old('clinic_ids') !== null
                ? array_values(array_filter((array) $request->old('clinic_ids')))
                : $employee->clinicScopeIds(),
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

        $employee->update($validated['attributes']);

        $employee->syncClinicAssignments(
            $validated['clinic_ids'],
            $validated['primary_clinic_id'],
        );

        $this->clinicScopes->syncForEmployee($employee);

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
            'clinic_ids' => ['nullable', 'array'],
            'clinic_ids.*' => ['uuid', Rule::exists('clinics', 'id')->where('organization_id', $orgId)],
            // Kompatibilitas form/API lama: satu klinik tunggal.
            'clinic_id' => ['nullable', 'uuid', Rule::exists('clinics', 'id')->where('organization_id', $orgId)],
            'primary_clinic_id' => ['nullable', 'uuid', Rule::exists('clinics', 'id')->where('organization_id', $orgId)],
            'is_active' => ['nullable', 'boolean'],
        ], [], [
            'clinic_ids' => 'klinik yang dipegang',
            'primary_clinic_id' => 'klinik utama',
        ]);

        $clinicIds = array_values(array_unique(array_filter((array) ($validated['clinic_ids'] ?? []))));
        $primaryClinicId = $validated['primary_clinic_id'] ?? $validated['clinic_id'] ?? null;

        // Subunit tidak lagi dipilih pada form pegawai; klinik adalah satuan
        // kerja terkecil yang dipegang. Kolom lama `employees.unit_id` tetap
        // ada sebagai cermin dan dibiarkan kosong.
        if ($primaryClinicId !== null && ! in_array($primaryClinicId, $clinicIds, true)) {
            array_unshift($clinicIds, $primaryClinicId);
            $clinicIds = array_values(array_unique($clinicIds));
        }

        $positionName = isset($validated['position_id'])
            ? Position::where('organization_id', $orgId)->where('id', $validated['position_id'])->value('name')
            : null;

        return [
            'attributes' => [
                'employee_number' => $validated['employee_number'],
                'full_name' => $validated['full_name'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'position_id' => $validated['position_id'] ?? null,
                // Disalin ke kolom teks lama agar data historis tetap terbaca.
                'position' => $positionName,
                'clinic_id' => $primaryClinicId,
                'unit_id' => null,
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ],
            'clinic_ids' => $clinicIds,
            'primary_clinic_id' => $primaryClinicId,
        ];
    }
}
