@extends('layouts.simmak')

@section('title', 'Checklist Audit — SIMMAK PPKP')
@section('page-title', 'Checklist Audit')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('audit.index') }}" class="text-sm font-medium text-teal-700 hover:underline">← Kembali ke Audit Internal</a>
        <a href="{{ route('audit.reports') }}" class="text-sm font-medium text-teal-700 hover:underline">Lihat Laporan Audit →</a>
    </div>

    <div class="mb-6 simmak-card p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="font-mono text-xs text-slate-500">{{ $schedule->schedule_code }}</p>
                <h2 class="text-lg font-bold text-slate-800">{{ $schedule->title }}</h2>
                <p class="mt-1 text-sm text-slate-600">{{ $schedule->clinic?->name ?? '—' }} · {{ $schedule->program?->name }}</p>
            </div>
            @php $sc = match($schedule->status) { 'completed' => 'bg-emerald-100 text-emerald-800', 'ongoing' => 'bg-amber-100 text-amber-800', default => 'bg-blue-100 text-blue-800' }; @endphp
            <span class="simmak-badge {{ $sc }}">{{ ucfirst($schedule->status) }}</span>
        </div>
        <div class="mt-4 grid gap-4 text-sm sm:grid-cols-3">
            <div><span class="text-slate-500">Periode</span><p class="font-medium">{{ $schedule->scheduled_start->format('d M') }} – {{ $schedule->scheduled_end->format('d M Y') }}</p></div>
            <div><span class="text-slate-500">Lead Auditor</span><p class="font-medium">{{ $schedule->leadAuditor?->name ?? '—' }}</p></div>
            <div><span class="text-slate-500">Lokasi</span><p class="font-medium">{{ $schedule->location ?? '—' }}</p></div>
        </div>
    </div>

    @if ($checklist)
        <div class="simmak-card">
            <div class="simmak-card-header flex items-center justify-between">
                <h3 class="font-semibold text-slate-800">{{ $checklist->title }}</h3>
                <span class="simmak-badge bg-blue-100 text-blue-800">{{ ucfirst(str_replace('_', ' ', $checklist->status)) }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3 w-16">Status</th>
                            <th class="px-5 py-3">Kode</th>
                            <th class="px-5 py-3">Item Pemeriksaan</th>
                            <th class="px-5 py-3">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($checklist->items as $item)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-3 text-center">
                                    @if ($item->is_checked)
                                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">✓</span>
                                    @else
                                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 font-mono text-xs">{{ $item->item_code }}</td>
                                <td class="px-5 py-3 text-slate-800">{{ $item->item_text }}</td>
                                <td class="px-5 py-3 text-slate-500">{{ $item->notes ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="simmak-card p-8 text-center text-slate-500">Belum ada checklist untuk jadwal audit ini.</div>
    @endif
@endsection
