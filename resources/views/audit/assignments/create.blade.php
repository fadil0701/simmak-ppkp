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
                        <label class="sim-form-label">Klinik Sasaran</label>
                        <select name="clinic_id" class="sim-form-select {{ $errors->has('clinic_id') ? 'border-sim-danger' : '' }}">
                            <option value="">— Pilih klinik (opsional) —</option>
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

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sim-form-group">
                        <label class="sim-form-label">Ketua Tim Auditor</label>
                        <select name="lead_auditor_id" class="sim-form-select {{ $errors->has('lead_auditor_id') ? 'border-sim-danger' : '' }}">
                            <option value="">— Pilih ketua tim —</option>
                            @foreach ($auditors as $auditor)
                                <option value="{{ $auditor->id }}" {{ old('lead_auditor_id') === $auditor->id ? 'selected' : '' }}>
                                    {{ $auditor->name }}
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
