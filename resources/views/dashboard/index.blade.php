@extends('layouts.app')

@section('title', 'Dashboard — SIMMAK PPKP')
@section('page-title', 'Executive Dashboard')

@section('content')
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-sm font-medium text-slate-500">Kesiapan Akreditasi</div>
            <div class="mt-2 text-3xl font-bold text-teal-700">{{ $stats['readiness'] }}%</div>
            <div class="mt-2 h-2 rounded-full bg-slate-100">
                <div class="h-2 rounded-full bg-teal-500" style="width: {{ $stats['readiness'] }}%"></div>
            </div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-sm font-medium text-slate-500">Temuan Terbuka</div>
            <div class="mt-2 text-3xl font-bold text-amber-600">{{ $stats['open_findings'] }}</div>
            <p class="mt-1 text-xs text-slate-400">Audit internal lintas klinik</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-sm font-medium text-slate-500">CAPA Berjalan</div>
            <div class="mt-2 text-3xl font-bold text-blue-600">{{ $stats['capa_open'] }}</div>
            <div class="mt-2 text-xs text-slate-500">Progress rata-rata {{ $stats['capa_progress'] }}%</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-sm font-medium text-slate-500">Dokumen Kedaluwarsa</div>
            <div class="mt-2 text-3xl font-bold text-red-600">{{ $stats['expired_documents'] }}</div>
            <p class="mt-1 text-xs text-slate-400">Perlu review & perpanjangan</p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <h2 class="mb-4 text-base font-semibold text-slate-800">Modul SIMMAK PPKP</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ([
                    ['DMS', 'Manajemen dokumen mutu, SOP, kebijakan', 'text-teal-700'],
                    ['Audit Internal', 'Program, jadwal, temuan, laporan', 'text-blue-700'],
                    ['CAPA', 'Tindakan korektif & preventif', 'text-indigo-700'],
                    ['Risiko', 'Register, assessment, heatmap', 'text-amber-700'],
                    ['Akreditasi', 'FKTP/FKTL, self assessment, gap', 'text-emerald-700'],
                    ['Mutu', 'Program mutu & indikator kinerja', 'text-cyan-700'],
                ] as [$title, $desc, $colorClass])
                    <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                        <div class="font-semibold {{ $colorClass }}">{{ $title }}</div>
                        <p class="mt-1 text-sm text-slate-600">{{ $desc }}</p>
                        <span class="mt-2 inline-block rounded-full bg-slate-200 px-2 py-0.5 text-xs text-slate-600">Fase 2</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-base font-semibold text-slate-800">Status Implementasi</h2>
            <ul class="space-y-3 text-sm">
                <li class="flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-green-500"></span>
                    Fase 1: Core Platform & Auth
                </li>
                <li class="flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-amber-400"></span>
                    Fase 2: DMS & Workflow
                </li>
                <li class="flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-slate-300"></span>
                    Fase 3: Audit & CAPA
                </li>
                <li class="flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-slate-300"></span>
                    Fase 4: Risiko & Akreditasi
                </li>
            </ul>
            <div class="mt-6 rounded-lg bg-teal-50 p-4 text-sm text-teal-800">
                <strong>Organisasi:</strong> {{ $user->organization->name }}<br>
                <strong>Klinik aktif:</strong> {{ $clinic?->name ?? '—' }}<br>
                <strong>Role:</strong> {{ $user->getRoleNames()->join(', ') }}
            </div>
        </div>
    </div>
@endsection
