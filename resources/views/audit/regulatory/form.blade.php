@extends('layouts.simmak')

@php
    $isCreate = is_null($source);
    $title = $isCreate ? 'Tambah Sumber Regulasi' : 'Edit Sumber Regulasi';
@endphp

@section('title', $title.' — SIMMAK PPKP')
@section('page-title', $title)

@section('content')
    <div class="mb-4">
        <x-action action="back" href="{{ route('audit.regulatory.index') }}" icon="chevron-left" label="Kembali ke Register" size="sm" />
    </div>

    @if (session('status'))
        <div class="sim-alert sim-alert-success mb-4">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="sim-alert sim-alert-error mb-4">{{ session('error') }}</div>
    @endif

    <div class="sim-card max-w-3xl">
        <div class="sim-card-header">
            <h3 class="sim-card-title">{{ $title }}</h3>
        </div>
        <div class="sim-card-body">
            <form method="POST" action="{{ $isCreate ? route('audit.regulatory.store') : route('audit.regulatory.update', $source) }}" class="space-y-5">
                @csrf
                @if (! $isCreate)
                    @method('PUT')
                @endif

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sim-form-group">
                        <label class="sim-form-label" for="document_number">Nomor Dokumen <span class="sim-form-label-required"></span></label>
                        <input type="text" id="document_number" name="document_number" maxlength="120" required
                               value="{{ old('document_number', $source?->document_number) }}"
                               class="sim-form-input {{ $errors->has('document_number') ? 'border-sim-danger' : '' }}"
                               placeholder="Contoh: KMK HK.01.07/MENKES/1983/2022">
                        @error('document_number')<p class="sim-form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="sim-form-group">
                        <label class="sim-form-label" for="issuer">Penerbit</label>
                        <input type="text" id="issuer" name="issuer" maxlength="255"
                               value="{{ old('issuer', $source?->issuer) }}"
                               class="sim-form-input {{ $errors->has('issuer') ? 'border-sim-danger' : '' }}"
                               placeholder="Contoh: Kementerian Kesehatan RI">
                        @error('issuer')<p class="sim-form-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="sim-form-group">
                    <label class="sim-form-label" for="title">Judul Dokumen <span class="sim-form-label-required"></span></label>
                    <input type="text" id="title" name="title" maxlength="255" required
                           value="{{ old('title', $source?->title) }}"
                           class="sim-form-input {{ $errors->has('title') ? 'border-sim-danger' : '' }}"
                           placeholder="Contoh: Standar Akreditasi Klinik">
                    @error('title')<p class="sim-form-error">{{ $message }}</p>@enderror
                </div>

                <div class="sim-form-group">
                    <label class="sim-form-label" for="source_url">URL Sumber Resmi</label>
                    <input type="url" id="source_url" name="source_url" maxlength="255"
                           value="{{ old('source_url', $source?->source_url) }}"
                           class="sim-form-input {{ $errors->has('source_url') ? 'border-sim-danger' : '' }}"
                           placeholder="https://jdih.kemkes.go.id/">
                    @error('source_url')<p class="sim-form-error">{{ $message }}</p>@enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-3">
                    <div class="sim-form-group">
                        <label class="sim-form-label" for="published_at">Tanggal Terbit</label>
                        <input type="date" id="published_at" name="published_at"
                               value="{{ old('published_at', $source?->published_at?->format('Y-m-d')) }}"
                               class="sim-form-input {{ $errors->has('published_at') ? 'border-sim-danger' : '' }}">
                        @error('published_at')<p class="sim-form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="sim-form-group">
                        <label class="sim-form-label" for="effective_from">Berlaku Sejak</label>
                        <input type="date" id="effective_from" name="effective_from"
                               value="{{ old('effective_from', $source?->effective_from?->format('Y-m-d')) }}"
                               class="sim-form-input {{ $errors->has('effective_from') ? 'border-sim-danger' : '' }}">
                        @error('effective_from')<p class="sim-form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="sim-form-group">
                        <label class="sim-form-label" for="effective_until">Berlaku Sampai</label>
                        <input type="date" id="effective_until" name="effective_until"
                               value="{{ old('effective_until', $source?->effective_until?->format('Y-m-d')) }}"
                               class="sim-form-input {{ $errors->has('effective_until') ? 'border-sim-danger' : '' }}">
                        @error('effective_until')<p class="sim-form-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="sim-form-group">
                    <label class="sim-form-label" for="verification_notes">Catatan</label>
                    <textarea id="verification_notes" name="verification_notes" rows="3"
                              class="sim-form-textarea {{ $errors->has('verification_notes') ? 'border-sim-danger' : '' }}"
                              placeholder="Catatan internal mengenai dokumen ini">{{ old('verification_notes', $source?->verification_notes) }}</textarea>
                    @error('verification_notes')<p class="sim-form-error">{{ $message }}</p>@enderror
                </div>

                <div class="flex justify-end gap-3 border-t border-sim-border-light pt-4">
                    <a href="{{ route('audit.regulatory.index') }}" class="sim-btn sim-btn-neutral">Batal</a>
                    <x-action action="edit" icon="check-circle" :label="$isCreate ? 'Simpan Sumber Regulasi' : 'Simpan Perubahan'" />
                </div>
            </form>
        </div>
    </div>

    @unless ($isCreate)
        @canany(['audit.standard.manage', 'audit.standard.review'])
            <div class="sim-card max-w-3xl mt-4">
                <div class="sim-card-header flex items-center justify-between">
                    <h3 class="sim-card-title">Verifikasi Dokumen</h3>
                    <span class="sim-badge sim-badge-status {{ \App\Http\Controllers\RegulatorySourceController::statusBadgeClass($source->verification_status) }}">
                        {{ \App\Http\Controllers\RegulatorySourceController::statusLabel($source->verification_status) }}
                    </span>
                </div>
                <div class="sim-card-body">
                    <p class="text-sm text-sim-muted mb-4">
                        Status selain <strong>Belum Diverifikasi</strong> adalah klaim kepatuhan. Wajib disertai
                        catatan bukti pemeriksaan dokumen resmi (mis. JDIH Kemenkes).
                        @if ($source->verified_at)
                            Terakhir diverifikasi {{ $source->verified_at->format('d/m/Y H:i') }}
                            oleh {{ $source->verifier?->name ?? 'pengguna terhapus' }}.
                        @endif
                    </p>

                    <form method="POST" action="{{ route('audit.regulatory.verify', $source) }}" class="space-y-4">
                        @csrf

                        <div class="sim-form-group">
                            <label class="sim-form-label" for="verification_status">Status Verifikasi</label>
                            <select id="verification_status" name="verification_status" class="sim-form-select">
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}" @selected(strtoupper((string) $source->verification_status) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="sim-form-group">
                            <label class="sim-form-label" for="verify_notes">Catatan Bukti Verifikasi</label>
                            <textarea id="verify_notes" name="verification_notes" rows="3"
                                      class="sim-form-textarea {{ $errors->has('verification_notes') ? 'border-sim-danger' : '' }}"
                                      placeholder="Contoh: Diperiksa via JDIH Kemenkes pada 27/10/2026, dokumen asli cocok.">{{ old('verification_notes') }}</textarea>
                            @error('verification_notes')<p class="sim-form-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="flex justify-end border-t border-sim-border-light pt-4">
                            <x-action action="save" icon="check-circle" label="Simpan Status Verifikasi" />
                        </div>
                    </form>
                </div>
            </div>
        @endcanany
    @endunless
@endsection


