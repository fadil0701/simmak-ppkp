<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — SIMMAK PPKP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-br from-teal-700 via-teal-800 to-slate-900">
    <div class="flex min-h-screen items-center justify-center p-4">
        <div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-2xl">
            <div class="mb-8 text-center">
                <div class="text-2xl font-bold text-teal-800">SIMMAK PPKP</div>
                <p class="mt-1 text-sm text-slate-500">Sistem Informasi Manajemen Mutu, Audit & Akreditasi</p>
                <p class="mt-1 text-xs text-slate-400">PPKP Provinsi DKI Jakarta</p>
            </div>

            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Email / Username</label>
                    <input type="text" name="email" value="{{ old('email') }}" required autofocus
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-200">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Password</label>
                    <input type="password" name="password" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-200">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Metode Login</label>
                    <select name="auth_provider"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-200">
                        <option value="local" @selected(old('auth_provider', 'local') === 'local')>Login Lokal</option>
                        <option value="ldap" @selected(old('auth_provider') === 'ldap')>LDAP / Active Directory</option>
                    </select>
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-teal-600">
                    Ingat saya
                </label>
                <button type="submit"
                        class="w-full rounded-lg bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-800">
                    Masuk
                </button>
            </form>
        </div>
    </div>
</body>
</html>
