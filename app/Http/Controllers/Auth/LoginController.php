<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginAttempt;
use App\Models\User;
use App\Support\SimmakMenu;
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
            return redirect(SimmakMenu::homeRouteFor(Auth::user()));
        }

        return view('auth.login', [
            'redirectTo' => session()->get('url.intended'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $authProvider = 'local';
        $identifier = $credentials['email'];

        $user = User::query()
            ->where('is_active', true)
            ->where('auth_provider', $authProvider)
            ->where(function ($query) use ($identifier) {
                $query->where('email', $identifier)
                    ->orWhere('username', $identifier);
            })
            ->first();

        $successful = false;

        if ($user && Auth::attempt([
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
            // Pesan yang lebih membantu: Bedakan "akun tidak ada" dari "sandi salah"
            // agar pengguna tidak terus mencoba dan terkena rate limit.
            $knownUser = User::query()
                ->where('email', $identifier)
                ->orWhere('username', $identifier)
                ->exists();

            $message = $knownUser
                ? __('Sandi salah. Silakan coba lagi.')
                : __('Akun tidak ditemukan. Pastikan email/username benar atau hubungi administrator.');

            throw ValidationException::withMessages(['email' => $message]);
        }

        $request->session()->regenerate();
        $user->update(['last_login_at' => now()]);

        return redirect()->intended(SimmakMenu::homeRouteFor($user));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
