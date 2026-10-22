@extends('layouts.simmak')

@php
    $isCreate = is_null($employee);
    $title = $isCreate ? 'Tambah Pegawai' : 'Sunting Pegawai';
    $route = $isCreate ? route('employees.store') : route('employees.update', $employee);
@endphp

@section('title', $title.' — SIMMAK PPKP')
@section('page-title', $title)

@section('content')
    <div class="mb-4">
        <x-action href="{{ route('employees.index') }}" icon="chevron-left" label="Kembali" variant="ghost" size="sm" class="mb-4" />
    </div>

    <div class="sim-card max-w-3xl">
        <div class="sim-card-header">
            <h3 class="sim-card-title">{{ $title }}</h3>
        </div>
        <div class="sim-card-body">
            <form method="POST" action="{{ $route }}" class="space-y-5">
                @csrf
                @if (! $isCreate)
                    @method('PUT')
                @endif

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sim-form-group">
                        <label class="sim-form-label" for="employee_number">NIP Pegawai<span class="sim-form-label-required"></span></label>
                        <input type="text" id="employee_number" name="employee_number" maxlength="50"
                               value="{{ old('employee_number', $employee?->employee_number) }}" required
                               class="sim-form-input {{ $errors->has('employee_number') ? 'border-sim-danger' : '' }}">
                        @error('employee_number')<p class="sim-form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="sim-form-group">
                        <label class="sim-form-label" for="full_name">Nama Lengkap <span class="sim-form-label-required"></span></label>
                        <input type="text" id="full_name" name="full_name" maxlength="255"
                               value="{{ old('full_name', $employee?->full_name) }}" required
                               class="sim-form-input {{ $errors->has('full_name') ? 'border-sim-danger' : '' }}">
                        @error('full_name')<p class="sim-form-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sim-form-group">
                        <label class="sim-form-label" for="position_id">Jabatan</label>
                        <select id="position_id" name="position_id" class="sim-form-select {{ $errors->has('position_id') ? 'border-sim-danger' : '' }}">
                            <option value="">— Tanpa jabatan —</option>
                            @foreach ($positions as $position)
                                <option value="{{ $position->id }}" @selected(old('position_id', $employee?->position_id) === $position->id)>
                                    {{ $position->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('position_id')<p class="sim-form-error">{{ $message }}</p>@enderror
                        <p class="sim-form-hint">
                            Jabatan hanya untuk kelengkapan data pegawai, bukan peran sistem.
                            Daftar dikelola di
                            <a href="{{ route('positions.index') }}" class="text-sim-primary hover:underline">Master Jabatan</a>.
                            Hak akses akun diatur terpisah di menu Pengguna.
                        </p>
                    </div>
                    <div class="sim-form-group">
                        <label class="sim-form-label" for="email">Email Pegawai</label>
                        <input type="email" id="email" name="email" maxlength="320"
                               value="{{ old('email', $employee?->email) }}"
                               class="sim-form-input {{ $errors->has('email') ? 'border-sim-danger' : '' }}">
                        @error('email')<p class="sim-form-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-3">
                    <div class="sim-form-group">
                        <label class="sim-form-label" for="clinic_id">Klinik / Unit</label>
                        <select id="clinic_id" name="clinic_id" class="sim-form-select">
                            <option value="">— Tidak ada —</option>
                            @foreach ($clinics as $clinic)
                                <option value="{{ $clinic->id }}" @selected(old('clinic_id', $employee?->clinic_id) === $clinic->id)>
                                    {{ $clinic->code }} — {{ $clinic->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('clinic_id')<p class="sim-form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="sim-form-group">
                        <label class="sim-form-label" for="unit_id">Subunit (opsional)</label>
                        <select id="unit_id" name="unit_id" class="sim-form-select">
                            <option value="">— Tidak ada —</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}" @selected(old('unit_id', $employee?->unit_id) === $unit->id)>
                                    {{ $unit->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('unit_id')<p class="sim-form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="sim-form-group">
                        <label class="sim-form-label" for="phone">No. Telepon</label>
                        <input type="text" id="phone" name="phone" maxlength="50"
                               value="{{ old('phone', $employee?->phone) }}"
                               class="sim-form-input {{ $errors->has('phone') ? 'border-sim-danger' : '' }}">
                        @error('phone')<p class="sim-form-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="sim-form-group">
                    <label class="flex items-center gap-2 text-sm font-medium text-sim-ink">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $employee?->is_active ?? true))
                               class="h-4 w-4 rounded border-sim-border text-sim-primary focus:ring-sim-primary">
                        Pegawai aktif
                    </label>
                </div>

                <div class="flex justify-end gap-3 border-t border-sim-border-light pt-4">
                    <a href="{{ route('employees.index') }}" class="sim-btn sim-btn-outline">Batal</a>
                    <x-action icon="check-circle" :label="$isCreate ? 'Simpan Pegawai' : 'Simpan Perubahan'" />
                </div>
            </form>
        </div>
    </div>
@endsection
