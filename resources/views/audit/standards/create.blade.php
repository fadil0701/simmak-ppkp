@extends('layouts.simmak')

@section('title', 'Tambah Standar Audit — SIMMAK PPKP')
@section('page-title', 'Tambah Standar Audit')

@section('content')
    <div class="mx-auto max-w-3xl space-y-5">
        <div class="sim-page-header">
            <div>
                <h2 class="sim-page-title">Tambah Standar Audit</h2>
                <p class="sim-page-subtitle">
                    Standar adalah acuan penilaian. Setelah standar dibuat, tambahkan versi instrumen
                    berisi daftar EP — bisa satu per satu atau sekaligus lewat impor Excel.
                </p>
            </div>
            <a href="{{ route('audit.standards.index') }}" class="sim-btn sim-btn-back">
                ←Kembali
            </a>
        </div>

        <form method="POST" action="{{ route('audit.standards.store') }}" class="space-y-5">
            @csrf

            <x-form-section step="1" title="Identitas Standar"
                            description="Kode dipakai sebagai penanda singkat dan harus unik di organisasi Anda.">
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="sm:col-span-1">
                        <label class="sim-form-label" for="code">
                            Kode Standar <span class="sim-form-label-required"></span>
                        </label>
                        <input id="code" type="text" name="code" maxlength="80" required
                               value="{{ old('code') }}"
                               class="sim-form-input font-mono {{ $errors->has('code') ? 'border-sim-danger' : '' }}"
                               placeholder="FKTP-01">
                        <p class="sim-form-hint">Singkat, tanpa spasi. Contoh: TKK, PMKP, FKTP-01.</p>
                        @error('code')
                            <p class="sim-form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="sim-form-label" for="title">
                            Judul Standar <span class="sim-form-label-required"></span>
                        </label>
                        <input id="title" type="text" name="title" maxlength="255" required
                               value="{{ old('title') }}"
                               class="sim-form-input {{ $errors->has('title') ? 'border-sim-danger' : '' }}"
                               placeholder="Standar Pelayanan Klinik Pratama">
                        <p class="sim-form-hint">Nama lengkap standar seperti tertulis pada dokumen acuan.</p>
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
                            <option value="{{ $source->id }}" @selected(old('source_document_id') === $source->id)>
                                {{ $source->document_number }} — {{ \Illuminate\Support\Str::limit($source->title, 70) }}
                            </option>
                        @endforeach
                    </select>
                    <p class="sim-form-hint">Opsional. Pilih regulasi acuan bila sudah terdaftar dan terverifikasi.</p>
                    @error('source_document_id')
                        <p class="sim-form-error">{{ $message }}</p>
                    @enderror
                </div>
            </x-form-section>

            <x-form-section step="2" title="Status Awal"
                            description="Standar baru umumnya dimulai sebagai draf. Status Aktif menandakan standar siap dipakai audit.">
                <div>
                    <label class="sim-form-label" for="status">Status</label>
                    <select id="status" name="status"
                            class="sim-form-select {{ $errors->has('status') ? 'border-sim-danger' : '' }}">
                        @foreach (\App\Services\AuditStandardService::STANDARD_STATUSES as $statusOption)
                            <option value="{{ $statusOption }}" @selected(old('status', 'draft') === $statusOption)>
                                {{ \App\Support\UiLabel::standardStatus($statusOption) }}
                            </option>
                        @endforeach
                    </select>
                    <p class="sim-form-hint">
                        Status <strong>Aktif</strong> hanya dapat dipilih setelah standar punya minimal satu
                        versi instrumen. Bila belum, standar tetap tersimpan sebagai Draf.
                    </p>
                    @error('status')
                        <p class="sim-form-error">{{ $message }}</p>
                    @enderror
                </div>
            </x-form-section>

            <div class="sim-save-bar">
                <span class="text-sm text-sim-muted">Data bertanda <span class="text-sim-danger">*</span> wajib diisi.</span>
                <div class="flex items-center gap-2">
                    <a href="{{ route('audit.standards.index') }}" class="sim-btn sim-btn-neutral">Batal</a>
                    <x-action action="save" icon="check-circle" label="Simpan Standar" />
                </div>
            </div>
        </form>
    </div>
@endsection

