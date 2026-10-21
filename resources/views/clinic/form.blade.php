@extends('layouts.simmak')

@php
    $isCreate = is_null($clinic);
    $title = $isCreate ? 'Tambah Klinik' : 'Sunting Klinik';
    $route = $isCreate ? route('clinics.store') : route('clinics.update', $clinic);
    $method = $isCreate ? 'POST' : 'PUT';
@endphp

@section('title', $title.' — SIMMAK PPKP')
@section('page-title', $title)

@section('content')
    <div class="mb-4">
        <a href="{{ route('clinics.index') }}" class="text-sm font-medium text-teal-700 hover:underline">← Kembali ke Daftar Klinik</a>
    </div>

    <div class="simmak-card max-w-2xl">
        <div class="simmak-card-header">
            <h3 class="font-semibold text-slate-800">{{ $title }}</h3>
        </div>
        <div class="simmak-card-body space-y-4">
            <form method="POST" action="{{ $route }}" class="space-y-4">
                @csrf
                @if (! $isCreate)
                    @method('PUT')
                @endif

                <input type="hidden" name="organization_id" value="{{ auth()->user()->organization_id }}">

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sim-form-group">
                        <label class="sim-form-label">Kode Klinik <span class="sim-form-label-required"></span></label>
                        <input type="text" name="code" maxlength="80"
                               value="{{ old('code', $clinic?->code ?? null) }}"
                               class="sim-form-input {{ $errors->has('code') ? 'border-sim-danger' : '' }}"
                               placeholder="Contoh: KP01" required>
                        @if ($errors->has('code'))
                            <p class="sim-form-error">{{ $errors->first('code') }}</p>
                        @endif
                    </div>

                    <div class="sim-form-group">
                        <label class="sim-form-label">Nama Klinik <span class="sim-form-label-required"></span></label>
                        <input type="text" name="name" maxlength="255"
                               value="{{ old('name', $clinic?->name ?? null) }}"
                               class="sim-form-input {{ $errors->has('name') ? 'border-sim-danger' : '' }}"
                               placeholder="Contoh: Klinik Pratama Jakarta Pusat" required>
                        @if ($errors->has('name'))
                            <p class="sim-form-error">{{ $errors->first('name') }}</p>
                        @endif
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sim-form-group">
                        <label class="sim-form-label">Tipe Klinik <span class="sim-form-label-required"></span></label>
                        <select name="clinic_type" class="sim-form-select {{ $errors->has('clinic_type') ? 'border-sim-danger' : '' }}" required>
                            @foreach ($clinicTypes as $value => $label)
                                <option value="{{ $value }}" {{ (old('clinic_type', $clinic?->clinic_type ?? null) === $value) ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @if ($errors->has('clinic_type'))
                            <p class="sim-form-error">{{ $errors->first('clinic_type') }}</p>
                        @endif
                    </div>

                    <div class="sim-form-group">
                        <label class="sim-form-label">Klinik Utama</label>
                        <input type="checkbox" name="is_main_clinic" value="1"
                               {{ (old('is_main_clinic', $clinic?->is_main_clinic ?? false)) ? 'checked' : '' }}
                               class="rounded border-slate-200 text-teal-700 focus:ring-teal-600">
                        @if ($errors->has('is_main_clinic'))
                            <p class="sim-form-error">{{ $errors->first('is_main_clinic') }}</p>
                        @endif
                        <p class="sim-form-hint">Hanya satu klinik utama per organisasi.</p>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sim-form-group">
                        <label class="sim-form-label">Tipe Akreditasi</label>
                        <select name="accreditation_type" class="sim-form-select {{ $errors->has('accreditation_type') ? 'border-sim-danger' : '' }}">
                            <option value="">— Pilih —</option>
                            @foreach ($accreditationTypes as $type)
                                <option value="{{ $type }}" {{ (old('accreditation_type', $clinic?->accreditation_type ?? null) === $type) ? 'selected' : '' }}>{{ $type }}</option>
                            @endforeach
                        </select>
                        @if ($errors->has('accreditation_type'))
                            <p class="sim-form-error">{{ $errors->first('accreditation_type') }}</p>
                        @endif
                    </div>

                    <div class="sim-form-group">
                        <label class="sim-form-label">Status Akreditasi</label>
                        <select name="accreditation_status" class="sim-form-select {{ $errors->has('accreditation_status') ? 'border-sim-danger' : '' }}">
                            <option value="">— Pilih —</option>
                            @foreach ($accreditationStatuses as $status)
                                <option value="{{ $status }}" {{ (old('accreditation_status', $clinic?->accreditation_status ?? null) === $status) ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                        @if ($errors->has('accreditation_status'))
                            <p class="sim-form-error">{{ $errors->first('accreditation_status') }}</p>
                        @endif
                    </div>
                </div>

                <div class="sim-form-group">
                    <label class="sim-form-label">Kota</label>
                    <input type="text" name="city" maxlength="100"
                           value="{{ old('city', $clinic?->city ?? null) }}"
                           class="sim-form-input {{ $errors->has('city') ? 'border-sim-danger' : '' }}"
                           placeholder="Contoh: Jakarta Pusat">
                    @if ($errors->has('city'))
                        <p class="sim-form-error">{{ $errors->first('city') }}</p>
                    @endif
                </div>

                <div class="sim-form-group">
                    <label class="sim-form-label">Alamat</label>
                    <textarea name="address" rows="3"
                              class="sim-form-textarea {{ $errors->has('address') ? 'border-sim-danger' : '' }}"
                              placeholder="Alamat lengkap klinik">{{ old('address', $clinic?->address ?? null) }}</textarea>
                    @if ($errors->has('address'))
                        <p class="sim-form-error">{{ $errors->first('address') }}</p>
                    @endif
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" id="is_active"
                               {{ (old('is_active', $clinic?->is_active ?? true)) ? 'checked' : '' }}
                               class="rounded border-slate-200 text-teal-700 focus:ring-teal-600">
                        <label for="is_active" class="text-sm font-medium text-slate-700">Aktif</label>
                    </div>
                </div>

                <div class="flex justify-end pt-4 border-t border-slate-100">
                    <button type="submit" class="sim-btn sim-btn-primary">
                        {{ $isCreate ? 'Simpan Klinik' : 'Perbarui Klinik' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
