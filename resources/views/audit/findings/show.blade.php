@extends('layouts.simmak')

@section('title', $finding->finding_code.' — SIMMAK PPKP')
@section('page-title', 'Detail Temuan Audit')

@section('content')
    <div class="mb-4">
        <a href="{{ route('findings.index') }}" class="text-sm font-medium text-teal-700 hover:underline">← Kembali ke Daftar Temuan</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="simmak-card lg:col-span-2">
            <div class="simmak-card-header">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-mono text-sm text-slate-500">{{ $finding->finding_code }}</p>
                        <h2 class="text-lg font-bold text-slate-900">{{ $finding->title }}</h2>
                    </div>
                    <div class="flex gap-2">
                        <span class="simmak-badge {{ $finding->severity === 'critical' ? 'bg-red-100 text-red-800' : 'bg-orange-100 text-orange-800' }}">{{ \App\Support\UiLabel::findingSeverity($finding->severity) }}</span>
                        <span class="simmak-badge bg-slate-100 text-slate-700">{{ \App\Support\UiLabel::findingStatus($finding->status) }}</span>
                    </div>
                </div>
            </div>
            <div class="simmak-card-body space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><span class="text-xs text-slate-500">Klinik</span><p class="font-medium">{{ $finding->clinic?->name ?? '—' }}</p></div>
                    <div><span class="text-xs text-slate-500">Jadwal Audit</span><p class="font-medium">{{ $finding->schedule?->title ?? '—' }}</p></div>
                    <div><span class="text-xs text-slate-500">Program</span><p class="font-medium">{{ $finding->schedule?->program?->name ?? '—' }}</p></div>
                    <div><span class="text-xs text-slate-500">Penanggung Jawab</span><p class="font-medium">{{ $finding->assignee?->name ?? '—' }}</p></div>
                    <div><span class="text-xs text-slate-500">Identifikasi</span><p class="font-medium">{{ $finding->identified_at->format('d M Y H:i') }}</p></div>
                    <div><span class="text-xs text-slate-500">Referensi Standar</span><p class="font-medium">{{ $finding->requirement_reference ?? '—' }}</p></div>
                </div>
                @if ($finding->description)
                    <div>
                        <span class="text-xs text-slate-500">Deskripsi</span>
                        <p class="mt-1 text-sm text-slate-700">{{ $finding->description }}</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="simmak-card p-5">
            <h3 class="mb-3 font-semibold text-slate-800">Tindak Lanjut CAPA</h3>
            @if ($finding->capaCase)
                <p class="mb-3 text-sm text-slate-600">CAPA sudah dibuat untuk temuan ini.</p>
                <a href="{{ route('capa.show', $finding->capaCase) }}" class="inline-flex rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800">
                    Lihat {{ $finding->capaCase->case_number }}
                </a>
            @elseif ($finding->status === 'open')
                <p class="mb-3 text-sm text-slate-600">Buat kasus CAPA untuk menindaklanjuti temuan ini.</p>
                <form method="POST" action="{{ route('findings.create-capa', $finding) }}">
                    @csrf
                    <button type="submit" class="w-full rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800">Buat CAPA</button>
                </form>
            @else
                <p class="text-sm text-slate-500">Temuan sudah ditindaklanjuti.</p>
            @endif
        </div>
    </div>
@endsection
