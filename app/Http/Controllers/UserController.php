<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
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
            'roles' => Role::where('guard_name', 'web')->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('users.create', $this->formData($request->user()));
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $request->user();
        $orgId = $actor->organization_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:80', Rule::unique('users', 'username')],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
            'default_clinic_id' => ['nullable', 'uuid', Rule::exists('clinics', 'id')->where('organization_id', $orgId)],
            'role' => ['required', 'string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'clinic_ids' => ['nullable', 'array'],
            'clinic_ids.*' => ['uuid', Rule::exists('clinics', 'id')->where('organization_id', $orgId)],
            'is_active' => ['nullable', 'boolean'],
        ], [], $this->attributeNames());

        $clinicIds = $this->resolveClinicIds($validated);

        $newUser = User::create([
            'organization_id' => $orgId,
            'default_clinic_id' => $validated['default_clinic_id'] ?? null,
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'auth_provider' => 'local',
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'email_verified_at' => now(),
        ]);

        $newUser->assignRole($validated['role']);
        $this->syncClinicScopes($newUser, $clinicIds, $validated['default_clinic_id'] ?? null);

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
            $this->formData($request->user()),
            [
                'user' => $user,
                'currentRole' => $user->getRoleNames()->first(),
                'scopedClinicIds' => $user->clinics()->pluck('clinics.id')->all(),
            ]
        ));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor->belongsToOrganization($user->organization_id), 403);

        $orgId = $actor->isSuperAdmin() ? $user->organization_id : $actor->organization_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:80', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'default_clinic_id' => ['nullable', 'uuid', Rule::exists('clinics', 'id')->where('organization_id', $orgId)],
            'role' => ['required', 'string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'clinic_ids' => ['nullable', 'array'],
            'clinic_ids.*' => ['uuid', Rule::exists('clinics', 'id')->where('organization_id', $orgId)],
            'is_active' => ['nullable', 'boolean'],
        ], [], $this->attributeNames());

        $clinicIds = $this->resolveClinicIds($validated);

        $data = [
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'default_clinic_id' => $validated['default_clinic_id'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ];

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        $user->syncRoles([$validated['role']]);
        $this->syncClinicScopes($user, $clinicIds, $validated['default_clinic_id'] ?? null);

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
     * @return array{clinics: Collection, roles: Collection}
     */
    private function formData(User $actor): array
    {
        return [
            'clinics' => Clinic::forOrganization($actor->organization_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'roles' => Role::where('guard_name', 'web')->orderBy('name')->get(),
        ];
    }

    private function attributeNames(): array
    {
        return [
            'name' => 'nama',
            'username' => 'username',
            'email' => 'surel',
            'password' => 'kata sandi',
            'role' => 'peran',
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
     * @param  array<int, string>  $clinicIds
     */
    private function syncClinicScopes(User $user, array $clinicIds, ?string $defaultClinicId): void
    {
        $sync = collect($clinicIds)
            ->mapWithKeys(fn (string $id) => [
                $id => ['is_default' => $id === $defaultClinicId],
            ])
            ->all();

        $user->clinics()->sync($sync);
    }
}
