<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LoginAttempt;
use App\Models\User;
use App\Services\AuditTrailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private AuditTrailService $auditTrail) {}

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
            'auth_provider' => ['nullable', 'in:local,ldap'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $authProvider = $credentials['auth_provider'] ?? 'local';
        $identifier = $credentials['email'];

        $user = User::query()
            ->where('is_active', true)
            ->where(function ($query) use ($identifier) {
                $query->where('email', $identifier)
                    ->orWhere('username', $identifier);
            })
            ->where('auth_provider', $authProvider)
            ->first();

        $successful = $user
            && $authProvider === 'local'
            && $user->password
            && Hash::check($credentials['password'], $user->password);

        LoginAttempt::create([
            'id' => (string) Str::uuid(),
            'identifier' => $identifier,
            'auth_provider' => $authProvider,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'successful' => $successful,
            'attempted_at' => now(),
        ]);

        if (! $successful) {
            throw ValidationException::withMessages([
                'email' => ['Kredensial tidak valid atau metode login tidak sesuai.'],
            ]);
        }

        $user->update(['last_login_at' => now()]);
        $token = $user->createToken($credentials['device_name'] ?? 'simmak-spa')->plainTextToken;

        $this->auditTrail->log('auth.login', $user, metadata: ['auth_provider' => $authProvider]);

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->formatUser($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->formatUser($request->user()->load(['organization', 'defaultClinic', 'roles'])),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->currentAccessToken()?->delete();

        $this->auditTrail->log('auth.logout', $user);

        return response()->json(['message' => 'Berhasil keluar.']);
    }

    private function formatUser(User $user): array
    {
        $user->loadMissing(['organization', 'defaultClinic', 'clinics', 'roles']);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'auth_provider' => $user->auth_provider,
            'organization' => $user->organization?->only(['id', 'code', 'name']),
            'default_clinic' => $user->defaultClinic?->only(['id', 'code', 'name', 'clinic_type']),
            'clinics' => $user->clinics->map->only(['id', 'code', 'name', 'clinic_type']),
            'roles' => $user->getRoleNames()->values(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values(),
        ];
    }
}
