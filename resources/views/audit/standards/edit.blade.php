@extends('layouts.simmak')

@section('title', 'Edit Standar Audit — SIMMAK PPKP')
@section('page-title', 'Edit Standar Audit')

@section('content')
    <div class="mx-auto max-w-3xl space-y-5">
        <div class="sim-page-header">
            <div>
                <h2 class="sim-page-title">Edit Standar Audit</h2>
                <p class="sim-page-subtitle">
                    <span class="sim-code">{{ $standard->code }}</span> — {{ $standard->title }}
                </p>
            </div>
            <a href="{{ route('audit.standards.show', $standard) }}" class="sim-btn sim-btn-back">
                ← Kembali ke Detail
            </a>
        </div>

        <form method="POST" action="{{ route('audit.standards.update', $standard) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <x-form-section step="1" title="Identitas Standar"
                            description="Perubahan kode hanya mengubah penanda standar; isi instrumen tidak terpengaruh.">
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="sm:col-span-1">
                        <label class="sim-form-label" for="code">
                            Kode Standar <span class="sim-form-label-required"></span>
                        </label>
                        <input id="code" type="text" name="code" maxlength="80" required
                               value="{{ old('code', $standard->code) }}"
                               class="sim-form-input font-mono {{ $errors->has('code') ? 'border-sim-danger' : '' }}"
                               placeholder="FKTP-01">
                        @error('code')
                            <p class="sim-form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="sim-form-label" for="title">
                            Judul Standar <span class="sim-form-label-required"></span>
                        </label>
                        <input id="title" type="text" name="title" maxlength="255" required
                               value="{{ old('title', $standard->title) }}"
                               class="sim-form-input {{ $errors->has('title') ? 'border-sim-danger' : '' }}"
                               placeholder="Standar Pelayanan Klinik Pratama">
                        @error('title')
                            <p class="sim-form-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="sim-form-label" for="source_document_id">Sumber Regulasi</label>
                    <select id="source_document_id" name="source_document_id"
                            class="sim-form-select {{ $errors->has('source_document_id') ? 'border-sim-danger' : '' }}">
                        <option value="">— Tidak terkait regulasi tertentu —</option>
                        @foreach ($sources as $source)
                            <option value="{{ $source->id }}" @selected(old('source_document_id', $standard->source_document_id) === $source->id)>
                                {{ $source->document_number }} — {{ \Illuminate\Support\Str::limit($source->title, 70) }}
                            </option>
                        @endforeach
                    </select>
                    @error('source_document_id')
                        <p class="sim-form-error">{{ $message }}</p>
                    @enderror
                </div>
            </x-form-section>

            <x-form-section step="2" title="Status Standar"
                            description="Status menentukan apakah standar dipakai dalam perencanaan audit.">
                <div class="flex flex-wrap items-center gap-2 rounded-lg border border-sim-border-light bg-sim-surface-alt px-3 py-2">
                    <span class="text-xs uppercase tracking-wide text-sim-muted">Status saat ini</span>
                    <span class="sim-badge sim-badge-status {{ \App\Support\UiLabel::standardStatusTone($standard->status) }}">
                        {{ \App\Support\UiLabel::standardStatus($standard->status) }}
                    </span>
                    <span class="ml-auto text-xs text-sim-muted">
                        {{ $standard->instrumentVersions->count() }} versi instrumen
                    </span>
                </div>

                <div>
                    <label class="sim-form-label" for="status">Ubah status menjadi</label>
                    <select id="status" name="status"
                            class="sim-form-select {{ $errors->has('status') ? 'border-sim-danger' : '' }}">
                        @foreach (\App\Services\AuditStandardService::STANDARD_STATUSES as $statusOption)
                            <option value="{{ $statusOption }}" @selected(old('status', $standard->status) === $statusOption)>
                                {{ \App\Support\UiLabel::standardStatus($statusOption) }}
                            </option>
                        @endforeach
                    </select>
                    <p class="sim-form-hint">
                        Status <strong>Aktif</strong> menuntut minimal satu versi instrumen.
                        Gunakan <strong>Diarsipkan</strong> untuk standar yang tidak dipakai lagi.
                    </p>
                    @error('status')
                        <p class="sim-form-error">{{ $message }}</p>
                    @enderror
                </div>
            </x-form-section>

            <div class="sim-save-bar">
                <span class="text-sm text-sim-muted">Perubahan tercatat pada riwayat audit.</span>
                <div class="flex items-center gap-2">
                    <a href="{{ route('audit.standards.show', $standard) }}" class="sim-btn sim-btn-neutral">Batal</a>
                    <x-action action="edit" icon="check-circle" label="Simpan Perubahan" />
                </div>
            </div>
        </form>
    </div>
@endsection

