<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — SIMMAK PPKP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-navy-950">
    <div class="flex min-h-screen">
        <div class="hidden flex-1 flex-col justify-between bg-gradient-to-br from-navy-900 to-navy-950 p-10 text-white lg:flex">
            <div>
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-teal-600 text-xl font-bold">+</div>
                    <div>
                        <div class="text-xl font-bold">SIMMAK PPKP</div>
                        <div class="text-sm text-slate-400">Sistem Manajemen Mutu Akreditasi Klinik</div>
                    </div>
                </div>
            </div>
            <div>
                <h2 class="text-3xl font-bold leading-tight">Kelola mutu, audit, dan akreditasi<br>17 entitas PPKP DKI Jakarta</h2>
                <p class="mt-4 max-w-md text-slate-400">Platform terintegrasi untuk manajemen dokumen, audit internal, CAPA, risiko, dan kesiapan akreditasi FKTP/FKTL.</p>
            </div>
            <div class="text-xs text-slate-500">© {{ date('Y') }} PPKP Provinsi DKI Jakarta</div>
        </div>

        <div class="flex w-full flex-col justify-center bg-slate-50 px-6 py-12 lg:w-[480px] lg:shrink-0 lg:px-12">
            <div class="mx-auto w-full max-w-sm">
                <div class="mb-8 lg:hidden">
                    <div class="text-xl font-bold text-navy-900">SIMMAK PPKP</div>
                </div>
                <h1 class="text-2xl font-bold text-slate-900">Masuk ke akun Anda</h1>
                <p class="mt-1 text-sm text-slate-500">Gunakan kredensial yang diberikan administrator</p>

                @if ($errors->any())
                    <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
                    @csrf
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Email / Username</label>
                        <input type="text" name="email" value="{{ old('email') }}" required autofocus
                               class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-100">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Password</label>
                        <input type="password" name="password" required
                               class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-100">
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-teal-600">
                        Ingat saya
                    </label>
                    <button type="submit" class="w-full rounded-lg bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-800">
                        Masuk
                    </button>
                </form>

                @if (app()->environment('local'))
                    <div class="mt-8 rounded-lg border border-slate-200 bg-white p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Akun demo (password: <code class="text-teal-700">password</code>)</p>
                        <div class="mt-3 overflow-x-auto">
                            <table class="w-full text-left text-xs text-slate-600">
                                <thead>
                                    <tr class="border-b border-slate-100 text-slate-500">
                                        <th class="pb-2 pr-2 font-medium">Role</th>
                                        <th class="pb-2 font-medium">Email</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    <tr><td class="py-1.5 pr-2">Admin Utama</td><td class="py-1.5 font-mono text-[11px]">admin@simmak.ppkp.local</td></tr>
                                    <tr><td class="py-1.5 pr-2">Admin Mutu</td><td class="py-1.5 font-mono text-[11px]">mutu@simmak.ppkp.local</td></tr>
                                    <tr><td class="py-1.5 pr-2">Kepala Klinik FKTP</td><td class="py-1.5 font-mono text-[11px]">kepala@simmak.ppkp.local</td></tr>
                                    <tr><td class="py-1.5 pr-2">PJ Mutu</td><td class="py-1.5 font-mono text-[11px]">pjmutu@simmak.ppkp.local</td></tr>
                                    <tr><td class="py-1.5 pr-2">Tim Akreditasi FKTP</td><td class="py-1.5 font-mono text-[11px]">akreditasi@simmak.ppkp.local</td></tr>
                                    <tr><td class="py-1.5 pr-2">Auditor</td><td class="py-1.5 font-mono text-[11px]">auditor@simmak.ppkp.local</td></tr>
                                    <tr><td class="py-1.5 pr-2">Staff</td><td class="py-1.5 font-mono text-[11px]">staff@simmak.ppkp.local</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
