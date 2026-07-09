@extends('layouts.simmak')

@section('title', 'Manajemen Risiko — SIMMAK PPKP')
@section('page-title', 'Manajemen Risiko')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-600">Register risiko organisasi, peta panas risiko inheren, dan daftar item risiko.</p>
        <a href="{{ route('risk.create') }}" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800">+ Catat Risiko</a>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
        <div class="simmak-card p-5"><div class="text-sm text-slate-500">Total Risiko</div><div class="text-3xl font-bold">{{ $stats['total'] }}</div></div>
        <div class="simmak-card p-5"><div class="text-sm text-slate-500">Sangat Tinggi</div><div class="text-3xl font-bold text-red-700">{{ $stats['extreme'] }}</div></div>
        <div class="simmak-card p-5"><div class="text-sm text-slate-500">Tinggi</div><div class="text-3xl font-bold text-red-600">{{ $stats['high'] }}</div></div>
        <div class="simmak-card p-5"><div class="text-sm text-slate-500">Sedang</div><div class="text-3xl font-bold text-amber-600">{{ $stats['medium'] }}</div></div>
        <div class="simmak-card p-5"><div class="text-sm text-slate-500">Rendah</div><div class="text-3xl font-bold text-emerald-600">{{ $stats['low'] }}</div></div>
        <div class="simmak-card p-5"><div class="text-sm text-slate-500">Register Aktif</div><div class="text-3xl font-bold text-teal-700">{{ $stats['active_registers'] }}</div></div>
    </div>

    <div class="mb-6 grid gap-6 lg:grid-cols-2">
        <div class="simmak-card">
            <div class="simmak-card-header">
                <h3 class="font-semibold text-slate-800">Peta Panas Risiko (Inheren)</h3>
                <p class="mt-1 text-xs text-slate-500">Likelihood (↓) × Impact (→)</p>
            </div>
            <div class="simmak-card-body">
                <div class="flex gap-2">
                    <div class="flex flex-col justify-between py-1 text-[10px] text-slate-500">
                        @foreach (['5', '4', '3', '2', '1'] as $l)
                            <span class="h-10 flex items-center">{{ $l }}</span>
                        @endforeach
                    </div>
                    <div class="flex-1">
                        <div class="grid grid-cols-5 gap-1">
                            @for ($l = 5; $l >= 1; $l--)
                                @for ($i = 1; $i <= 5; $i++)
                                    @php
                                        $count = $heatmap[$l][$i] ?? 0;
                                        $color = app(\App\Services\RiskService::class)->heatmapColor($l, $i);
                                    @endphp
                                    <div class="{{ $color }} flex h-10 items-center justify-center rounded text-xs font-semibold {{ $count > 0 ? 'text-white' : 'text-white/70' }}" title="L{{ $l }} × I{{ $i }}">
                                        {{ $count > 0 ? $count : '' }}
                                    </div>
                                @endfor
                            @endfor
                        </div>
                        <div class="mt-2 grid grid-cols-5 text-center text-[10px] text-slate-500">
                            @foreach (range(1, 5) as $i)
                                <span>{{ $i }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="simmak-card">
            <div class="simmak-card-header">
                <h3 class="font-semibold text-slate-800">Register Risiko</h3>
            </div>
            <div class="simmak-card-body space-y-3">
                @forelse ($registers as $register)
                    <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="font-mono text-xs text-slate-500">{{ $register->register_code }}</div>
                                <div class="font-semibold text-slate-800">{{ $register->name }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $register->clinic?->name ?? 'Organisasi' }} · {{ $register->owner?->name }}</div>
                            </div>
                            <span class="simmak-badge bg-teal-100 text-teal-800">{{ ucfirst($register->status) }}</span>
                        </div>
                        <div class="mt-2 text-xs text-slate-500">{{ $register->items_count }} item risiko</div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Belum ada register risiko.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="simmak-card">
        <form method="GET" class="flex flex-wrap gap-3 border-b border-slate-100 px-5 py-4">
            <select name="level" class="rounded-lg border border-slate-200 px-3 py-2 text-sm" onchange="this.form.submit()">
                <option value="">Semua Level</option>
                @foreach (['extreme' => 'Sangat Tinggi', 'high' => 'Tinggi', 'medium' => 'Sedang', 'low' => 'Rendah'] as $val => $label)
                    <option value="{{ $val }}" @selected(request('level') === $val)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="status" class="rounded-lg border border-slate-200 px-3 py-2 text-sm" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                @foreach (['identified', 'assessed', 'mitigated', 'monitored', 'closed'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-100 bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Kode</th>
                        <th class="px-5 py-3">Judul / Kategori</th>
                        <th class="px-5 py-3">Klinik</th>
                        <th class="px-5 py-3">Inheren</th>
                        <th class="px-5 py-3">Residual</th>
                        <th class="px-5 py-3">Level</th>
                        <th class="px-5 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($items as $item)
                        @php $riskService = app(\App\Services\RiskService::class); @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-mono text-xs">
                                <a href="{{ route('risk.show', $item) }}" class="font-medium text-teal-700 hover:underline">{{ $item->risk_code }}</a>
                            </td>
                            <td class="px-5 py-3">
                                <div class="font-medium text-slate-800">{{ \Illuminate\Support\Str::limit($item->title, 50) }}</div>
                                <div class="text-xs text-slate-500">{{ $item->category?->name }}</div>
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ $item->clinic?->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-center">
                                <span class="font-semibold">{{ $item->inherent_score }}</span>
                                <span class="text-xs text-slate-500">({{ $item->inherent_likelihood }}×{{ $item->inherent_impact }})</span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="font-semibold">{{ $item->residual_score }}</span>
                                <span class="text-xs text-slate-500">({{ $item->residual_likelihood }}×{{ $item->residual_impact }})</span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="simmak-badge {{ $riskService->riskLevelColor($item->risk_level) }}">
                                    {{ $riskService->riskLevelLabel($item->risk_level) }}
                                </span>
                            </td>
                            <td class="px-5 py-3"><span class="simmak-badge bg-blue-100 text-blue-800">{{ \App\Support\UiLabel::riskStatus($item->status) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-8 text-center text-slate-500">Belum ada item risiko.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($items->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">{{ $items->links() }}</div>
        @endif
    </div>
@endsection
