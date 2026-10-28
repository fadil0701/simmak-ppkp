<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Masuk ke SIMMAK PPKP — sistem manajemen mutu, audit internal, dan akreditasi klinik PPKP Provinsi DKI Jakarta.">
    <meta name="theme-color" content="#0b1a2b">
    <title>Masuk — SIMMAK PPKP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="min-h-screen bg-sim-surface text-sim-ink antialiased">
    <a href="#login-form" class="sim-skip-link">Langsung ke formulir masuk</a>

    <div class="grid min-h-screen lg:grid-cols-[minmax(0,1fr)_minmax(0,30rem)]">
        {{-- Panel identitas institusi --}}
        <aside class="relative hidden overflow-hidden bg-sim-navy-950 text-white lg:flex lg:flex-col lg:justify-between lg:p-12"
               aria-hidden="true">
            {{-- Mesh gradient: bukan linear 45° yang datar --}}
            <div class="pointer-events-none absolute inset-0"
                 style="background:
                    radial-gradient(60rem 40rem at 8% -10%, rgba(20,184,166,.28), transparent 60%),
                    radial-gradient(48rem 34rem at 110% 20%, rgba(37,99,235,.22), transparent 55%),
                    radial-gradient(40rem 40rem at 60% 120%, rgba(13,148,136,.18), transparent 60%);"></div>
            {{-- Tekstur grain halus --}}
            <div class="pointer-events-none absolute inset-0 opacity-[0.05] mix-blend-overlay"
                 style="background-image:url('data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%27120%27 height=%27120%27%3E%3Cfilter id=%27n%27%3E%3CfeTurbulence type=%27fractalNoise%27 baseFrequency=%270.9%27 numOctaves=%272%27/%3E%3C/filter%3E%3Crect width=%27100%25%27 height=%27100%25%27 filter=%27url(%23n)%27/%3E%3C/svg%3E');"></div>
            {{-- Garis aksen bawah --}}
            <div class="pointer-events-none absolute inset-x-0 bottom-0 h-px bg-gradient-to-r from-transparent via-sim-teal-500/60 to-transparent"></div>

            <div class="relative">
                <div class="flex items-center gap-3.5">
                    <div class="flex h-12 w-12 items-center justify-center rounded-[14px] bg-sim-teal-600 shadow-[0_10px_30px_-8px_rgba(13,148,136,.7)]">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
                        </svg>
                    </div>
                    <div class="leading-tight">
                        <div class="text-lg font-bold tracking-tight">SIMMAK&nbsp;PPKP</div>
                        <div class="text-[11px] uppercase tracking-[0.18em] text-white/55">PPKP Provinsi DKI Jakarta</div>
                    </div>
                </div>
            </div>

            <div class="relative max-w-lg">
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-sim-teal-500">Manajemen Mutu Terpadu</p>
                <h2 class="mt-4 text-[2.35rem] font-bold leading-[1.08] tracking-tight text-white text-balance">
                    Kelola mutu, audit, dan akreditasi klinik dalam satu sistem.
                </h2>
                <p class="mt-5 max-w-md text-sm leading-relaxed text-white/65 text-pretty">
                    Terintegrasi untuk manajemen dokumen, audit internal, tindak lanjut, manajemen risiko,
                    dan kesiapan akreditasi 16 klinik FKTP/FKTL.
                </p>

                <dl class="mt-9 grid grid-cols-3 gap-4 border-t border-white/10 pt-7">
                    <div>
                        <dt class="text-[11px] uppercase tracking-wider text-white/45">Klinik</dt>
                        <dd class="mt-1 font-mono text-2xl font-semibold tabular-nums text-white">16</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] uppercase tracking-wider text-white/45">Modul</dt>
                        <dd class="mt-1 font-mono text-2xl font-semibold tabular-nums text-white">7</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] uppercase tracking-wider text-white/45">Standar</dt>
                        <dd class="mt-1 font-mono text-2xl font-semibold tabular-nums text-white">FKTP</dd>
                    </div>
                </dl>
            </div>

            <div class="relative text-[11px] text-white/40">© {{ date('Y') }} Dinas Kesehatan Provinsi DKI Jakarta</div>
        </aside>

        {{-- Panel formulir --}}
        <main class="relative flex flex-col justify-center bg-sim-surface-alt px-6 py-12 sm:px-10">
            <div class="pointer-events-none absolute inset-0"
                 style="background: radial-gradient(40rem 30rem at 120% -10%, rgba(30,58,95,.07), transparent 55%);"></div>

            <div class="relative mx-auto w-full max-w-sm">
                <div class="mb-9 flex items-center gap-3 lg:hidden">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-sim-navy-900">
                        <svg class="h-5 w-5 text-sim-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
                        </svg>
                    </div>
                    <div class="leading-tight">
                        <div class="text-base font-bold tracking-tight text-sim-navy-900">SIMMAK PPKP</div>
                        <div class="text-[10px] uppercase tracking-[0.18em] text-sim-muted">PPKP DKI Jakarta</div>
                    </div>
                </div>

                <h1 class="text-2xl font-bold tracking-tight text-sim-navy-900">Masuk ke akun Anda</h1>
                <p class="mt-1.5 text-sm text-sim-muted">Gunakan kredensial yang diberikan administrator.</p>

                @if (! empty($redirectTo))
                    <p class="mt-3 rounded-lg border border-sim-teal-500/25 bg-sim-teal-50 px-3 py-2 text-xs text-sim-navy-800">
                        Silakan masuk untuk melanjutkan ke halaman yang diminta.
                    </p>
                @endif

                @if ($errors->any())
                    <div role="alert" class="mt-6 flex items-start gap-3 rounded-xl border border-sim-danger/25 bg-sim-danger-bg px-4 py-3">
                        <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-sim-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                        </svg>
                        <span class="text-sm font-medium text-sim-danger">{{ $errors->first() }}</span>
                    </div>
                @endif

                <form id="login-form" method="POST" action="{{ route('login') }}" class="mt-6 space-y-4"
                      x-data="{ submitting: false, showPassword: false }" @submit="submitting = true">
                    @csrf
                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-sim-ink">Email / Username</label>
                        <input id="email" type="text" name="email" value="{{ old('email') }}" required autofocus
                               autocomplete="username" spellcheck="false"
                               class="w-full rounded-lg border border-sim-border bg-sim-white px-3.5 py-2.5 text-sm text-sim-ink shadow-sim-inset transition-colors placeholder:text-sim-muted focus:border-sim-navy-800 focus:outline-none focus:ring-2 focus:ring-sim-navy-800/15"
                               placeholder="nama@ppkp.local">
                    </div>
                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium text-sim-ink">Password</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sim-muted" aria-hidden="true">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V7.5a4.5 4.5 0 10-9 0v3m-1.5 0h12A1.5 1.5 0 0119.5 12v7.5a1.5 1.5 0 01-1.5 1.5h-12a1.5 1.5 0 01-1.5-1.5V12a1.5 1.5 0 011.5-1.5z"/>
                                </svg>
                            </span>
                            <input id="password" :type="showPassword ? 'text' : 'password'" name="password" required
                                   autocomplete="current-password"
                                   class="w-full rounded-lg border border-sim-border bg-sim-white py-2.5 pl-10 pr-11 text-sm text-sim-ink shadow-sim-inset transition-colors focus:border-sim-navy-800 focus:outline-none focus:ring-2 focus:ring-sim-navy-800/15">
                            <button type="button"
                                    class="absolute inset-y-0 right-0 flex items-center px-3 text-sim-muted transition-colors hover:text-sim-navy-800 focus:outline-none focus:text-sim-navy-800"
                                    @click="showPassword = !showPassword"
                                    :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                                    :aria-pressed="showPassword.toString()">
                                <svg x-show="!showPassword" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4 12 4c4.638 0 8.573 3.508 9.963 8.687a1.013 1.013 0 010 .639C20.577 16.49 16.64 20 12 20c-4.638 0-8.573-3.508-9.964-8.678z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <svg x-show="showPassword" x-cloak class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <label class="flex cursor-pointer items-center gap-2.5 text-sm text-sim-muted">
                        <input type="checkbox" name="remember"
                               class="h-4 w-4 rounded border-sim-border text-sim-teal-600 focus:ring-sim-teal-600/30">
                        Ingat saya di perangkat ini
                    </label>

                    <button type="submit" :disabled="submitting"
                            class="group relative flex w-full items-center justify-center gap-2 rounded-lg bg-sim-navy-800 px-4 py-2.5 text-sm font-semibold text-white shadow-[0_10px_24px_-12px_rgba(18,38,63,.9)] transition-all duration-200 hover:bg-sim-navy-900 hover:shadow-[0_14px_30px_-12px_rgba(18,38,63,.95)] focus:outline-none focus:ring-2 focus:ring-sim-navy-800 focus:ring-offset-2 active:translate-y-px disabled:cursor-not-allowed disabled:opacity-70">
                        <svg x-show="!submitting" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"/>
                        </svg>
                        <svg x-show="submitting" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                        </svg>
                        <span x-text="submitting ? 'Memproses…' : 'Masuk'">Masuk</span>
                    </button>
                </form>

                <p class="mt-8 text-center text-[11px] text-sim-muted">
                    SIMMAK PPKP · Sistem Manajemen Mutu &amp; Akreditasi Klinik
                </p>
            </div>
        </main>
    </div>
</body>
</html>
