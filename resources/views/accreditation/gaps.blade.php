@extends('layouts.simmak')

@section('title', 'Analisis Kesenjangan — SIMMAK PPKP')
@section('page-title', 'Analisis Kesenjangan Akreditasi')

@section('content')
    <div class="mb-4">
        <a href="{{ route('accreditation.index') }}" class="text-sm font-medium text-teal-700 hover:underline">← Kembali ke Dashboard Akreditasi</a>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="simmak-card p-5"><div class="text-sm text-slate-500">Total Kesenjangan</div><div class="text-3xl font-bold">{{ $stats['total'] }}</div></div>
        <div class="simmak-card p-5"><div class="text-sm text-slate-500">Kritikal</div><div class="text-3xl font-bold text-red-700">{{ $stats['critical'] }}</div></div>
        <div class="simmak-card p-5"><div class="text-sm text-slate-500">Mayor</div><div class="text-3xl font-bold text-orange-600">{{ $stats['major'] }}</div></div>
        <div class="simmak-card p-5"><div class="text-sm text-slate-500">Ringan</div><div class="text-3xl font-bold text-emerald-600">{{ $stats['minor'] }}</div></div>
    </div>

    <div class="simmak-card">
        <form method="GET" class="border-b border-slate-100 px-5 py-4">
            <select name="severity" class="rounded-lg border border-slate-200 px-3 py-2 text-sm" onchange="this.form.submit()">
                <option value="">Semua Tingkat</option>
                @foreach (['critical' => 'Kritikal', 'major' => 'Mayor', 'minor' => 'Ringan'] as $val => $label)
                    <option value="{{ $val }}" @selected(request('severity') === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-100 bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Standar</th>
                        <th class="px-5 py-3">Deskripsi Kesenjangan</th>
                        <th class="px-5 py-3">Tingkat</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Target</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($gaps as $gap)
                        @php
                            $sevClass = match($gap->severity) {
                                'critical' => 'bg-red-100 text-red-800',
                                'major' => 'bg-orange-100 text-orange-800',
                                default => 'bg-emerald-100 text-emerald-800',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-mono text-xs">{{ $gap->standard?->standard_code ?? '—' }}</td>
                            <td class="px-5 py-3 max-w-md text-slate-800">{{ $gap->gap_description }}</td>
                            <td class="px-5 py-3"><span class="simmak-badge {{ $sevClass }}">{{ \App\Support\UiLabel::gapSeverity($gap->severity) }}</span></td>
                            <td class="px-5 py-3"><span class="simmak-badge bg-blue-100 text-blue-800">{{ \App\Support\UiLabel::gapStatus($gap->status) }}</span></td>
                            <td class="px-5 py-3 text-slate-600">{{ $gap->target_date?->format('d M Y') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-slate-500">Belum ada kesenjangan teridentifikasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($gaps->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">{{ $gaps->links() }}</div>
        @endif
    </div>
@endsection
