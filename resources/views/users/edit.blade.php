@extends('layouts.simmak')

@section('title', 'Ubah Pengguna — SIMMAK PPKP')
@section('page-title', 'Ubah Pengguna')
@section('page-subtitle', 'Perbarui data, peran, dan akses klinik pengguna.')

@section('breadcrumb')
    <li class="flex items-center gap-1 text-sim-muted">
        <a href="{{ route('users.index') }}">Pengguna</a>
        <svg class="h-3.5 w-3.5 text-sim-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
        <a href="{{ route('users.show', $user) }}">{{ $user->name }}</a>
        <svg class="h-3.5 w-3.5 text-sim-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
        <span>Ubah</span>
    </li>
@endsection

@section('content')
    <div class="sim-card max-w-3xl">
        <div class="sim-card-header">
            <h3 class="sim-card-title">Data Pengguna</h3>
        </div>
        <div class="sim-card-body">
            @if ($errors->any())
                <div class="sim-alert sim-alert-error mb-5" role="alert">
                    <span class="sim-alert-icon">!</span>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-5">
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

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sim-form-group">
                        <label class="sim-form-label" for="password">Kata Sandi Baru</label>
                        <input type="password" id="password" name="password" autocomplete="new-password"
                               class="sim-form-input {{ $errors->has('password') ? 'border-sim-danger' : '' }}">
                        <p class="sim-form-hint">Biarkan kosong bila tidak ingin mengubah kata sandi.</p>
                        @error('password')<p class="sim-form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="sim-form-group">
                        <label class="sim-form-label" for="password_confirmation">Konfirmasi Kata Sandi Baru</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password"
                               class="sim-form-input">
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sim-form-group">
                        <label class="sim-form-label" for="role">Peran <span class="sim-form-label-required"></span></label>
                        <select id="role" name="role" required class="sim-form-select {{ $errors->has('role') ? 'border-sim-danger' : '' }}">
                            <option value="">— Pilih peran —</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->name }}" @selected(old('role', $currentRole) === $role->name)>
                                    {{ \App\Support\RoleLabel::for($role->name) }} ({{ $role->name }})
                                </option>
                            @endforeach
                        </select>
                        @error('role')<p class="sim-form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="sim-form-group">
                        <label class="sim-form-label" for="default_clinic_id">Klinik Default</label>
                        <select id="default_clinic_id" name="default_clinic_id" class="sim-form-select">
                            <option value="">— Tidak ada (lintas klinik) —</option>
                            @foreach ($clinics as $clinic)
                                <option value="{{ $clinic->id }}" @selected(old('default_clinic_id', $user->default_clinic_id) == $clinic->id)>
                                    {{ $clinic->name }} ({{ $clinic->code }})
                                </option>
                            @endforeach
                        </select>
                        <p class="sim-form-hint">Konteks klinik yang dipakai saat pengguna masuk.</p>
                    </div>
                </div>

                <div class="sim-form-group">
                    <label class="sim-form-label">Akses Klinik</label>
                    <div class="grid gap-2 rounded-lg border border-sim-border bg-sim-surface-alt p-3 sm:grid-cols-2">
                        @foreach ($clinics as $clinic)
                            <label class="flex items-center gap-2 text-sm text-sim-ink">
                                <input type="checkbox" name="clinic_ids[]" value="{{ $clinic->id }}"
                                       @checked(collect(old('clinic_ids', $scopedClinicIds))->contains($clinic->id))
                                       class="h-4 w-4 rounded border-sim-border text-sim-primary focus:ring-sim-primary">
                                <span>{{ $clinic->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="sim-form-hint">Bila tidak ada yang dipilih, pengguna hanya mendapat Klinik Default.</p>
                </div>

                <div class="sim-form-group">
                    <label class="flex items-center gap-2 text-sm font-medium text-sim-ink">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active))
                               class="h-4 w-4 rounded border-sim-border text-sim-primary focus:ring-sim-primary">
                        Akun aktif
                    </label>
                </div>

                <div class="flex justify-end gap-3 border-t border-sim-border-light pt-4">
                    <a href="{{ route('users.show', $user) }}" class="sim-btn sim-btn-outline">Batal</a>
                    <button type="submit" class="sim-btn sim-btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
@endsection
