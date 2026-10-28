@extends('layouts.simmak')

@section('title', 'Buat Laporan Audit — SIMMAK PPKP')
@section('page-title', 'Buat Laporan Audit')

@section('content')
    <div class="mb-4">
        <a href="{{ route('audit.reports.index') }}" class="sim-btn sim-btn-back sim-btn-sm">← Kembali ke Daftar Laporan</a>
    </div>

    <div class="sim-card max-w-2xl">
        <div class="sim-card-header">
            <h3 class="sim-card-title">Buat Laporan Audit</h3>
        </div>
        <div class="sim-card-body space-y-4">
            <form method="POST" action="{{ route('audit.reports.store') }}" class="space-y-4">
                @csrf

                <div class="sim-form-group">
                    <label class="sim-form-label">Judul Laporan <span class="text-red-500">*</span></label>
                    <input type="text" name="title" maxlength="255"
                           value="{{ old('title') }}"
                           class="sim-form-input {{ $errors->has('title') ? 'border-sim-danger' : '' }}"
                           placeholder="Contoh: Laporan Temuan Audit Kuartal III 2026" required>
                    @if ($errors->has('title'))
                        <p class="sim-form-error">{{ $errors->first('title') }}</p>
                    @endif
                </div>

                <div class="sim-form-group">
                    <label class="sim-form-label">Tipe Laporan <span class="text-red-500">*</span></label>
                    <select name="report_type" class="sim-form-select {{ $errors->has('report_type') ? 'border-sim-danger' : '' }}" required>
                        <option value="summary" {{ old('report_type') === 'summary' ? 'selected' : '' }}>Ringkasan Audit</option>
                        <option value="findings" {{ old('report_type') === 'findings' ? 'selected' : '' }}>Temuan Audit</option>
                        <option value="capa" {{ old('report_type') === 'capa' ? 'selected' : '' }}>Tindak Lanjut</option>
                        <option value="execution" {{ old('report_type') === 'execution' ? 'selected' : '' }}>Eksekusi Audit</option>
                        <option value="program" {{ old('report_type') === 'program' ? 'selected' : '' }}>Program Audit</option>
                    </select>
                    @if ($errors->has('report_type'))
                        <p class="sim-form-error">{{ $errors->first('report_type') }}</p>
                    @endif
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sim-form-group">
                        <label class="sim-form-label">Periode Awal</label>
                        <input type="date" name="period_start"
                               value="{{ old('period_start') }}"
                               class="sim-form-input {{ $errors->has('period_start') ? 'border-sim-danger' : '' }}">
                        @if ($errors->has('period_start'))
                            <p class="sim-form-error">{{ $errors->first('period_start') }}</p>
                        @endif
                    </div>

                    <div class="sim-form-group">
                        <label class="sim-form-label">Periode Akhir</label>
                        <input type="date" name="period_end"
                               value="{{ old('period_end') }}"
                               class="sim-form-input {{ $errors->has('period_end') ? 'border-sim-danger' : '' }}">
                        @if ($errors->has('period_end'))
                            <p class="sim-form-error">{{ $errors->first('period_end') }}</p>
                        @endif
                    </div>
                </div>

                <div class="sim-form-group">
                    <label class="sim-form-label">Klinik</label>
                    <select name="clinic_id" class="sim-form-select {{ $errors->has('clinic_id') ? 'border-sim-danger' : '' }}">
                        <option value="">— Semua Klinik dalam Ruang Lingkup —</option>
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

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sim-form-group">
                        <label class="sim-form-label">Jenis Audit</label>
                        <select name="audit_type" class="sim-form-select">
                            <option value="">— Semua —</option>
                            @foreach (['periodic' => 'Periodik', 'thematic' => 'Tematik', 'special' => 'Khusus', 'follow_up' => 'Ulang'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('audit_type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sim-form-group">
                        <label class="sim-form-label">Auditor (anggota tim)</label>
                        <select name="auditor_id" class="sim-form-select">
                            <option value="">— Semua —</option>
                            @foreach ($auditors as $auditor)
                                <option value="{{ $auditor->id }}" @selected(old('auditor_id') === $auditor->id)>{{ $auditor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sim-form-group">
                        <label class="sim-form-label">Status temuan</label>
                        <select name="finding_status" class="sim-form-select">
                            <option value="">— Semua —</option>
                            @foreach (['draft', 'under_review', 'confirmed', 'in_capa', 'pending_verification', 'closed'] as $status)
                                <option value="{{ $status }}" @selected(old('finding_status') === $status)>{{ \App\Support\UiLabel::findingStatus($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sim-form-group">
                        <label class="sim-form-label">Tingkat risiko</label>
                        <select name="severity" class="sim-form-select">
                            <option value="">— Semua —</option>
                            @foreach (['critical', 'major', 'minor'] as $severity)
                                <option value="{{ $severity }}" @selected(old('severity') === $severity)>{{ \App\Support\UiLabel::findingSeverity($severity) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sim-form-group">
                        <label class="sim-form-label">Status Tindak Lanjut</label>
                        <select name="capa_status" class="sim-form-select">
                            <option value="">— Semua —</option>
                            @foreach (['open', 'analysis', 'action', 'verification', 'closed'] as $status)
                                <option value="{{ $status }}" @selected(old('capa_status') === $status)>{{ \App\Support\UiLabel::capaStatus($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="sim-form-group">
                    <label class="sim-form-label">Eksekusi Audit</label>
                    <select name="audit_execution_id" class="sim-form-select {{ $errors->has('audit_execution_id') ? 'border-sim-danger' : '' }}">
                        <option value="">— Semua Eksekusi —</option>
                        @foreach ($executions as $execution)
                            <option value="{{ $execution->id }}" {{ old('audit_execution_id') === $execution->id ? 'selected' : '' }}>
                                {{ $execution->execution_code }} — {{ $execution->title }}
                            </option>
                        @endforeach
                    </select>
                    @if ($errors->has('audit_execution_id'))
                        <p class="sim-form-error">{{ $errors->first('audit_execution_id') }}</p>
                    @endif
                </div>

                <div class="sim-form-group">
                    <label class="sim-form-label">Ringkasan Eksekutif</label>
                    <textarea name="executive_summary" rows="4"
                              class="sim-form-textarea {{ $errors->has('executive_summary') ? 'border-sim-danger' : '' }}"
                              placeholder="Ringkasan hasil laporan audit">{{ old('executive_summary') }}</textarea>
                    @if ($errors->has('executive_summary'))
                        <p class="sim-form-error">{{ $errors->first('executive_summary') }}</p>
                    @endif
                </div>

                <div class="flex justify-end gap-2 pt-4 border-t border-sim-border-light">
                    <x-action action="create" icon="wand" label="Buat Laporan" />
                </div>
            </form>
        </div>
    </div>
@endsection
