@extends('layouts.simmak')

@section('title', 'Laporan Audit — SIMMAK PPKP')
@section('page-title', 'Laporan Audit Internal')

@section('content')
    <div class="mb-4">
        <a href="{{ route('audit.index') }}" class="text-sm font-medium text-teal-700 hover:underline">← Kembali ke Audit Internal</a>
    </div>

    <div class="simmak-card">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-100 bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3">No. Laporan</th>
                        <th class="px-5 py-3">Jadwal Audit</th>
                        <th class="px-5 py-3">Klinik</th>
                        <th class="px-5 py-3">Tanggal</th>
                        <th class="px-5 py-3">Temuan</th>
                        <th class="px-5 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($reports as $report)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-mono text-xs font-medium text-teal-700">{{ $report->report_number }}</td>
                            <td class="px-5 py-3">
                                <div class="font-medium text-slate-800">{{ $report->schedule?->title }}</div>
                                <div class="text-xs text-slate-500">{{ $report->schedule?->schedule_code }}</div>
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ $report->schedule?->clinic?->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $report->report_date->format('d M Y') }}</td>
                            <td class="px-5 py-3 text-center font-semibold">{{ $report->findings_count }}</td>
                            <td class="px-5 py-3"><span class="simmak-badge bg-blue-100 text-blue-800">{{ ucfirst($report->status) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">Belum ada laporan audit.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($reports->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">{{ $reports->links() }}</div>
        @endif
    </div>

    @if ($reports->isNotEmpty())
        <div class="mt-6 space-y-4">
            @foreach ($reports as $report)
                <div class="simmak-card p-5">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <h3 class="font-semibold text-slate-800">{{ $report->report_number }} — {{ $report->schedule?->title }}</h3>
                        <span class="text-xs text-slate-500">Disusun: {{ $report->preparer?->name ?? '—' }}</span>
                    </div>
                    <p class="mt-2 text-sm text-slate-600">{{ $report->executive_summary }}</p>
                </div>
            @endforeach
        </div>
    @endif
@endsection
