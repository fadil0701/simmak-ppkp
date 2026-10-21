@extends('layouts.simmak')

@section('title', 'Unggah Bukti Audit — SIMMAK PPKP')
@section('page-title', 'Unggah Bukti Audit')

@section('content')
    <div class="mb-4">
        <a href="{{ route('audit.evidence.index') }}" class="text-sm font-medium text-teal-700 hover:underline">← Kembali ke Daftar Bukti</a>
    </div>

    <div class="simmak-card max-w-2xl">
        <div class="simmak-card-header">
            <h3 class="font-semibold text-slate-800">Unggah Bukti Audit</h3>
        </div>
        <div class="simmak-card-body space-y-4">
            <form method="POST" action="{{ route('audit.evidence.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div class="sim-form-group">
                    <label class="sim-form-label">Berkas<span class="sim-form-label-required"></span></label>
                    <input type="file" name="file"
                           class="sim-form-input {{ $errors->has('file') ? 'border-sim-danger' : '' }}"
                           accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.csv,.txt" required>
                    <p class="sim-form-hint">Maksimal 10 MB. Tipe yang diizinkan: PDF, JPG, PNG, DOC, DOCX, XLS, XLSX, CSV, TXT.</p>
                    @if ($errors->has('file'))
                        <p class="sim-form-error">{{ $errors->first('file') }}</p>
                    @endif
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sim-form-group">
                        <label class="sim-form-label">Klinik</label>
                        <select name="clinic_id" class="sim-form-select {{ $errors->has('clinic_id') ? 'border-sim-danger' : '' }}">
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

                    <div class="sim-form-group">
                        <label class="sim-form-label">Eksekusi Audit</label>
                        <select name="audit_execution_id" class="sim-form-select {{ $errors->has('audit_execution_id') ? 'border-sim-danger' : '' }}">
                            <option value="">— Pilih eksekusi —</option>
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
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sim-form-group">
                        <label class="sim-form-label">Jenis Bukti</label>
                        <input type="text" name="evidence_type" value="{{ old('evidence_type') }}" placeholder="Contoh: Lapor Hasil Pemeriksaan" class="sim-form-input {{ $errors->has('evidence_type') ? 'border-sim-danger' : '' }}">
                        @if ($errors->has('evidence_type'))
                            <p class="sim-form-error">{{ $errors->first('evidence_type') }}</p>
                        @endif
                    </div>

                    <div class="sim-form-group">
                        <label class="sim-form-label">Klasifikasi Akses</label>
                        <select name="access_classification" class="sim-form-select {{ $errors->has('access_classification') ? 'border-sim-danger' : '' }}">
                            <option value="internal" {{ old('access_classification', 'internal') === 'internal' ? 'selected' : '' }}>Internal</option>
                            <option value="confidential" {{ old('access_classification') === 'confidential' ? 'selected' : '' }}>Rahasia</option>
                            <option value="restricted" {{ old('access_classification') === 'restricted' ? 'selected' : '' }}>Terbatas</option>
                        </select>
                        @if ($errors->has('access_classification'))
                            <p class="sim-form-error">{{ $errors->first('access_classification') }}</p>
                        @endif
                    </div>
                </div>

                <div class="sim-form-group">
                    <label class="sim-form-label">Deskripsi</label>
                    <textarea name="description" rows="3" class="sim-form-textarea {{ $errors->has('description') ? 'border-sim-danger' : '' }}" placeholder="Deskripsi bukti">{{ old('description') }}</textarea>
                    @if ($errors->has('description'))
                        <p class="sim-form-error">{{ $errors->first('description') }}</p>
                    @endif
                </div>

                <div class="flex justify-end pt-4 border-t border-slate-100">
                    <button type="submit" class="sim-btn sim-btn-primary">
                        Unggah Bukti
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
