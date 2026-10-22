@extends('layouts.simmak')

@section('title', 'Tambah Versi Instrumen — SIMMAK PPKP')
@section('page-title', 'Tambah Versi Instrumen')

@section('content')
    <div class="mb-4">
        <a href="{{ route('audit.standards.show', $standard) }}" class="sim-btn sim-btn-outline sim-btn-sm">← Kembali ke Detail Standar</a>
    </div>

    <div class="sim-card max-w-lg">
        <div class="sim-card-header">
            <h3 class="sim-card-title">Tambah Versi Instrumen</h3>
        </div>
        <div class="sim-card-body space-y-4">
            <form method="POST" action="{{ route('audit.standards.versions.store', $standard) }}" class="space-y-4">
                @csrf

                <div class="sim-form-group">
                    <label class="sim-form-label">Label Versi <span class="text-red-500">*</span></label>
                    <input type="text" name="version_label" maxlength="80"
                           value="{{ old('version_label') }}"
                           class="sim-form-input {{ $errors->has('version_label') ? 'border-sim-danger' : '' }}"
                           placeholder="Contoh: 1.0.0" required>
                    @if ($errors->has('version_label'))
                        <p class="sim-form-error">{{ $errors->first('version_label') }}</p>
                    @endif
                    <p class="sim-form-hint">Contoh: 1.0, 1.1, 2.0. Versi yang digunakan dalam audit tidak dapat diubah secara retroaktif.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sim-form-group">
                        <label class="sim-form-label">Tanggal Berlaku Dari</label>
                        <input type="date" name="effective_from"
                               value="{{ old('effective_from') }}"
                               class="sim-form-input {{ $errors->has('effective_from') ? 'border-sim-danger' : '' }}">
                        @if ($errors->has('effective_from'))
                            <p class="sim-form-error">{{ $errors->first('effective_from') }}</p>
                        @endif
                    </div>

                    <div class="sim-form-group">
                        <label class="sim-form-label">Tanggal Berlaku Sampai</label>
                        <input type="date" name="effective_until"
                               value="{{ old('effective_until') }}"
                               class="sim-form-input {{ $errors->has('effective_until') ? 'border-sim-danger' : '' }}">
                        @if ($errors->has('effective_until'))
                            <p class="sim-form-error">{{ $errors->first('effective_until') }}</p>
                        @endif
                    </div>
                </div>

                <div class="sim-form-group">
                    <label class="sim-form-label">Sumber Snapshot (catatan)</label>
                    <textarea name="source_snapshot" rows="3"
                              class="sim-form-textarea {{ $errors->has('source_snapshot') ? 'border-sim-danger' : '' }}"
                              placeholder="Catatan sumber yang digunakan untuk versi ini">{{ old('source_snapshot') }}</textarea>
                    @if ($errors->has('source_snapshot'))
                        <p class="sim-form-error">{{ $errors->first('source_snapshot') }}</p>
                    @endif
                </div>

                <div class="flex justify-end pt-4 border-t border-sim-border-light">
                    <x-action icon="check-circle" label="Simpan Versi" />
                </div>
            </form>
        </div>
    </div>
@endsection
