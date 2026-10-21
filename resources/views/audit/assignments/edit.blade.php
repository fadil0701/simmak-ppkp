@extends('layouts.simmak')

@section('title', 'Sunting Penugasan — SIMMAK PPKP')
@section('page-title', 'Sunting Penugasan')

@section('content')
    <div class="mb-4">
        <a href="{{ route('audit.assignments.show', $assignment) }}" class="text-sm font-medium text-teal-700 hover:underline">← Kembali ke Detail Penugasan</a>
    </div>

    <div class="simmak-card max-w-3xl">
        <div class="simmak-card-header">
            <h3 class="font-semibold text-slate-800">Sunting Penugasan</h3>
        </div>
        <div class="simmak-card-body space-y-4">
            <form method="POST" action="{{ route('audit.assignments.update', $assignment) }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="sim-form-group">
                    <label class="sim-form-label">Kode Tugas<span class="sim-form-label-required"></span></label>
                    <input type="text" name="assignment_code" maxlength="80"
                           value="{{ old('assignment_code', $assignment->assignment_code) }}"
                           class="sim-form-input {{ $errors->has('assignment_code') ? 'border-sim-danger' : '' }}"
                           placeholder="Contoh: TUGAS-2026-001" required>
                    @if ($errors->has('assignment_code'))
                        <p class="sim-form-error">{{ $errors->first('assignment_code') }}</p>
                    @endif
                </div>

                <div class="sim-form-group">
                    <label class="sim-form-label">Judul Tugas<span class="sim-form-label-required"></span></label>
                    <input type="text" name="title" maxlength="255"
                           value="{{ old('title', $assignment->title) }}"
                           class="sim-form-input {{ $errors->has('title') ? 'border-sim-danger' : '' }}"
                           placeholder="Contoh: Audit Tahunan Klinik Pratama Tebet" required>
                    @if ($errors->has('title'))
                        <p class="sim-form-error">{{ $errors->first('title') }}</p>
                    @endif
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sim-form-group">
                        <label class="sim-form-label">Klinik Sasaran</label>
                        <select name="clinic_id" class="sim-form-select {{ $errors->has('clinic_id') ? 'border-sim-danger' : '' }}">
                            <option value="">— Pilih klinik (opsional) —</option>
                            @foreach ($clinics as $clinic)
                                <option value="{{ $clinic->id }}" {{ old('clinic_id', $assignment->clinic_id) === $clinic->id ? 'selected' : '' }}>
                                    {{ $clinic->code }} — {{ $clinic->name }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('clinic_id'))
                            <p class="sim-form-error">{{ $errors->first('clinic_id') }}</p>
                        @endif
                    </div>

                    <div class="sim-form-group">
                        <label class="sim-form-label">Ketua Tim Auditor</label>
                        <select name="lead_auditor_id" class="sim-form-select {{ $errors->has('lead_auditor_id') ? 'border-sim-danger' : '' }}">
                            <option value="">— Pilih ketua tim —</option>
                            @foreach ($auditors as $auditor)
                                <option value="{{ $auditor->id }}" {{ old('lead_auditor_id', $assignment->lead_auditor_id) === $auditor->id ? 'selected' : '' }}>
                                    {{ $auditor->name }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('lead_auditor_id'))
                            <p class="sim-form-error">{{ $errors->first('lead_auditor_id') }}</p>
                        @endif
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sim-form-group">
                        <label class="sim-form-label">Tanggal Mulai<span class="sim-form-label-required"></span></label>
                        <input type="date" name="scheduled_start"
                               value="{{ old('scheduled_start', $assignment->scheduled_start?->format('Y-m-d')) }}"
                               class="sim-form-input {{ $errors->has('scheduled_start') ? 'border-sim-danger' : '' }}"
                               required>
                        @if ($errors->has('scheduled_start'))
                            <p class="sim-form-error">{{ $errors->first('scheduled_start') }}</p>
                        @endif
                    </div>

                    <div class="sim-form-group">
                        <label class="sim-form-label">Tanggal Selesai<span class="sim-form-label-required"></span></label>
                        <input type="date" name="scheduled_end"
                               value="{{ old('scheduled_end', $assignment->scheduled_end?->format('Y-m-d')) }}"
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
                              placeholder="Catatan ruang lingkup penugasan">{{ old('scope_notes', $assignment->scope_notes) }}</textarea>
                    @if ($errors->has('scope_notes'))
                        <p class="sim-form-error">{{ $errors->first('scope_notes') }}</p>
                    @endif
                </div>

                <div class="flex justify-end pt-4 border-t border-slate-100">
                    <button type="submit" class="sim-btn sim-btn-primary">
                        Perbarui Penugasan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
