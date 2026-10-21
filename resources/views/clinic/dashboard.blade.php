@extends('layouts.simmak')

@section('title', 'Dashboard Klinik — SIMMAK PPKP')
@section('page-title', 'Dashboard Klinik — '.$clinic_name)
@section('page-subtitle', 'Selamat datang, '.$user->name)

@section('content')
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="simmak-card p-5">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-sm font-medium text-slate-500">Audit</div>
                    <div class="mt-2 flex gap-4">
                        <div><span class="text-2xl font-bold text-blue-600">{{ $audit['scheduled'] }}</span><span class="ml-1 text-xs text-slate-500">Terjadwal</span></div>
                        <div><span class="text-2xl font-bold text-emerald-600">{{ $audit['done'] }}</span><span class="ml-1 text-xs text-slate-500">Selesai</span></div>
                    </div>
                </div>
                <div class="rounded-lg bg-blue-50 p-2 text-blue-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
            </div>
            <a href="{{ route('audit.index') }}" class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-teal-700 hover:underline">
                Lihat Semua <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        <div class="simmak-card p-5">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-sm font-medium text-slate-500">CAPA</div>
                    <div class="mt-2 flex gap-4">
                        <div><span class="text-2xl font-bold text-red-600">{{ $capa['open'] }}</span><span class="ml-1 text-xs text-slate-500">Terbuka</span></div>
                        <div><span class="text-2xl font-bold text-amber-500">{{ $capa['in_progress'] }}</span><span class="ml-1 text-xs text-slate-500">Dalam Proses</span></div>
                    </div>
                </div>
                <div class="rounded-lg bg-amber-50 p-2 text-amber-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
            </div>
            <a href="{{ route('capa.index') }}" class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-teal-700 hover:underline">
                Lihat Semua <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        <div class="simmak-card p-5">
            <div class="text-sm font-medium text-slate-700">Ringkasan Risiko</div>
            <div class="mt-3">
                @include('partials.dashboard.risk-heatmap', ['heatmap' => $risk_heatmap, 'compact' => true])
            </div>
            <a href="{{ route('risk.index') }}" class="mt-2 inline-flex items-center gap-1 text-sm font-medium text-teal-700 hover:underline">
                Lihat Semua <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        <div class="simmak-card p-5">
            <div class="flex items-center justify-between">
                <div class="text-sm font-medium text-slate-700">Kesiapan Akreditasi</div>
                @if (!empty($accreditation_score))
                    <span class="text-lg font-bold text-teal-700">{{ $accreditation_score }}%</span>
                @endif
            </div>
            <div class="mt-3 max-h-36 space-y-2 overflow-y-auto">
                @forelse ($accreditation_readiness as $item)
                    <div>
                        <div class="mb-1 flex justify-between gap-2 text-xs">
                            <span class="truncate text-slate-600" title="{{ $item['name'] }}">{{ $item['name'] }}</span>
                            <span class="shrink-0 font-medium text-emerald-700">{{ $item['pct'] }}%</span>
                        </div>
                        <div class="h-1.5 rounded-full bg-slate-100">
                            <div class="h-1.5 rounded-full bg-emerald-500" style="width: {{ $item['pct'] }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Belum ada program akreditasi.</p>
                @endforelse
            </div>
            <a href="{{ route('accreditation.index') }}" class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-teal-700 hover:underline">
                Dashboard Akreditasi <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    </div>

    <div class="mb-6 grid gap-6 xl:grid-cols-4">
        <div class="simmak-card xl:col-span-2">
            <div class="simmak-card-header flex items-center justify-between">
                <h3 class="font-semibold text-slate-800">Tren Indikator Mutu</h3>
                <select class="sim-form-select px-2 py-1 text-xs text-slate-600">
                    <option>6 Bulan Terakhir</option>
                </select>
            </div>
            <div class="simmak-card-body">
                @include('partials.dashboard.quality-line-chart', ['trends' => $quality_trends])
            </div>
        </div>

        <div class="simmak-card">
            <div class="simmak-card-header">
                <h3 class="font-semibold text-slate-800">Dokumen Terbaru</h3>
            </div>
            <div class="simmak-card-body space-y-3">
                @forelse ($documents as $doc)
                    @php
                        $file = strtolower($doc->currentVersion?->file_name ?? $doc->title);
                        $ext = match(true) {
                            str_contains($file, '.pdf') => ['PDF', 'bg-red-50 text-red-700'],
                            str_contains($file, '.xls') => ['XLSX', 'bg-emerald-50 text-emerald-700'],
                            default => ['DOCX', 'bg-blue-50 text-blue-700'],
                        };
                    @endphp
                    <div class="flex items-start gap-3 border-b border-slate-50 pb-3 last:border-0">
                        <div class="rounded px-2 py-1 text-[10px] font-bold uppercase {{ $ext[1] }}">{{ $ext[0] }}</div>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('dms.show', $doc) }}" class="block truncate text-sm font-medium text-slate-800 hover:text-teal-700">{{ $doc->title }}</a>
                            <div class="text-xs text-slate-500">{{ $doc->document_number }} · {{ $doc->updated_at->format('d M Y') }}</div>
                        </div>
                        <span class="simmak-badge shrink-0 {{ $doc->status === 'published' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                            {{ $doc->status === 'published' ? 'Aktif' : 'Tinjauan' }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Belum ada dokumen.</p>
                @endforelse
            </div>
        </div>

        <div class="simmak-card">
            <div class="simmak-card-header">
                <h3 class="font-semibold text-slate-800">Tugas Tim</h3>
            </div>
            <div class="simmak-card-body space-y-4">
                @foreach ($tasks as $task)
                    @php
                        $statusClass = match($task['status']) {
                            'overdue' => 'bg-red-100 text-red-700',
                            'in_progress' => 'bg-amber-100 text-amber-800',
                            default => 'bg-blue-100 text-blue-800',
                        };
                        $statusLabel = \App\Support\UiLabel::taskStatus($task['status']);
                    @endphp
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-teal-700 text-xs font-bold text-white">{{ $task['initials'] }}</div>
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-medium text-slate-800">{{ $task['task'] }}</div>
                            <div class="truncate text-xs text-slate-500">{{ $task['name'] }} · {{ $task['category'] }} · {{ $task['due'] }}</div>
                        </div>
                        <span class="simmak-badge shrink-0 {{ $statusClass }}">{{ $statusLabel }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="simmak-card border-l-4 border-l-teal-600 p-5">
            <h3 class="mb-2 font-semibold text-teal-800">Visi Mutu</h3>
            <p class="text-sm leading-relaxed text-slate-600">{{ $vision }}</p>
        </div>
        <div class="simmak-card border-l-4 border-l-teal-600 p-5">
            <h3 class="mb-2 font-semibold text-teal-800">Misi Mutu</h3>
            <p class="text-sm leading-relaxed text-slate-600">{{ $mission }}</p>
        </div>
    </div>
@endsection
