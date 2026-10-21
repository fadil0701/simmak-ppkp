@extends('layouts.simmak')

@section('title', '{{ $report->report_number }} — SIMMAK PPKP')
@section('page-title', 'Detail Laporan: {{ $report->report_number }}')

@section('content')
    <div class="sim-page-header">
        <div>
            <h2 class="sim-page-title">{{ $report->report_number }} — {{ $report->title }}</h2>
            <p class="sim-page-subtitle">
                Tipe: {{ ucfirst(str_replace('_', ' ', $report->report_type)) }} ·
                Status: {{ ucfirst($report->status) }}
            </p>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('audit.reports.download-pdf', $report) }}" class="sim-btn sim-btn-secondary sim-btn-sm">
                <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Unduh PDF
            </a>
            <a href="{{ route('audit.reports.download-excel', $report) }}" class="sim-btn sim-btn-secondary sim-btn-sm">
                <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Unduh Excel
            </a>
            <a href="{{ route('audit.reports.index') }}" class="sim-btn sim-btn-outline">
                Kembali
            </a>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="sim-card lg:col-span-2">
            <div class="sim-card-header">
                <h3 class="sim-card-title">Informasi Laporan</h3>
            </div>
            <div class="sim-card-body space-y-3">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <span class="text-xs text-sim-muted">Nomor Laporan</span>
                        <div class="font-mono text-sm text-sim-ink">{{ $report->report_number }}</div>
                    </div>
                    <div>
                        <span class="text-xs text-sim-muted">Tipe Laporan</span>
                        <div class="text-sm text-sim-ink">{{ ucfirst(str_replace('_', ' ', $report->report_type)) }}</div>
                    </div>
                    <div>
                        <span class="text-xs text-sim-muted">Status</span>
                        <span class="sim-badge sim-badge-status {{ $report->status === 'published' ? 'border-sim-success bg-sim-success-bg text-sim-success' : 'border-sim-neutral bg-sim-neutral-bg text-sim-neutral' }}">
                            {{ ucfirst($report->status) }}
                        </span>
                    </div>
                    <div class="sm:col-span-2">
                        <span class="text-xs text-sim-muted">Periode</span>
                        <div class="text-sm text-sim-ink">
                            {{ $report->period_start?->format('d M Y') ?? '-' }} – {{ $report->period_end?->format('d M Y') ?? '-' }}
                        </div>
                    </div>
                    <div>
                        <span class="text-xs text-sim-muted">Klinik</span>
                        <div class="text-sm text-sim-ink">{{ $report->clinic?->name ?? '—' }}</div>
                    </div>
                    <div>
                        <span class="text-xs text-sim-muted">Eksekusi</span>
                        <div class="text-sm text-sim-ink">{{ $report->execution?->execution_code ?? '—' }}</div>
                    </div>
                    <div>
                        <span class="text-xs text-sim-muted">Dibuat Oleh</span>
                        <div class="text-sm text-sim-ink">{{ $report->generator?->name ?? '—' }}</div>
                    </div>
                    <div>
                        <span class="text-xs text-sim-muted">Tanggal Dibuat</span>
                        <div class="text-sm text-sim-ink">{{ $report->generated_at?->format('d M Y H:i') ?? '—' }}</div>
                    </div>
                    <div>
                        <span class="text-xs text-sim-muted">Ukuran File PDF</span>
                        <div class="text-sm text-sim-ink">{{ number_format($report->file_size / 1024, 1) }} KB</div>
                    </div>
                    <div>
                        <span class="text-xs text-sim-muted">Checksum (SHA-256)</span>
                        <div class="text-xs font-mono text-sim-muted break-all">{{ $report->checksum ?? '—' }}</div>
                    </div>
                </div>

                @if ($report->executive_summary)
                    <div class="mt-4 pt-4 border-t border-sim-border-light">
                        <span class="text-xs text-sim-muted font-semibold uppercase">Ringkasan Eksekutif</span>
                        <div class="mt-1 text-sm text-sim-ink whitespace-pre-wrap">{{ $report->executive_summary }}</div>
                    </div>
                @endif
            </div>
        </div>

        <div class="sim-card lg:col-span-1">
            <div class="sim-card-header">
                <h3 class="sim-card-title">Ringkasan Data</h3>
            </div>
            <div class="sim-card-body space-y-4">
                <div class="p-3 border border-sim-border-light rounded-lg">
                    <span class="text-xs text-sim-muted">Total Temuan</span>
                    <div class="text-2xl font-bold text-sim-danger">{{ $report->findings_summary['total'] ?? 0 }}</div>
                </div>
                <div class="p-3 border border-sim-border-light rounded-lg">
                    <span class="text-xs text-sim-muted">Total CAPA</span>
                    <div class="text-2xl font-bold text-sim-info">{{ $report->capa_summary['total'] ?? 0 }}</div>
                </div>

                @if (isset($report->findings_summary['by_status']) && is_array($report->findings_summary['by_status']))
                    <div class="mt-3 pt-3 border-t border-sim-border-light">
                        <span class="text-xs text-sim-muted font-semibold uppercase">Temuan per Status</span>
                        <ul class="mt-1 space-y-1 text-sm">
                            @foreach ($report->findings_summary['by_status'] as $status => $count)
                                <li class="flex justify-between">
                                    <span class="text-sim-muted">{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
                                    <span class="font-medium">{{ $count }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (isset($report->capa_summary['by_status']) && is_array($report->capa_summary['by_status']))
                    <div class="mt-3 pt-3 border-t border-sim-border-light">
                        <span class="text-xs text-sim-muted font-semibold uppercase">CAPA per Status</span>
                        <ul class="mt-1 space-y-1 text-sm">
                            @foreach ($report->capa_summary['by_status'] as $status => $count)
                                <li class="flex justify-between">
                                    <span class="text-sim-muted">{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
                                    <span class="font-medium">{{ $count }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
