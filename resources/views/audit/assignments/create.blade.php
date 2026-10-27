@extends('layouts.simmak')

@section('title', 'Buat Penugasan Auditor — SIMMAK PPKP')
@section('page-title', 'Buat Penugasan Auditor')

@section('content')
    <div class="mb-4">
        <x-action href="{{ route('audit.assignments.index') }}" icon="chevron-left" label="Kembali" variant="ghost" size="sm" />
    </div>

    <div class="simmak-card max-w-3xl">
        <div class="simmak-card-header">
            <h3 class="font-semibold text-slate-800">Buat Penugasan Auditor</h3>
        </div>
        <div class="simmak-card-body space-y-4">
            <form method="POST" action="{{ route('audit.assignments.store') }}" class="space-y-4">
                @csrf

                <div class="sim-form-group">
                    <label class="sim-form-label">Kode Tugas<span class="sim-form-label-required"></span></label>
                    <input type="text" name="assignment_code" maxlength="80"
                           value="{{ old('assignment_code') }}"
                           class="sim-form-input {{ $errors->has('assignment_code') ? 'border-sim-danger' : '' }}"
                           placeholder="Contoh: TUGAS-2026-001" required>
                    @if ($errors->has('assignment_code'))
                        <p class="sim-form-error">{{ $errors->first('assignment_code') }}</p>
                    @endif
                </div>

                <div class="sim-form-group">
                    <label class="sim-form-label">Judul Tugas<span class="sim-form-label-required"></span></label>
                    <input type="text" name="title" maxlength="255"
                           value="{{ old('title') }}"
                           class="sim-form-input {{ $errors->has('title') ? 'border-sim-danger' : '' }}"
                           placeholder="Contoh: Audit Tahunan Klinik Pratama Tebat" required>
                    @if ($errors->has('title'))
                        <p class="sim-form-error">{{ $errors->first('title') }}</p>
                    @endif
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sim-form-group">
                        <label class="sim-form-label">Program Audit<span class="sim-form-label-required"></span></label>
                        <select name="audit_program_id" class="sim-form-select {{ $errors->has('audit_program_id') ? 'border-sim-danger' : '' }}" required>
                            <option value="">— Pilih program —</option>
                            @foreach ($programs as $program)
                                <option value="{{ $program->id }}" {{ old('audit_program_id') === $program->id ? 'selected' : '' }}>
                                    {{ $program->program_code }} — {{ $program->name }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('audit_program_id'))
                            <p class="sim-form-error">{{ $errors->first('audit_program_id') }}</p>
                        @endif
                    </div>

                    <div class="sim-form-group">
                        <label class="sim-form-label">Klinik / Unit yang Diaudit<span class="sim-form-label-required"></span></label>
                        <select name="clinic_id" class="sim-form-select {{ $errors->has('clinic_id') ? 'border-sim-danger' : '' }}" required>
                            <option value="">— Pilih klinik —</option>
                            @foreach ($clinics as $clinic)
                                <option value="{{ $clinic->id }}" {{ old('clinic_id') === $clinic->id ? 'selected' : '' }}>
                                    {{ $clinic->code }} — {{ $clinic->name }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('clinic_id'))
                            <p class="sim-form-error">{{ $errors->first('clinic_id') }}</p>
                        @endif
                    </div>
                </div>

                <div class="sim-form-group">
                    <label class="sim-form-label">Standar / Instrumen (opsional)</label>
                    <select name="instrument_version_id" class="sim-form-select {{ $errors->has('instrument_version_id') ? 'border-sim-danger' : '' }}">
                        <option value="">— Belum ditentukan —</option>
                        @foreach ($instrumentVersions as $version)
                            <option value="{{ $version->id }}" @selected(old('instrument_version_id') === $version->id)>
                                {{ $version->standard?->title ?? 'Standar' }} ({{ $version->version_label }})
                            </option>
                        @endforeach
                    </select>
                    @if ($errors->has('instrument_version_id'))
                        <p class="sim-form-error">{{ $errors->first('instrument_version_id') }}</p>
                    @endif
                    <p class="form-hint text-xs text-slate-500">
                        Instrumen yang direncanakan. Bila dikosongkan, kolom indeks menampilkan
                        instrumen dari eksekusi audit yang lahir dari penugasan ini.
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sim-form-group">
                        <label class="sim-form-label">Ketua Tim (nama pegawai)<span class="sim-form-label-required"></span></label>
                        <select name="lead_auditor_id" class="sim-form-select {{ $errors->has('lead_auditor_id') ? 'border-sim-danger' : '' }}" required>
                            <option value="">— Pilih pegawai auditor —</option>
                            @foreach ($auditors as $auditor)
                                <option value="{{ $auditor->id }}" {{ old('lead_auditor_id') === $auditor->id ? 'selected' : '' }}>
                                    {{ $auditor->employee?->full_name ?? $auditor->name }}
                                    @if ($auditor->employee?->unit?->name)
                                        — {{ $auditor->employee->unit->name }}
                                    @elseif ($auditor->employee?->clinic?->name)
                                        — {{ $auditor->employee->clinic->name }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('lead_auditor_id'))
                            <p class="sim-form-error">{{ $errors->first('lead_auditor_id') }}</p>
                        @endif
                    </div>

                    <div class="sim-form-group">
                        <label class="sim-form-label">Tanggal Mulai<span class="sim-form-label-required"></span></label>
                        <input type="date" name="scheduled_start"
                               value="{{ old('scheduled_start') }}"
                               class="sim-form-input {{ $errors->has('scheduled_start') ? 'border-sim-danger' : '' }}"
                               required>
                        @if ($errors->has('scheduled_start'))
                            <p class="sim-form-error">{{ $errors->first('scheduled_start') }}</p>
                        @endif
                    </div>

                    <div class="sim-form-group">
                        <label class="sim-form-label">Tanggal Selesai<span class="sim-form-label-required"></span></label>
                        <input type="date" name="scheduled_end"
                               value="{{ old('scheduled_end') }}"
                               class="sim-form-input {{ $errors->has('scheduled_end') ? 'border-sim-danger' : '' }}"
                               required>
                        @if ($errors->has('scheduled_end'))
                            <p class="sim-form-error">{{ $errors->first('scheduled_end') }}</p>
                        @endif
                    </div>
                </div>

                @if ($auditors->count() > 1)
                    <div class="sim-form-group">
                        <label class="sim-form-label">Anggota Tim Auditor</label>
                        <div class="space-y-2 rounded-lg border border-slate-200 p-3 max-h-48 overflow-y-auto">
                            @foreach ($auditors as $auditor)
                                <label class="flex items-start gap-2 text-sm text-slate-700">
                                    <input type="checkbox" name="member_ids[]" value="{{ $auditor->id }}"
                                           class="mt-1"
                                           @checked(in_array($auditor->id, old('member_ids', []), true))>
                                    <span>
                                        {{ $auditor->employee?->full_name ?? $auditor->name }}
                                        <span class="block text-xs text-slate-500">
                                            {{ $auditor->employee?->unit?->name ?? $auditor->employee?->clinic?->name ?? $auditor->email }}
                                        </span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Ketua tim otomatis menjadi anggota. Auditor hanya dapat memeriksa klinik pada penugasan ini.</p>
                    </div>
                @endif

                <div class="sim-form-group">
                    <label class="sim-form-label">Catatan Ruang Lingkup</label>
                    <textarea name="scope_notes" rows="3"
                              class="sim-form-textarea {{ $errors->has('scope_notes') ? 'border-sim-danger' : '' }}"
                              placeholder="Catatan ruang lingkup penugasan">{{ old('scope_notes') }}</textarea>
                    @if ($errors->has('scope_notes'))
                        <p class="sim-form-error">{{ $errors->first('scope_notes') }}</p>
                    @endif
                </div>

                <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
                    <x-action icon="check-circle" label="Buat Penugasan" />
                </div>
            </form>
        </div>
    </div>
@endsection
