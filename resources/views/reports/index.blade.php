@extends('layouts.simmak')

@section('title', 'Pelaporan — SIMMAK PPKP')
@section('page-title', 'Pelaporan')

@section('content')
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="simmak-card p-5"><div class="text-sm text-slate-500">Kesiapan Organisasi</div><div class="text-3xl font-bold text-teal-700">{{ $stats['readiness'] }}%</div></div>
        <div class="simmak-card p-5"><div class="text-sm text-slate-500">Temuan Terbuka</div><div class="text-3xl font-bold text-amber-600">{{ $stats['open_findings'] }}</div></div>
        <div class="simmak-card p-5"><div class="text-sm text-slate-500">CAPA Aktif</div><div class="text-3xl font-bold text-blue-600">{{ $stats['capa_open'] }}</div></div>
        <div class="simmak-card p-5"><div class="text-sm text-slate-500">Risiko Aktif</div><div class="text-3xl font-bold text-red-600">{{ $stats['active_risks'] }}</div></div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="simmak-card">
            <div class="simmak-card-header">
                <h3 class="font-semibold text-slate-800">Laporan Tersedia</h3>
            </div>
            <div class="simmak-card-body space-y-3">
                <a href="{{ route('dashboard.export-ranking') }}" class="flex items-center justify-between rounded-lg border border-slate-100 bg-slate-50 p-4 hover:border-teal-200 hover:bg-teal-50">
                    <div>
                        <div class="font-medium text-slate-800">Ranking Klinik — Kesiapan Mutu</div>
                        <div class="text-sm text-slate-500">Ekspor CSV semua klinik PPKP DKI</div>
                    </div>
                    <span class="text-sm font-medium text-teal-700">Download →</span>
                </a>
                <a href="{{ route('dashboard') }}" class="flex items-center justify-between rounded-lg border border-slate-100 bg-slate-50 p-4 hover:border-teal-200 hover:bg-teal-50">
                    <div>
                        <div class="font-medium text-slate-800">Dashboard Eksekutif</div>
                        <div class="text-sm text-slate-500">Ringkasan KPI organisasi</div>
                    </div>
                    <span class="text-sm font-medium text-teal-700">Buka →</span>
                </a>
                <a href="{{ route('accreditation.index') }}" class="flex items-center justify-between rounded-lg border border-slate-100 bg-slate-50 p-4 hover:border-teal-200 hover:bg-teal-50">
                    <div>
                        <div class="font-medium text-slate-800">Laporan Kesiapan Akreditasi</div>
                        <div class="text-sm text-slate-500">Self Assessment, kesenjangan, dan bukti</div>
                    </div>
                    <span class="text-sm font-medium text-teal-700">Buka →</span>
                </a>
            </div>
        </div>

        <div class="simmak-card">
            <div class="simmak-card-header">
                <h3 class="font-semibold text-slate-800">Top 5 Klinik — Kesiapan</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3">#</th>
                            <th class="px-5 py-3">Klinik</th>
                            <th class="px-5 py-3">Skor</th>
                            <th class="px-5 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($ranking_preview as $clinic)
                            <tr>
                                <td class="px-5 py-3">{{ $clinic['rank'] }}</td>
                                <td class="px-5 py-3 font-medium">{{ $clinic['name'] }}</td>
                                <td class="px-5 py-3 font-semibold text-teal-700">{{ $clinic['readiness'] }}%</td>
                                <td class="px-5 py-3"><span class="simmak-badge {{ $clinic['status_color'] }}">{{ $clinic['status_label'] }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
