@extends('layouts.simmak')

@section('title', 'Laporan Audit — SIMMAK PPKP')
@section('page-title', 'Laporan Audit Internal')
@section('page-subtitle', 'Ekspor laporan audit berdasarkan periode, klinik, jenis audit, dan status temuan')

@section('breadcrumb')
    <li class="flex items-center gap-1 text-sim-muted">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
        <span>Laporan</span>
    </li>
@endsection

@section('content')
    <div class="sim-card">
        <div class="sim-card-header flex items-center justify-between">
            <div>
                <h3 class="sim-card-title">Filter Laporan</h3>
                <p class="text-xs text-sim-muted">Pilih rentang periode dan cakupan laporan sebelum mengekspor.</p>
            </div>

            @can('audit.report.publish')
                <a href="#" class="sim-btn sim-btn-primary sim-btn-sm" onclick="document.getElementById('exportForm').submit()">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h11a3 3 0 003-3v-6m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Ekspor Laporan
                </a>
            @endcan
        </div>

        <div class="sim-card-body">
            <form id="exportForm" method="GET" action="{{ route('audit.reports.export') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="sim-form-group">
                    <label class="sim-form-label">Dari Tanggal</label>
                    <input type="date" name="date_from" class="sim-form-input" value="{{ request('date_from') }}">
                </div>

                <div class="sim-form-group">
                    <label class="sim-form-label">Sampai Tanggal</label>
                    <input type="date" name="date_to" class="sim-form-input" value="{{ request('date_to') }}">
                </div>

                <div class="sim-form-group">
                    <label class="sim-form-label">Klinik</label>
                    <select name="clinic_id" class="sim-form-select">
                        <option value="">Semua Klinik</option>
                        @foreach ($clinics as $clinic)
                            <option value="{{ $clinic->id }}" {{ request('clinic_id') === $clinic->id ? 'selected' : '' }}>
                                {{ $clinic->code }} — {{ $clinic->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="sim-form-group">
                    <label class="sim-form-label">Jenis Audit</label>
                    <select name="audit_type" class="sim-form-select">
                        <option value="">Semua Jenis</option>
                        <option value="periodik" {{ request('audit_type') === 'periodik' ? 'selected' : '' }}>Audit Periodik</option>
                        <option value="tematik" {{ request('audit_type') === 'tematik' ? 'selected' : '' }}>Audit Tematik</option>
                        <option value="khusus" {{ request('audit_type') === 'khusus' ? 'selected' : '' }}>Audit Khusus</option>
                        <option value="ulang" {{ request('audit_type') === 'ulang' ? 'selected' : '' }}>Audit Ulang</option>
                    </select>
                </div>

                <div class="sim-form-group sm:col-span-2 lg:col-span-2">
                    <label class="sim-form-label">Status Temuan</label>
                    <select name="finding_status" class="sim-form-select">
                        <option value="">Semua Status</option>
                        @foreach (['draft', 'under_review', 'confirmed', 'action_required', 'pending_verification', 'closed'] as $s)
                            <option value="{{ $s }}" {{ request('finding_status') === $s ? 'selected' : '' }}>
                                {{ \App\Support\UiLabel::findingStatus($s) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="sim-form-group sm:col-span-2 lg:col-span-2">
                    <label class="sim-form-label">Format</label>
                    <select name="format" class="sim-form-select">
                        <option value="pdf">PDF</option>
                        <option value="excel">Excel (.xlsx)</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    @can('audit.report.publish')
        <div class="sim-card mt-6">
            <div class="sim-card-header">
                <h3 class="sim-card-title">Panduan Ekspor</h3>
            </div>
            <div class="sim-card-body space-y-3">
                <div class="flex items-start gap-3 p-3 rounded-lg bg-sim-info-bg border border-sim-info/20">
                    <div class="flex h-6 w-6 items-center justify-center rounded-md bg-sim-info text-white text-xs font-bold">i</div>
                    <div class="text-sm text-sim-ink">
                        <strong>Laporan PDF</strong> mencakup rencana dan realisasi audit, ringkasan hasil, rincian pemeriksaan, daftar temuan dan bukti, rekomendasi, rencana tindakan, progres tindak lanjut, hasil verifikasi, dan riwayat persetujuan.
                    </div>
                </div>
                <div class="flex items-start gap-3 p-3 rounded-lg bg-sim-success-bg border border-sim-success/20">
                    <div class="flex h-6 w-6 items-center justify-center rounded-md bg-sim-success text-white text-xs font-bold">✓</div>
                    <div class="text-sm text-sim-ink">
                        <strong>Laporan Excel</strong> diformat untuk dokumentasi internal dan dapat dibuka dengan spreadsheet standar. Semua angka dihitung dari data aktual dalam database.
                    </div>
                </div>
                <div class="text-xs text-sim-muted">
                    Laporan final memiliki identitas dokumen, periode, versi, tanggal penerbitan, status, dan informasi pengesahan. Laporan bukan sertifikat atau hasil akreditasi resmi.
                </div>
            </div>
        </div>
    @endcan

    @cannot('audit.report.publish')
        <div class="sim-card mt-6">
            <div class="sim-card-body p-6 text-center">
                <p class="text-sm text-sim-muted">Anda tidak memiliki kewenangan untuk mengekspor laporan audit. Hubungi administrator sistem untuk izin <code>audit.report.publish</code>.</p>
            </div>
        </div>
    @endcannot
@endsection