<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $orgId = $request->user()->organization_id;

        $users = User::query()
            ->where('organization_id', $orgId)
            ->with(['defaultClinic', 'roles'])
            ->orderBy('name')
            ->paginate(15);

        return view('users.index', compact('users'));
    }

    public function create(Request $request): View
    {
        $orgId = $request->user()->organization_id;

        return view('users.create', [
            'clinics' => Clinic::forOrganization($orgId)->where('is_active', true)->orderBy('name')->get(),
            'roles' => Role::where('guard_name', 'web')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $orgId = $user->organization_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:80', Rule::unique('users', 'username')],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
            'default_clinic_id' => ['nullable', 'uuid', Rule::exists('clinics', 'id')->where('organization_id', $orgId)],
            'role' => ['required', 'string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
        ]);

        $newUser = User::create([
            'organization_id' => $orgId,
            'default_clinic_id' => $validated['default_clinic_id'] ?? null,
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'auth_provider' => 'local',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $newUser->assignRole($validated['role']);

        if ($validated['default_clinic_id'] ?? null) {
            $newUser->clinics()->sync([
                $validated['default_clinic_id'] => ['is_default' => true],
            ]);
        }

        return redirect()->route('users.index')->with('status', 'Pengguna berhasil ditambahkan.');
    }
}
