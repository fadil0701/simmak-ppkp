<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\Employee;
use App\Models\User;
use App\Services\UserClinicScopeSynchronizer;
use App\Services\UserScopeSynchronizer;
use App\Support\SimmakRoles;
use App\Support\SimmakScopes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(
        private readonly UserClinicScopeSynchronizer $clinicScopes,
        private readonly UserScopeSynchronizer $userScopes,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $orgId = $user->organization_id;

        $query = User::query()
            ->with(['defaultClinic', 'roles'])
            ->orderBy('name');

        // Administrator sistem melihat seluruh pengguna lintas organisasi.
        if (! $user->isSuperAdmin()) {
            $query->where('organization_id', $orgId);
        }

        if ($request->filled('q')) {
            $term = '%'.$request->string('q').'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('username', 'like', $term)
                    ->orWhere('email', 'like', $term);
            });
        }

        if ($request->filled('role')) {
            $role = $request->string('role');
            $query->whereHas('roles', fn ($q) => $q->where('name', $role));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->string('status') === 'active');
        }

        $users = $query->paginate(15)->withQueryString();

        return view('users.index', [
            'users' => $users,
            'roles' => Role::where('guard_name', 'web')
                ->whereIn('name', SimmakRoles::assignableNames())
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('users.create', array_merge($this->formData($request->user()), [
            'prefilledEmployeeId' => $request->string('employee_id')->toString() ?: null,
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $request->user();
        $orgId = $actor->organization_id;

        $validated = $request->validate([
            'employee_id' => ['nullable', 'uuid', Rule::exists('employees', 'id')->where('organization_id', $orgId)],
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:80', Rule::unique('users', 'username')],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
            'default_clinic_id' => ['nullable', 'uuid', Rule::exists('clinics', 'id')->where('organization_id', $orgId)],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['string', Rule::in(SimmakRoles::assignableNames())],
            // Kompatibilitas form/tautan lama yang masih mengirim satu peran.
            'role' => ['nullable', 'string', Rule::in(SimmakRoles::assignableNames())],
            'clinic_ids' => ['nullable', 'array'],
            'clinic_ids.*' => ['uuid', Rule::exists('clinics', 'id')->where('organization_id', $orgId)],
            'scope_names' => ['nullable', 'array'],
            'scope_names.*' => ['string', Rule::in(SimmakScopes::assignableNames())],
            'is_active' => ['nullable', 'boolean'],
        ], [], $this->attributeNames());

        $roleNames = $this->resolveRoleNames($validated);
        $clinicIds = $this->resolveClinicIds($validated);
        $employee = isset($validated['employee_id']) ? Employee::find($validated['employee_id']) : null;

        $newUser = User::create([
            'organization_id' => $orgId,
            'employee_id' => $validated['employee_id'] ?? null,
            'default_clinic_id' => $employee?->primaryClinicId() ?? ($validated['default_clinic_id'] ?? null),
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'auth_provider' => 'local',
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'email_verified_at' => now(),
        ]);

        $newUser->syncRoles($roleNames);
        $this->syncClinicScope($newUser, $employee, $clinicIds, $validated['default_clinic_id'] ?? null);
        $this->userScopes->sync($newUser, $validated['scope_names'] ?? [], $actor);

        return redirect()->route('users.index')->with('status', 'Pengguna berhasil ditambahkan.');
    }

    public function show(Request $request, User $user): View
    {
        abort_unless($request->user()->belongsToOrganization($user->organization_id), 403);

        $user->load(['defaultClinic', 'roles', 'clinics']);

        return view('users.show', [
            'user' => $user,
            'scopeClinics' => $user->clinics()->orderBy('name')->get(),
        ]);
    }

    public function edit(Request $request, User $user): View
    {
        abort_unless($request->user()->belongsToOrganization($user->organization_id), 403);

        return view('users.edit', array_merge(
            $this->formData($request->user(), $user),
            [
                'user' => $user,
                'currentRoles' => $user->getRoleNames()->all(),
                'currentScopes' => $user->scopeNames(),
                'scopedClinicIds' => $user->clinics()->pluck('clinics.id')->all(),
                'derivedClinicIds' => $user->employee?->clinicScopeIds() ?? [],
            ]
        ));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor->belongsToOrganization($user->organization_id), 403);

        $orgId = $actor->isSuperAdmin() ? $user->organization_id : $actor->organization_id;

        $validated = $request->validate([
            'employee_id' => ['nullable', 'uuid', Rule::exists('employees', 'id')->where('organization_id', $orgId)],
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:80', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'default_clinic_id' => ['nullable', 'uuid', Rule::exists('clinics', 'id')->where('organization_id', $orgId)],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['string', Rule::in(SimmakRoles::assignableNames())],
            // Kompatibilitas form/tautan lama yang masih mengirim satu peran.
            'role' => ['nullable', 'string', Rule::in(SimmakRoles::assignableNames())],
            'clinic_ids' => ['nullable', 'array'],
            'clinic_ids.*' => ['uuid', Rule::exists('clinics', 'id')->where('organization_id', $orgId)],
            'scope_names' => ['nullable', 'array'],
            'scope_names.*' => ['string', Rule::in(SimmakScopes::assignableNames())],
            'is_active' => ['nullable', 'boolean'],
        ], [], $this->attributeNames());

        $roleNames = $this->resolveRoleNames($validated);
        $clinicIds = $this->resolveClinicIds($validated);
        $employee = isset($validated['employee_id']) ? Employee::find($validated['employee_id']) : null;

        $data = [
            'employee_id' => $validated['employee_id'] ?? null,
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'default_clinic_id' => $employee?->primaryClinicId() ?? ($validated['default_clinic_id'] ?? null),
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ];

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        $user->syncRoles($roleNames);
        $this->syncClinicScope($user, $employee, $clinicIds, $validated['default_clinic_id'] ?? null);
        $this->userScopes->sync($user, $validated['scope_names'] ?? [], $actor);

        return redirect()->route('users.show', $user)->with('status', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();

        abort_unless($actor->belongsToOrganization($user->organization_id), 403);
        abort_if($actor->id === $user->id, 403, 'Anda tidak dapat menghapus akun sendiri.');

        $user->delete();

        return redirect()->route('users.index')->with('status', 'Pengguna berhasil dihapus.');
    }

    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();

        abort_unless($actor->belongsToOrganization($user->organization_id), 403);
        abort_if($actor->id === $user->id, 403, 'Anda tidak dapat menonaktifkan akun sendiri.');

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('status', $user->is_active
            ? 'Pengguna diaktifkan.'
            : 'Pengguna dinonaktifkan.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->belongsToOrganization($user->organization_id), 403);

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [], ['password' => 'kata sandi']);

        $user->update(['password' => Hash::make($validated['password'])]);

        return back()->with('status', 'Kata sandi pengguna berhasil direset.');
    }

    /**
     * @return array{clinics: Collection, roles: Collection, employees: Collection}
     */
    private function formData(User $actor, ?User $target = null): array
    {
        $roleNames = SimmakRoles::assignableNames();

        // Peran lama milik akun tetap ditampilkan agar tidak hilang saat disunting.
        foreach ($target?->getRoleNames()->all() ?? [] as $currentRole) {
            if (! in_array($currentRole, $roleNames, true)) {
                $roleNames[] = $currentRole;
            }
        }

        return [
            'clinics' => Clinic::forOrganization($actor->organization_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'roles' => Role::where('guard_name', 'web')
                ->whereIn('name', $roleNames)
                ->get()
                ->sortBy(function (Role $role) use ($roleNames) {
                    $index = array_search($role->name, $roleNames, true);

                    return $index === false ? 99 : $index;
                }),
            'employees' => Employee::where('organization_id', $actor->organization_id)
                ->where('is_active', true)
                ->with('clinicAssignments')
                ->orderBy('full_name')
                ->get(),
            'scopes' => SimmakScopes::labels(),
        ];
    }

    /**
     * Peran yang dipilih: `role_ids[]` (multi) dengan fallback `role` tunggal.
     *
     * @return array<int, string>
     */
    private function resolveRoleNames(array $validated): array
    {
        $roleNames = array_values(array_unique(array_filter((array) ($validated['role_ids'] ?? []))));

        if ($roleNames === [] && ! empty($validated['role'])) {
            $roleNames = [$validated['role']];
        }

        if ($roleNames === []) {
            throw ValidationException::withMessages([
                'role_ids' => 'Pilih minimal satu peran untuk pengguna ini.',
            ]);
        }

        return $roleNames;
    }

    private function attributeNames(): array
    {
        return [
            'employee_id' => 'data pegawai',
            'name' => 'nama',
            'username' => 'username',
            'email' => 'surel',
            'password' => 'kata sandi',
            'role' => 'peran',
            'role_ids' => 'peran',
            'default_clinic_id' => 'klinik default',
            'clinic_ids' => 'akses klinik',
        ];
    }

    /**
     * Klinik yang di-assign; bila kosong, pakai klinik default sebagai scope.
     *
     * @return array<int, string>
     */
    private function resolveClinicIds(array $validated): array
    {
        $clinicIds = $validated['clinic_ids'] ?? [];

        if (empty($clinicIds) && ! empty($validated['default_clinic_id'])) {
            $clinicIds = [$validated['default_clinic_id']];
        }

        return array_values(array_unique($clinicIds));
    }

    /**
     * Akses klinik: mengikuti master pegawai bila akun terhubung ke pegawai,
     * selain itu memakai pilihan manual pada form.
     *
     * @param  array<int, string>  $clinicIds
     */
    private function syncClinicScope(User $user, ?Employee $employee, array $clinicIds, ?string $defaultClinicId): void
    {
        if ($employee !== null) {
            $this->clinicScopes->syncForEmployee($employee);

            return;
        }

        $this->clinicScopes->syncScopes($user, $clinicIds, $defaultClinicId);
    }
}
