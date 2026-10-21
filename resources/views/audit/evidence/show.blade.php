@extends('layouts.simmak')

@section('title', 'Detail Bukti Audit — SIMMAK PPKP')
@section('page-title', 'Detail Bukti Audit')
@section('page-subtitle', 'Metadata dan informasi akses berkas bukti objektif')

@section('breadcrumb')
    <li class="flex items-center gap-1 text-sim-muted">
        <a href="{{ route('audit.evidence.index') }}">Bukti Audit</a>
        <svg class="h-3.5 w-3.5 text-sim-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
        <span>Detail Bukti</span>
    </li>
@endsection

@section('content')
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="sim-page-title">Detail Bukti Audit</h2>
            <p class="sim-page-subtitle">Berkas disimpan pada private storage dan hanya dapat diakses dengan otorisasi yang valid.</p>
        </div>

        <a href="{{ route('audit.evidence.download', $evidence) }}" class="sim-btn sim-btn-primary inline-flex items-center gap-2">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Unduh Berkas
        </a>

        <a href="{{ route('audit.evidence.index') }}" class="sim-btn sim-btn-outline inline-flex items-center gap-2">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            Kembali
        </a>
    </div>

    <div class="sim-card">
        <div class="sim-card-header">
            <h3 class="sim-card-title">Informasi Berkas</h3>
        </div>
        <div class="sim-card-body grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="sim-form-group">
                <label class="sim-form-label">Nama Berkas Asli</label>
                <p class="text-sm text-sim-ink break-all">{{ $evidence->original_filename }}</p>
            </div>
            <div class="sim-form-group">
                <label class="sim-form-label">Tipe MIME</label>
                <p class="text-sm text-sim-ink text-sim-mono">{{ $evidence->mime_type }}</p>
            </div>
            <div class="sim-form-group">
                <label class="sim-form-label">Ukuran</label>
                <p class="text-sm text-sim-ink">{{ number_format($evidence->size_bytes / 1024, 1) }} KB</p>
            </div>
            <div class="sim-form-group">
                <label class="sim-form-label">Klasifikasi Akses</label>
                <span class="sim-badge sim-badge-status
                    @switch($evidence->access_classification)
                        @case('confidential') border-sim-warning bg-sim-warning-bg text-sim-warning
                        @case('restricted') border-sim-danger bg-sim-danger-bg text-sim-danger
                        @default border-sim-info bg-sim-info-bg text-sim-info
                    @endswitch
                ">
                    {{ ucfirst($evidence->access_classification) }}
                </span>
            </div>
            <div class="sim-form-group sm:col-span-2">
                <label class="sim-form-label">Deskripsi</label>
                <p class="text-sm text-sim-ink">{{ $evidence->description ?? '—' }}</p>
            </div>
            <div class="sim-form-group">
                <label class="sim-form-label">Diunggah Oleh</label>
                <p class="text-sm text-sim-ink">{{ $evidence->uploader?->name ?? '—' }}</p>
            </div>
            <div class="sim-form-group">
                <label class="sim-form-label">Waktu Unggah</label>
                <p class="text-sm text-sim-ink text-sim-mono">{{ $evidence->uploaded_at?->format('d M Y H:i') ?? '—' }}</p>
            </div>
            <div class="sim-form-group sm:col-span-2 lg:col-span-4">
                <label class="sim-form-label">Konteks Audit</label>
                <div class="text-sm text-sim-muted">
                    @if ($evidence->execution)
                        Eksekusi: <strong>{{ $evidence->execution->execution_code }}</strong> — {{ $evidence->execution->title }}<br>
                        Klinik: <strong>{{ $evidence->execution->clinic?->code }}</strong> — {{ $evidence->execution->clinic?->name }}
                    @elseif ($evidence->clinic)
                        Klinik: <strong>{{ $evidence->clinic->code }}</strong> — {{ $evidence->clinic->name }}
                    @else
                        Tidak terikat ke eksekusi atau klinik tertentu.
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="sim-card mt-6">
        <div class="sim-card-header">
            <h3 class="sim-card-title">Keamanan</h3>
        </div>
        <div class="sim-card-body">
            <ul class="divide-y divide-sim-border-light">
                <li class="px-4 py-3 text-sm">
                    <div class="font-medium text-sim-ink">Penyimpanan privat</div>
                    <div class="text-xs text-sim-muted">Berkas disimpan pada private storage, tidak ada URL publik yang dapat diakses tanpa otorisasi.</div>
                </li>
                <li class="px-4 py-3 text-sm">
                    <div class="font-medium text-sim-ink">Otorisasi setiap unduh</div>
                    <div class="text-xs text-sim-muted">Setiap permintaan unduhan dicek organisasi, klinik, dan penugasan sebelum berkas diserahkan.</div>
                </li>
                <li class="px-4 py-3 text-sm">
                    <div class="font-medium text-sim-ink">Nama penyimpanan acak</div>
                    <div class="text-xs text-sim-muted">Nama berkas pengguna tidak digunakan sebagai nama penyimpanan tanpa normalisasi.</div>
                </li>
            </ul>
        </div>
    </div>
@endsection