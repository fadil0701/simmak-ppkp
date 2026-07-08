<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginAttempt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
            'auth_provider' => ['nullable', 'in:local,ldap'],
        ]);

        $authProvider = $credentials['auth_provider'] ?? 'local';
        $identifier = $credentials['email'];

        $userQuery = \App\Models\User::query()
            ->where('is_active', true)
            ->where(function ($query) use ($identifier) {
                $query->where('email', $identifier)
                    ->orWhere('username', $identifier);
            })
            ->where('auth_provider', $authProvider);

        $user = $userQuery->first();

        $successful = false;

        if ($user && $authProvider === 'local' && Auth::attempt([
            'email' => $user->email,
            'password' => $credentials['password'],
        ], $request->boolean('remember'))) {
            $successful = true;
        }

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
                'email' => __('Kredensial tidak valid atau metode login tidak sesuai.'),
            ]);
        }

        $request->session()->regenerate();
        $user->update(['last_login_at' => now()]);

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
