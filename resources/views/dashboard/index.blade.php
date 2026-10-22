@extends('layouts.simmak')

@section('title', $showExecutive ? 'Dashboard Eksekutif — SIMMAK PPKP' : 'Dashboard Audit Internal — SIMMAK PPKP')
@section('page-title', $showExecutive ? 'Dashboard Eksekutif' : 'Dashboard Audit Internal')
@section('page-subtitle', $showExecutive
    ? 'Ringkasan kesiapan akreditasi, temuan, CAPA, dan risiko seluruh klinik'
    : 'Antrian kerja audit internal dan status audit seluruh klinik')

@section('content')
    @if ($canViewClinicDashboard)
        <div class="mb-4 rounded-lg border border-sim-border bg-sim-surface-alt px-4 py-3 text-sm text-sim-ink">
            Pegawai ini juga memiliki peran pemantauan klinik.
            <a href="{{ route('clinic.dashboard') }}" class="font-medium text-sim-primary hover:underline">Buka Dashboard Klinik</a>
            untuk melihat kondisi klinik aktif.
        </div>
    @endif

    @if ($showExecutive)
    <div class="flex flex-col gap-4">
        {{-- Baris KPI --}}
        <div class="grid shrink-0 items-stretch gap-4 xl:grid-cols-4">
            {{-- Kesiapan Akreditasi --}}
            <div class="sim-card flex flex-col overflow-hidden">
                <div class="sim-card-header border-b-0 pb-0">
                    <h3 class="text-xs font-bold uppercase tracking-wide text-sim-navy-900">Kesiapan Akreditasi</h3>
                </div>
                <div class="sim-card-body flex flex-1 flex-col">
                    @include('partials.dashboard.gauge-readiness', [
                        'value' => $stats['readiness'],
                        'target' => $stats['accreditation_target'],
                    ])
                </div>
            </div>

            {{-- Temuan Terbuka --}}
            <div class="sim-card flex flex-col">
                <div class="sim-card-header border-b-0 pb-0">
                    <h3 class="text-xs font-bold uppercase tracking-wide text-sim-navy-900">Temuan Terbuka</h3>
                </div>
                <div class="sim-card-body flex flex-1 flex-col">
                    <div class="flex-1">
                        <div class="flex items-center gap-4">
                            <div class="rounded-2xl bg-blue-100 p-3.5 text-blue-600">
                                <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 4h.01M9 16h.01"/>
                                </svg>
                            </div>
                            <div class="text-5xl font-bold leading-none text-sim-ink">{{ $stats['open_findings'] }}</div>
                        </div>
                        <p class="mt-3 text-sm text-sim-muted">Total Temuan Terbuka</p>
                    </div>
                    <div class="sim-card-footer">
                        <span class="font-semibold text-sim-danger">Prioritas Tinggi: {{ $stats['high_priority_findings'] }}</span>
                    </div>
                </div>
            </div>

            {{-- Progres CAPA --}}
            <div class="sim-card flex flex-col">
                <div class="sim-card-header border-b-0 pb-0">
                    <h3 class="text-xs font-bold uppercase tracking-wide text-sim-navy-900">Progres CAPA</h3>
                    <p class="mt-0.5 text-[10px] text-sim-muted">Tindakan Korektif &amp; Preventif</p>
                </div>
                <div class="sim-card-body flex flex-1 flex-col">
                    @include('partials.dashboard.capa-bar-chart', [
                        'items' => $capa_breakdown,
                        'total' => $stats['capa_total'],
                    ])
                </div>
            </div>

            {{-- Peta Panas Risiko --}}
            <div class="sim-card flex flex-col">
                <div class="sim-card-header border-b-0 pb-0">
                    <h3 class="text-xs font-bold uppercase tracking-wide text-sim-navy-900">Peta Panas Risiko</h3>
                </div>
                <div class="sim-card-body flex flex-1 flex-col">
                    @include('partials.dashboard.risk-heatmap', [
                        'heatmap' => $heatmap,
                        'total' => $stats['active_risks'],
                    ])
                </div>
            </div>
        </div>

        {{-- Baris bawah: sidebar kiri + tabel peringkat --}}
        <div class="grid min-h-0 flex-1 gap-4 xl:grid-cols-12">
            <div class="flex flex-col gap-4 xl:col-span-4">
                <div class="sim-card border-sim-danger/30">
                    <div class="sim-card-body">
                        <div class="flex items-start gap-4">
                            <div class="rounded-xl bg-sim-danger p-3 text-white shadow-sim-sm">
                                <svg class="h-9 w-9" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <div>
                                <div class="text-4xl font-bold text-sim-danger">{{ $stats['expired_documents'] }}</div>
                                <p class="text-sm font-medium text-sim-ink">Dokumen Kedaluwarsa</p>
                            </div>
                        </div>
                        <div class="mt-4 flex items-start gap-2 rounded-lg border border-sim-danger/20 bg-sim-danger-bg px-3 py-2.5 text-xs text-sim-danger">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-sim-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                            <span>Segera lakukan tinjauan dan perbarui dokumen.</span>
                        </div>
                        <a href="{{ route('dms.index') }}" class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-sim-primary hover:underline">
                            Lihat Semua
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>

                <div class="sim-card flex min-h-0 flex-1 flex-col">
                    <div class="sim-card-header border-b-0 pb-2">
                        <h3 class="text-sm font-bold text-sim-ink">Aktivitas Terbaru</h3>
                    </div>
                    <div class="sim-card-body flex flex-1 flex-col pt-0">
                        @include('partials.dashboard.activity-list', ['activities' => $activities])
                        <a href="#" class="mt-auto inline-flex items-center gap-1 pt-4 text-sm font-medium text-sim-primary hover:underline">
                            Lihat Semua Aktivitas
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>
            </div>

            <div class="sim-card flex min-h-0 flex-col xl:col-span-8">
                <div class="sim-card-header flex flex-col gap-3 border-b-0 pb-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-sm font-bold uppercase tracking-wide text-sim-ink">Peringkat Klinik</h3>
                        <p class="text-xs text-sim-muted">Kesiapan Mutu per Klinik</p>
                    </div>
                    <form method="GET" action="{{ route('dashboard') }}" class="flex gap-2">
                        <select name="region" onchange="this.form.submit()" class="rounded-lg border border-sim-border bg-sim-white px-3 py-1.5 text-sm text-sim-ink">
                            <option value="">Semua Wilayah</option>
                            @foreach ($regions as $region)
                                <option value="{{ $region }}" @selected($selected_region === $region)>{{ $region }}</option>
                            @endforeach
                        </select>
                        <a href="{{ route('dashboard.export-ranking') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-sim-border bg-sim-white px-3 py-1.5 text-sm font-medium text-sim-ink hover:bg-sim-hover">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Ekspor
                        </a>
                    </form>
                </div>
                <div class="min-h-0 flex-1 overflow-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="sticky top-0 z-10 border-b border-sim-border-light bg-sim-surface-alt text-xs uppercase text-sim-muted">
                            <tr>
                                <th class="px-4 py-2.5">#</th>
                                <th class="px-4 py-2.5">Klinik</th>
                                <th class="px-4 py-2.5">Wilayah</th>
                                <th class="px-4 py-2.5">Skor Akreditasi (%)</th>
                                <th class="px-4 py-2.5">Temuan Terbuka</th>
                                <th class="px-4 py-2.5">CAPA Selesai (%)</th>
                                <th class="px-4 py-2.5">Skor Risiko</th>
                                <th class="px-4 py-2.5">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-sim-border-light">
                            @forelse ($clinic_ranking->take(15) as $clinic)
                                <tr class="hover:bg-sim-hover">
                                    <td class="px-4 py-2.5 font-medium text-sim-muted">{{ $clinic['rank'] }}</td>
                                    <td class="px-4 py-2.5 font-medium text-sim-ink">{{ $clinic['name'] }}</td>
                                    <td class="px-4 py-2.5 text-sim-muted">{{ $clinic['region'] }}</td>
                                    <td class="px-4 py-2.5 font-semibold text-sim-primary">{{ $clinic['readiness'] }}%</td>
                                    <td class="px-4 py-2.5">{{ $clinic['open_findings'] }}</td>
                                    <td class="px-4 py-2.5">{{ $clinic['capa_done'] }}%</td>
                                    <td class="px-4 py-2.5">{{ $clinic['risk_score'] }}</td>
                                    <td class="px-4 py-2.5">
                                        <span class="sim-badge border {{ $clinic['status_color'] }}">{{ $clinic['status_label'] }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-sm text-sim-muted">Tidak ada data klinik untuk wilayah ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="flex shrink-0 items-center justify-between border-t border-sim-border-light px-4 py-3 text-xs text-sim-muted">
                    <span>Menampilkan 1 – {{ min(15, $clinic_ranking->count()) }} dari {{ $clinic_ranking->count() }} klinik</span>
                    @if ($clinic_ranking->count() > 15)
                        <span>Gunakan filter wilayah untuk mempersempit daftar klinik.</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    @include('partials.dashboard.audit-section')
@endsection
