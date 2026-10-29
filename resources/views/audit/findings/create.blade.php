@extends('layouts.simmak')

@section('title', 'Tambah Temuan Audit — SIMMAK PPKP')
@section('page-title', 'Tambah Temuan Audit')

@section('content')
    <div class="mb-4">
        <x-action action="back" href="{{ route('findings.index') }}" icon="chevron-left" label="Kembali" size="sm" />
    </div>

    <div class="simmak-card max-w-3xl">
        <div class="simmak-card-header">
            <h3 class="font-semibold text-slate-800">Tambah Temuan Audit</h3>
        </div>
        <div class="simmak-card-body space-y-4">
            <form method="POST" action="{{ route('findings.store') }}" class="space-y-4">
                @csrf

                @if ($clinicalAudit)
                    <input type="hidden" name="clinical_audit_id" value="{{ $clinicalAudit->id }}">
                    <div class="rounded-lg border border-teal-100 bg-teal-50 p-3 text-sm text-teal-800">
                        <span class="text-xs font-medium text-teal-700">Temuan dari Audit Klinis</span>
                        <p class="mt-1 font-medium">{{ $clinicalAudit->audit_number }} — {{ $clinicalAudit->title }}</p>
                        <p class="mt-1 text-xs text-teal-700">
                            Temuan ini masuk ke daftar Temuan Audit bersama. Klinik mengikuti audit klinis
                            ({{ $clinicalAudit->clinic?->name ?? '—' }}); eksekusi audit tidak diperlukan.
                        </p>
                    </div>
                @endif

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sim-form-group">
                        <label class="sim-form-label">Eksekusi Audit @unless ($clinicalAudit) <span class="sim-form-label-required"></span> @endunless</label>
                        <select name="audit_execution_id" class="sim-form-select {{ $errors->has('audit_execution_id') ? 'border-sim-danger' : '' }}" @unless ($clinicalAudit) required @endunless>
                            <option value="">{{ $clinicalAudit ? '— Tidak terkait eksekusi (audit klinis) —' : '— Pilih eksekusi —' }}</option>
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
                        <label class="sim-form-label">Klinik <span class="sim-form-label-required"></span></label>
                        <select name="clinic_id" class="sim-form-select {{ $errors->has('clinic_id') ? 'border-sim-danger' : '' }}" required>
                            <option value="">— Pilih klinik —</option>
                            @foreach ($clinics as $clinic)
                                <option value="{{ $clinic->id }}" {{ old('clinic_id', $clinicalAudit?->clinic_id) === $clinic->id ? 'selected' : '' }}>
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
                    <label class="sim-form-label">Kode Temuan</label>
                    <input type="text" value="{{ $suggestedNumber ?? '' }}" readonly tabindex="-1"
                           class="sim-form-input bg-sim-surface-alt">
                    <p class="text-xs text-sim-muted mt-1">Dibuat otomatis saat data disimpan. Tidak dapat diubah.</p>
                </div>

                <div class="sim-form-group">
                    <label class="sim-form-label">Judul Temuan <span class="sim-form-label-required"></span></label>
                    <input type="text" name="title" maxlength="255"
                           value="{{ old('title') }}"
                           class="sim-form-input {{ $errors->has('title') ? 'border-sim-danger' : '' }}"
                           placeholder="Contoh: Tidak ada prosedur sterilisasi alat medis" required>
                    @if ($errors->has('title'))
                        <p class="sim-form-error">{{ $errors->first('title') }}</p>
                    @endif
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sim-form-group">
                        <label class="sim-form-label">Tingkat Risiko <span class="sim-form-label-required"></span></label>
                        <select name="severity" class="sim-form-select {{ $errors->has('severity') ? 'border-sim-danger' : '' }}" required>
                            <option value="minor">Ringan</option>
                            <option value="major">Major</option>
                            <option value="critical">Kritikal</option>
                        </select>
                        @if ($errors->has('severity'))
                            <p class="sim-form-error">{{ $errors->first('severity') }}</p>
                        @endif
                    </div>

                    <div class="sim-form-group">
                        <label class="sim-form-label">Tipe Temuan</label>
                        <select name="finding_type" class="sim-form-select {{ $errors->has('finding_type') ? 'border-sim-danger' : '' }}">
                            <option value="non_conformity">Ketidaksesuaian</option>
                            <option value="observation">Observasi</option>
                            <option value="improvement">Peluang Perbaikan</option>
                        </select>
                        @if ($errors->has('finding_type'))
                            <p class="sim-form-error">{{ $errors->first('finding_type') }}</p>
                        @endif
                    </div>
                </div>

                <div class="sim-form-group">
                    <label class="sim-form-label">Deskripsi Kondisi Aktual</label>
                    <textarea name="condition" rows="3" class="sim-form-textarea {{ $errors->has('condition') ? 'border-sim-danger' : '' }}" placeholder="Deskripsikan kondisi yang ditemukan saat audit">{{ old('condition') }}</textarea>
                    @if ($errors->has('condition'))
                        <p class="sim-form-error">{{ $errors->first('condition') }}</p>
                    @endif
                </div>

                <div class="sim-form-group">
                    <label class="sim-form-label">Persyaratan yang Harus Dipenuhi</label>
                    <textarea name="requirement" rows="3" class="sim-form-textarea {{ $errors->has('requirement') ? 'border-sim-danger' : '' }}" placeholder="Apa yang seharusnya terpenuhi sesuai standar">{{ old('requirement') }}</textarea>
                    @if ($errors->has('requirement'))
                        <p class="sim-form-error">{{ $errors->first('requirement') }}</p>
                    @endif
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sim-form-group">
                        <label class="sim-form-label">Ditujukan Kepada (PIC)</label>
                        <select name="owner_user_id" class="sim-form-select {{ $errors->has('owner_user_id') ? 'border-sim-danger' : '' }}">
                            <option value="">— Pilih PIC —</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" {{ old('owner_user_id') === $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('owner_user_id'))
                            <p class="sim-form-error">{{ $errors->first('owner_user_id') }}</p>
                        @endif
                    </div>

                    <div class="sim-form-group">
                        <label class="sim-form-label">Tenggat Penyelesaian</label>
                        <input type="date" name="due_at"
                               value="{{ old('due_at') }}"
                               class="sim-form-input {{ $errors->has('due_at') ? 'border-sim-danger' : '' }}">
                        @if ($errors->has('due_at'))
                            <p class="sim-form-error">{{ $errors->first('due_at') }}</p>
                        @endif
                    </div>
                </div>

                <div class="sim-form-group">
                    <label class="sim-form-label">Rekomendasi Perbaikan</label>
                    <textarea name="recommendation" rows="3" class="sim-form-textarea {{ $errors->has('recommendation') ? 'border-sim-danger' : '' }}" placeholder="Rekomendasi tindakan perbaikan">{{ old('recommendation') }}</textarea>
                    @if ($errors->has('recommendation'))
                        <p class="sim-form-error">{{ $errors->first('recommendation') }}</p>
                    @endif
                </div>

                <div class="sim-form-group">
                    <label class="sim-form-label">Status Awal</label>
                    <select name="status" class="sim-form-select {{ $errors->has('status') ? 'border-sim-danger' : '' }}">
                        <option value="draft">Draf</option>
                        <option value="under_review">Tinjauan</option>
                        <option value="confirmed">Dikonfirmasi</option>
                    </select>
                    @if ($errors->has('status'))
                        <p class="sim-form-error">{{ $errors->first('status') }}</p>
                    @endif
                </div>

                <div class="flex justify-end pt-4 border-t border-slate-100">
                    <x-action action="save" icon="check-circle" label="Simpan Temuan" />
                </div>
            </form>
        </div>
    </div>
@endsection
