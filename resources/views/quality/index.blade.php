@extends('layouts.simmak')

@section('title', 'Program Mutu — SIMMAK PPKP')
@section('page-title', 'Program & Indikator Mutu')

@section('content')
    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="simmak-card p-5"><div class="text-sm text-slate-500">Program Aktif</div><div class="text-3xl font-bold">{{ $stats['programs'] }}</div></div>
        <div class="simmak-card p-5"><div class="text-sm text-slate-500">Indikator</div><div class="text-3xl font-bold text-teal-700">{{ $stats['indicators'] }}</div></div>
        <div class="simmak-card p-5"><div class="text-sm text-slate-500">Pengukuran</div><div class="text-3xl font-bold text-blue-600">{{ $stats['measurements'] }}</div></div>
    </div>

    @forelse ($programs as $program)
        <div class="mb-6 simmak-card">
            <div class="simmak-card-header">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="font-mono text-xs text-slate-500">{{ $program->program_code }}</div>
                        <h3 class="text-lg font-bold text-slate-800">{{ $program->name }}</h3>
                        <p class="mt-1 text-sm text-slate-600">{{ $program->objective }}</p>
                    </div>
                    <span class="simmak-badge bg-teal-100 text-teal-800">{{ ucfirst($program->status) }}</span>
                </div>
                <div class="mt-2 flex flex-wrap gap-4 text-xs text-slate-500">
                    <span>Klinik: {{ $program->clinic?->name ?? 'Organisasi' }}</span>
                    <span>Tahun: {{ $program->fiscalYear?->name ?? '—' }}</span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="sim-table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Indikator</th>
                            <th>Target</th>
                            <th>Terakhir</th>
                            <th>Pengukuran Terbaru</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($program->indicators as $indicator)
                            @php $latest = $indicator->measurements->first(); @endphp
                            <tr>
                                <td class="font-mono text-xs">{{ $indicator->indicator_code }}</td>
                                <td>
                                    <div class="font-medium text-slate-800">{{ $indicator->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $indicator->unit }} · {{ $indicator->direction === 'higher_better' ? 'Semakin tinggi baik' : 'Semakin rendah baik' }}</div>
                                </td>
                                <td class="font-semibold">{{ number_format($indicator->target_value, 2) }} {{ $indicator->unit }}</td>
                                <td>
                                    @if ($latest)
                                        <span class="font-semibold {{ $indicator->direction === 'higher_better' ? ($latest->value >= $indicator->target_value ? 'text-emerald-600' : 'text-amber-600') : ($latest->value <= $indicator->target_value ? 'text-emerald-600' : 'text-amber-600') }}">
                                            {{ number_format($latest->value, 2) }}
                                        </span>
                                        <span class="text-xs text-slate-500">{{ $indicator->unit }}</span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="text-xs text-slate-500">
                                    @if ($indicator->measurements->isNotEmpty())
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ($indicator->measurements->take(6)->reverse() as $m)
                                                <span class="rounded bg-slate-100 px-1.5 py-0.5" title="{{ $m->measurement_date->format('d M Y') }}">{{ number_format($m->value, 1) }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        Belum ada data
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="sim-table-empty">Belum ada indikator mutu.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="simmak-card p-8 text-center text-slate-500">Belum ada program mutu. Jalankan <code class="rounded bg-slate-100 px-2 py-0.5">ExtendedModuleSeeder</code>.</div>
    @endforelse
@endsection
