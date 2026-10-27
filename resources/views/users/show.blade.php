@extends('layouts.simmak')

@section('title', $user->name.' — SIMMAK PPKP')
@section('page-title', 'Detail Pengguna')
@section('page-subtitle', 'Informasi akun, peran, dan akses klinik.')

@section('breadcrumb')
    <li class="flex items-center gap-1 text-sim-muted">
        <a href="{{ route('users.index') }}">Pengguna</a>
        <svg class="h-3.5 w-3.5 text-sim-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
        <span>{{ $user->name }}</span>
    </li>
@endsection

@section('content')
    <div class="sim-page-header">
        <div>
            <h2 class="sim-page-title">{{ $user->name }}</h2>
            <p class="sim-page-subtitle">
                {{ $user->getRoleNames()->map(fn ($roleName) => \App\Support\RoleLabel::for($roleName))->join(', ') ?: 'Tanpa peran' }}
                <span class="text-sim-mono text-xs text-sim-muted">({{ $user->username }})</span>
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <x-action href="{{ route('users.index') }}" icon="chevron-left" label="Kembali" variant="outline" />
            @can('users.manage')
                <x-action href="{{ route('users.edit', $user) }}" icon="pencil" label="Ubah" />
            @endcan
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="sim-card">
                <div class="sim-card-header"><h3 class="sim-card-title">Informasi Akun</h3></div>
                <div class="sim-card-body">
                    <dl class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-sim-muted">Nama</dt>
                            <dd class="mt-1 text-sm font-medium text-sim-ink">{{ $user->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-sim-muted">Username</dt>
                            <dd class="mt-1 text-sim-mono text-sm text-sim-ink">{{ $user->username ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-sim-muted">Surel</dt>
                            <dd class="mt-1 text-sm text-sim-ink">{{ $user->email }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-sim-muted">Klinik Default</dt>
                            <dd class="mt-1 text-sm text-sim-ink">{{ $user->defaultClinic?->name ?? 'Semua Klinik' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-sim-muted">Sumber Autentikasi</dt>
                            <dd class="mt-1 text-sm text-sim-ink uppercase">{{ $user->auth_provider }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-sim-muted">Masuk Terakhir</dt>
                            <dd class="mt-1 text-sm text-sim-ink">
                                {{ $user->last_login_at?->translatedFormat('d F Y, H:i') ?? 'Belum pernah masuk' }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="sim-card">
                <div class="sim-card-header"><h3 class="sim-card-title">Peran &amp; Scope Sistem</h3></div>
                <div class="sim-card-body space-y-4">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-sim-muted">Peran</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @forelse ($user->getRoleNames() as $roleName)
                                <span class="sim-badge sim-badge-status bg-sim-surface-alt text-sim-ink border border-sim-border">
                                    {{ \App\Support\RoleLabel::for($roleName) }}
                                    <span class="text-sim-mono text-[10px] text-sim-muted">{{ $roleName }}</span>
                                </span>
                            @empty
                                <span class="text-sm text-sim-muted">Belum ada peran.</span>
                            @endforelse
                        </div>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-sim-muted">Scope Sistem</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @forelse ($user->scopeNames() as $scopeName)
                                <span class="sim-badge sim-badge-status bg-sim-surface-alt text-sim-ink border border-sim-border">
                                    {{ \App\Support\SimmakScopes::labels()[$scopeName] ?? $scopeName }}
                                    <span class="text-sim-mono text-[10px] text-sim-muted">{{ $scopeName }}</span>
                                </span>
                            @empty
                                <span class="text-sm text-sim-muted">Tidak ada scope tambahan.</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="sim-card">
                <div class="sim-card-header"><h3 class="sim-card-title">Akses Klinik ({{ $scopeClinics->count() }})</h3></div>
                <div class="sim-card-body">
                    @if ($user->viewsAllClinics())
                        <div class="sim-alert sim-alert-info">
                            <span class="sim-alert-icon">i</span>
                            <span>Administrator sistem — akses ke seluruh klinik lintas organisasi.</span>
                        </div>
                    @elseif ($scopeClinics->isEmpty())
                        <p class="text-sm text-sim-muted">Belum ada akses klinik yang ditetapkan.</p>
                    @else
                        <div class="flex flex-wrap gap-2">
                            @foreach ($scopeClinics as $clinic)
                                <span class="sim-badge sim-badge-status bg-sim-surface-alt text-sim-ink border border-sim-border">
                                    {{ $clinic->name }}
                                    <span class="text-sim-mono text-[10px] text-sim-muted">{{ $clinic->code }}</span>
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="sim-card">
                <div class="sim-card-header"><h3 class="sim-card-title">Status</h3></div>
                <div class="sim-card-body space-y-3">
                    <span class="sim-badge sim-badge-status {{ $user->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-sim-surface-alt text-sim-muted' }}">
                        {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                    <p class="text-xs text-sim-muted">
                        Dibuat {{ $user->created_at?->translatedFormat('d F Y') }}
                    </p>
                    @can('users.manage')
                        <x-action href="{{ route('users.toggle-active', $user) }}" icon="check-circle"
                                  :label="$user->is_active ? 'Nonaktifkan' : 'Aktifkan'"
                                  variant="secondary" size="sm" class="w-full" method="POST" />
                    @endcan
                </div>
            </div>

            @can('users.manage')
                <div class="sim-card">
                    <div class="sim-card-header"><h3 class="sim-card-title">Reset Kata Sandi</h3></div>
                    <div class="sim-card-body">
                        @if ($errors->has('password'))
                            <div class="sim-alert sim-alert-error mb-3" role="alert">
                                <span class="sim-alert-icon">!</span>
                                <span>{{ $errors->first('password') }}</span>
                            </div>
                        @endif
                        <form method="POST" action="{{ route('users.reset-password', $user) }}" class="space-y-3">
                            @csrf
                            <div class="sim-form-group">
                                <label class="sim-form-label" for="reset_password">Kata Sandi Baru</label>
                                <input type="password" id="reset_password" name="password" required autocomplete="new-password" class="sim-form-input">
                            </div>
                            <div class="sim-form-group">
                                <label class="sim-form-label" for="reset_password_confirmation">Konfirmasi</label>
                                <input type="password" id="reset_password_confirmation" name="password_confirmation" required autocomplete="new-password" class="sim-form-input">
                            </div>
                            <x-action icon="check-circle" label="Reset Kata Sandi" variant="secondary" class="w-full" />
                        </form>
                    </div>
                </div>
            @endcan

            @can('users.manage')
                <div class="sim-card border-sim-danger/30">
                    <div class="sim-card-header"><h3 class="sim-card-title text-sim-danger">Zona Berbahaya</h3></div>
                    <div class="sim-card-body">
                        <x-action href="{{ route('users.destroy', $user) }}" icon="trash" label="Hapus Pengguna" variant="danger"
                                  class="w-full" method="DELETE"
                                  confirm="Hapus pengguna {{ $user->name }}? Tindakan ini tidak dapat dibatalkan." />
                    </div>
                </div>
            @endcan
        </div>
    </div>
@endsection
