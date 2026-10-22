@extends('layouts.simmak')

@section('title', 'Tambah Standar Audit — SIMMAK PPKP')
@section('page-title', 'Tambah Standar Audit')

@section('content')
    <div class="mb-4">
        <a href="{{ route('audit.standards.index') }}" class="sim-btn sim-btn-outline sim-btn-sm">← Kembali ke Daftar Standar</a>
    </div>

    <div class="sim-card max-w-2xl">
        <div class="sim-card-header">
            <h3 class="sim-card-title">Tambah Standar Audit</h3>
        </div>
        <div class="sim-card-body space-y-4">
            <form method="POST" action="{{ route('audit.standards.store') }}" class="space-y-4">
                @csrf

                <div class="sim-form-group">
                    <label class="sim-form-label">Kode Standar <span class="text-red-500">*</span></label>
                    <input type="text" name="code" maxlength="80"
                           value="{{ old('code') }}"
                           class="sim-form-input {{ $errors->has('code') ? 'border-sim-danger' : '' }}"
                           placeholder="Contoh: FKTP-01" required>
                    @if ($errors->has('code'))
                        <p class="sim-form-error">{{ $errors->first('code') }}</p>
                    @endif
                </div>

                <div class="sim-form-group">
                    <label class="sim-form-label">Judul Standar <span class="text-red-500">*</span></label>
                    <input type="text" name="title" maxlength="255"
                           value="{{ old('title') }}"
                           class="sim-form-input {{ $errors->has('title') ? 'border-sim-danger' : '' }}"
                           placeholder="Contoh: Standar Pelayanan Klinik Pratama" required>
                    @if ($errors->has('title'))
                        <p class="sim-form-error">{{ $errors->first('title') }}</p>
                    @endif
                </div>

                <div class="sim-form-group">
                    <label class="sim-form-label">Sumber Regulasi</label>
                    <select name="source_document_id" class="sim-form-select {{ $errors->has('source_document_id') ? 'border-sim-danger' : '' }}">
                        <option value="">— Pilih sumber regulasi —</option>
                        @foreach ($sources as $source)
                            <option value="{{ $source->id }}" {{ old('source_document_id') === $source->id ? 'selected' : '' }}>
                                {{ $source->document_number }} — {{ \Illuminate\Support\Str::limit($source->title, 60) }}
                            </option>
                        @endforeach
                    </select>
                    @if ($errors->has('source_document_id'))
                        <p class="sim-form-error">{{ $errors->first('source_document_id') }}</p>
                    @endif
                    <p class="sim-form-hint">Pilih sumber regulasi yang telah diverifikasi (jika tersedia).</p>
                </div>

                <div class="flex justify-end pt-4 border-t border-sim-border-light">
                    <x-action icon="check-circle" label="Simpan Standar" />
                </div>
            </form>
        </div>
    </div>
@endsection
