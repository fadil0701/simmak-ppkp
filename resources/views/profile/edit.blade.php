@extends('layouts.simmak')

@section('title', 'Profil Saya — SIMMAK PPKP')
@section('page-title', 'Profil Saya')
@section('page-subtitle', 'Perbarui data akun Anda. Peran dan akses klinik diatur oleh administrator.')

@section('content')
    <div class="sim-card max-w-2xl">
        <div class="sim-card-header">
            <h3 class="sim-card-title">Data Akun</h3>
        </div>
        <div class="sim-card-body">
            @if (session('status'))
                <div class="sim-alert sim-alert-success mb-5" role="status">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="sim-alert sim-alert-error mb-5" role="alert">
                    <span class="sim-alert-icon">!</span>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('profile.update') }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="sim-form-group">
                    <label class="sim-form-label" for="name">Nama Lengkap <span class="sim-form-label-required"></span></label>
                    <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                           class="sim-form-input {{ $errors->has('name') ? 'border-sim-danger' : '' }}">
                    @error('name')<p class="sim-form-error">{{ $message }}</p>@enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sim-form-group">
                        <label class="sim-form-label" for="username">Username <span class="sim-form-label-required"></span></label>
                        <input type="text" id="username" name="username" value="{{ old('username', $user->username) }}" required
                               class="sim-form-input {{ $errors->has('username') ? 'border-sim-danger' : '' }}">
                        @error('username')<p class="sim-form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="sim-form-group">
                        <label class="sim-form-label" for="email">Surel <span class="sim-form-label-required"></span></label>
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                               class="sim-form-input {{ $errors->has('email') ? 'border-sim-danger' : '' }}">
                        @error('email')<p class="sim-form-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="rounded-lg border border-sim-border bg-sim-surface-alt px-4 py-3 text-sm text-sim-muted">
                    <div><span class="font-medium text-sim-ink">Peran:</span> {{ \App\Support\RoleLabel::for($user->getRoleNames()->first()) }}</div>
                    <div class="mt-1"><span class="font-medium text-sim-ink">Klinik default:</span> {{ $user->defaultClinic?->name ?? 'Lintas klinik' }}</div>
                </div>

                @if ($user->isLocalAuth())
                    <div class="border-t border-sim-border-light pt-5">
                        <h4 class="mb-3 text-sm font-semibold text-sim-ink">Ubah kata sandi</h4>
                        <p class="mb-4 text-xs text-sim-muted">Kosongkan bila tidak ingin mengganti kata sandi.</p>
                        <div class="space-y-4">
                            <div class="sim-form-group">
                                <label class="sim-form-label" for="current_password">Kata sandi saat ini</label>
                                <input type="password" id="current_password" name="current_password" autocomplete="current-password"
                                       class="sim-form-input {{ $errors->has('current_password') ? 'border-sim-danger' : '' }}">
                                @error('current_password')<p class="sim-form-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="grid gap-5 sm:grid-cols-2">
                                <div class="sim-form-group">
                                    <label class="sim-form-label" for="password">Kata sandi baru</label>
                                    <input type="password" id="password" name="password" autocomplete="new-password"
                                           class="sim-form-input {{ $errors->has('password') ? 'border-sim-danger' : '' }}">
                                    @error('password')<p class="sim-form-error">{{ $message }}</p>@enderror
                                </div>
                                <div class="sim-form-group">
                                    <label class="sim-form-label" for="password_confirmation">Konfirmasi kata sandi baru</label>
                                    <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password"
                                           class="sim-form-input">
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="flex justify-end gap-3 border-t border-sim-border-light pt-4">
                    <x-action action="save" icon="check-circle" label="Simpan Profil" />
                </div>
            </form>
        </div>
    </div>
@endsection
