@extends('layouts.simmak')

@section('title', 'Temuan Audit — SIMMAK PPKP')
@section('page-title', 'Temuan Audit Internal')

@section('content')
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('audit.index') }}" class="text-sm font-medium text-teal-700 hover:underline">← Program & Jadwal Audit</a>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div class="simmak-card p-5"><div class="text-sm text-slate-500">Total</div><div class="text-3xl font-bold">{{ $stats['total'] }}</div></div>
        <div class="simmak-card p-5"><div class="text-sm text-slate-500">Terbuka</div><div class="text-3xl font-bold text-red-600">{{ $stats['open'] }}</div></div>
        <div class="simmak-card p-5"><div class="text-sm text-slate-500">Dalam CAPA</div><div class="text-3xl font-bold text-amber-600">{{ $stats['in_capa'] }}</div></div>
        <div class="simmak-card p-5"><div class="text-sm text-slate-500">Ditutup</div><div class="text-3xl font-bold text-emerald-600">{{ $stats['closed'] }}</div></div>
        <div class="simmak-card p-5"><div class="text-sm text-slate-500">Kritis Aktif</div><div class="text-3xl font-bold text-red-700">{{ $stats['critical'] }}</div></div>
    </div>

    <div class="simmak-card">
        <form method="GET" class="flex flex-wrap gap-3 border-b border-slate-100 px-5 py-4">
            <select name="status" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                <option value="">Semua Status</option>
                @foreach (['open' => 'Terbuka', 'in_capa' => 'Dalam CAPA', 'closed' => 'Ditutup', 'verified' => 'Terverifikasi'] as $val => $label)
                    <option value="{{ $val }}" @selected(request('status') === $val)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="severity" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                <option value="">Semua Tingkat</option>
                @foreach (['observation' => 'Observasi', 'minor' => 'Ringan', 'major' => 'Mayor', 'critical' => 'Kritikal'] as $val => $label)
                    <option value="{{ $val }}" @selected(request('severity') === $val)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white">Filter</button>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-100 bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Kode</th>
                        <th class="px-5 py-3">Judul</th>
                        <th class="px-5 py-3">Klinik</th>
                        <th class="px-5 py-3">Tingkat</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($findings as $finding)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-mono text-xs">
                                <a href="{{ route('findings.show', $finding) }}" class="text-teal-700 hover:underline">{{ $finding->finding_code }}</a>
                            </td>
                            <td class="px-5 py-3 font-medium text-slate-800">{{ $finding->title }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $finding->clinic?->name ?? '—' }}</td>
                            <td class="px-5 py-3">
                                @php $sev = match($finding->severity) { 'critical' => 'bg-red-100 text-red-800', 'major' => 'bg-orange-100 text-orange-800', default => 'bg-yellow-100 text-yellow-800' }; @endphp
                                <span class="simmak-badge {{ $sev }}">{{ \App\Support\UiLabel::findingSeverity($finding->severity) }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="simmak-badge bg-slate-100 text-slate-700">{{ \App\Support\UiLabel::findingStatus($finding->status) }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <a href="{{ route('findings.show', $finding) }}" class="text-sm font-medium text-teal-700 hover:underline">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">Belum ada temuan audit.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($findings->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">{{ $findings->links() }}</div>
        @endif
    </div>
@endsection
