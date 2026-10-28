@extends('layouts.simmak')

@section('title', 'Tindak Lanjut — SIMMAK PPKP')
@section('page-title', 'Manajemen Tindak Lanjut')

@section('content')
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div class="simmak-card p-5"><div class="text-sm text-slate-500">Total</div><div class="text-3xl font-bold">{{ $stats['total'] }}</div></div>
        <div class="simmak-card p-5"><div class="text-sm text-slate-500">Terbuka / Analisis</div><div class="text-3xl font-bold text-blue-600">{{ $stats['open'] }}</div></div>
        <div class="simmak-card p-5"><div class="text-sm text-slate-500">Tindakan</div><div class="text-3xl font-bold text-amber-600">{{ $stats['action'] }}</div></div>
        <div class="simmak-card p-5"><div class="text-sm text-slate-500">Selesai</div><div class="text-3xl font-bold text-emerald-600">{{ $stats['closed'] }}</div></div>
        <div class="simmak-card p-5"><div class="text-sm text-slate-500">Terlambat</div><div class="text-3xl font-bold text-red-600">{{ $stats['overdue'] }}</div></div>
    </div>

    <div class="simmak-card">
        <form method="GET" class="flex flex-wrap items-center gap-3 border-b border-slate-100 px-5 py-4">
            <select name="status" class="sim-form-select" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                @foreach (['open' => 'Terbuka', 'analysis' => 'Analisis', 'action' => 'Tindakan', 'verification' => 'Verifikasi', 'closed' => 'Selesai'] as $val => $label)
                    <option value="{{ $val }}" @selected(request('status') === $val)>{{ $label }}</option>
                @endforeach
            </select>
            <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="overdue" value="1" class="rounded border-slate-300" @checked(request()->boolean('overdue')) onchange="this.form.submit()">
                Hanya terlambat
            </label>
            <a href="{{ route('capa.export', request()->query()) }}" class="text-sm font-medium text-teal-700 hover:underline" class="sim-btn sim-btn-download">Ekspor CSV</a>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-100 bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3">No. Tindak Lanjut</th>
                        <th class="px-5 py-3">Permasalahan</th>
                        <th class="px-5 py-3">Klinik</th>
                        <th class="px-5 py-3">Prioritas</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Target</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($cases as $case)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-mono text-xs">
                                <a href="{{ route('capa.show', $case) }}" class="font-medium text-teal-700 hover:underline">{{ $case->case_number }}</a>
                            </td>
                            <td class="px-5 py-3 max-w-xs truncate text-slate-800">{{ \Illuminate\Support\Str::limit($case->problem_statement, 60) }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $case->clinic?->name ?? '—' }}</td>
                            <td class="px-5 py-3">
                                @php $pc = match($case->priority) { 'critical' => 'bg-red-100 text-red-800', 'high' => 'bg-orange-100 text-orange-800', default => 'bg-slate-100 text-slate-700' }; @endphp
                                <span class="simmak-badge {{ $pc }}">{{ \App\Support\UiLabel::capaPriority($case->priority) }}</span>
                            </td>
                            <td class="px-5 py-3"><span class="simmak-badge bg-blue-100 text-blue-800">{{ \App\Support\UiLabel::capaStatus($case->status) }}</span></td>
                            <td class="px-5 py-3 text-slate-600 {{ $case->target_close_date->isPast() && $case->status !== 'closed' ? 'font-semibold text-red-600' : '' }}">
                                {{ $case->target_close_date->format('d M Y') }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">Belum ada kasus tindak lanjut.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($cases->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">{{ $cases->links() }}</div>
        @endif
    </div>
@endsection
