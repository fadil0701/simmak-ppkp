@extends('layouts.simmak')

@php
    $isCreate = is_null($clinic);
    $title = $isCreate ? 'Tambah Unit/Klinik' : 'Sunting Unit/Klinik';
    $route = $isCreate ? route('clinics.store') : route('clinics.update', $clinic);
    $method = $isCreate ? 'POST' : 'PUT';
@endphp

@section('title', $title.' — SIMMAK PPKP')
@section('page-title', $title)

@section('content')
    <div class="mb-4">
        <x-action href="{{ route('clinics.index') }}" icon="chevron-left" label="Kembali" variant="ghost" size="sm" class="mb-4" />
    </div>

    <div class="simmak-card max-w-2xl">
        <div class="simmak-card-header">
            <h3 class="font-semibold text-slate-800">{{ $title }}</h3>
        </div>
        <div class="simmak-card-body space-y-4">
            <form method="POST" action="{{ $route }}" class="space-y-4"
                  x-data="{ type: '{{ old('clinic_type', $clinic?->clinic_type ?? 'pratama') }}' }">
                @csrf
                @if (! $isCreate)
                    @method('PUT')
                @endif

                <input type="hidden" name="organization_id" value="{{ auth()->user()->organization_id }}">

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sim-form-group">
                        <label class="sim-form-label">Kode Unit/Klinik <span class="sim-form-label-required"></span></label>
                        <input type="text" name="code" maxlength="80"
                               value="{{ old('code', $clinic?->code ?? null) }}"
                               class="sim-form-input {{ $errors->has('code') ? 'border-sim-danger' : '' }}"
                               placeholder="Contoh: KP01" required>
                        @if ($errors->has('code'))
                            <p class="sim-form-error">{{ $errors->first('code') }}</p>
                        @endif
                    </div>

                    <div class="sim-form-group">
                        <label class="sim-form-label">Nama Unit/Klinik <span class="sim-form-label-required"></span></label>
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
                        <label class="sim-form-label">Tipe Unit <span class="sim-form-label-required"></span></label>
                        <select name="clinic_type" x-model="type" class="sim-form-select {{ $errors->has('clinic_type') ? 'border-sim-danger' : '' }}" required>
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

                <div class="sim-form-group">
                    <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                        <input type="hidden" name="is_auditable" value="0">
                        <input type="checkbox" name="is_auditable" value="1"
                               x-bind:disabled="type === 'pusat'"
                               x-bind:checked="type !== 'pusat' && {{ (old('is_auditable', $clinic?->is_auditable ?? true)) ? 'true' : 'false' }}"
                               class="rounded border-slate-200 text-teal-700 focus:ring-teal-600">
                        Sasaran audit internal
                    </label>
                    <p class="sim-form-hint">
                        Kantor Pusat (Manajemen) tidak diaudit; perannya memantau kegiatan audit dan menetapkan unit sasaran.
                        Klinik utama/pratama dapat ditambah atau dikurangi kapan saja.
                    </p>
                    @if ($errors->has('is_auditable'))
                        <p class="sim-form-error">{{ $errors->first('is_auditable') }}</p>
                    @endif
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
                    <x-action icon="check-circle" :label="$isCreate ? 'Simpan Unit/Klinik' : 'Simpan Perubahan'" />
                </div>
            </form>
        </div>
    </div>
@endsection
