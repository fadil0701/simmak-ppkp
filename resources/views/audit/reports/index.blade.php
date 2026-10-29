@extends('layouts.simmak')

@section('title', 'Laporan Audit — SIMMAK PPKP')
@section('page-title', 'Laporan Audit')

@section('content')
    <div class="sim-page-header">
        <div>
            <h2 class="sim-page-title">Laporan Audit</h2>
            <p class="sim-page-subtitle">Kelola laporan audit dalam format PDF dan Excel. Semua laporan dihasilkan dari data aktual database.</p>
        </div>

        @can('audit.report.manage')
            <a href="{{ route('audit.reports.create') }}" class="sim-btn sim-btn-create inline-flex items-center gap-2">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Buat Laporan
            </a>
        @endcan
    </div>

    <div class="sim-card">
        <div class="sim-card-header">
            <h3 class="sim-card-title">Daftar Laporan</h3>
        </div>
        <div class="sim-card-body overflow-x-auto">
            <table class="sim-table w-full text-left text-sm">
                <thead>
                    <tr>
                        <th>Nomor Laporan</th>
                        <th>Judul</th>
                        <th>Konteks</th>
                        <th>Tipe</th>
                        <th>Periode</th>
                        <th class="text-center">Klinik</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Dibuat Oleh</th>
                        <th class="text-center">Tanggal</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reports as $report)
                        <tr>
                            <td class="font-mono text-xs text-sim-muted">{{ $report->report_number }}</td>
                            <td>
                                <div class="font-medium text-sim-ink">{{ $report->title }}</div>
                            </td>
                            <td class="text-sim-muted text-xs">
                                @if ($report->action?->capaCase)
                                    <a href="{{ route('capa.show', $report->action->capaCase) }}" class="font-medium text-teal-700 hover:underline">
                                        {{ $report->action->capaCase->case_number }}
                                    </a>
                                    <div class="text-[11px] text-slate-500">Laporan tindak lanjut</div>
                                @elseif ($report->finding)
                                    <a href="{{ route('findings.show', $report->finding) }}" class="font-medium text-teal-700 hover:underline">
                                        {{ $report->finding->finding_code }}
                                    </a>
                                    <div class="text-[11px] text-slate-500">Laporan temuan</div>
                                @else
                                    <span class="text-slate-500">Laporan audit{{ $report->execution ? ' ('.$report->execution->execution_code.')' : '' }}</span>
                                @endif
                            </td>
                            <td class="text-sim-muted">{{ ucfirst(str_replace('_', ' ', $report->report_type)) }}</td>
                            <td class="text-sim-muted">
                                {{ $report->period_start?->format('d M Y') ?? '-' }} – {{ $report->period_end?->format('d M Y') ?? '-' }}
                            </td>
                            <td class="text-center text-sim-muted">{{ $report->clinic?->name ?? '—' }}</td>
                            <td class="text-center">
                                <span class="sim-badge sim-badge-status {{ $report->status === 'published' ? 'border-sim-success bg-sim-success-bg text-sim-success' : 'border-sim-neutral bg-sim-neutral-bg text-sim-neutral' }}">
                                    {{ ucfirst($report->status) }}
                                </span>
                            </td>
                            <td class="text-center text-sim-muted">{{ $report->generator?->name ?? '—' }}</td>
                            <td class="text-center text-sim-muted">{{ $report->generated_at?->format('d M Y H:i') ?? '—' }}</td>
                            <td class="text-center">
                                <div class="sim-action-group">
                                    <x-action action="detail" href="{{ route('audit.reports.show', $report) }}" icon="file-text" label="Detail" size="sm" />
                                    @if ($report->isFollowUpReport())
                                        @can('view', $report)
                                            <x-action action="download" href="{{ route('capa.download-report', $report) }}" icon="download" label="Berkas" size="sm" />
                                        @endcan
                                    @else
                                        <x-action action="download" href="{{ route('audit.reports.download-pdf', $report) }}" icon="download" label="PDF" size="sm" />
                                        <x-action action="download" href="{{ route('audit.reports.download-excel', $report) }}" icon="download" label="Excel" size="sm" />
                                    @endif
                                    @can('delete', $report)
                                        <x-action action="delete" href="{{ route('audit.reports.destroy', $report) }}" icon="trash" label="Hapus" size="sm" method="DELETE" confirm="Hapus laporan ini?" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="sim-table-empty">Belum ada laporan audit.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($reports->hasPages())
            <div class="sim-card-footer">{{ $reports->links() }}</div>
        @endif
    </div>
@endsection
