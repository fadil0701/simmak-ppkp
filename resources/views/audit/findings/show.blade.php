@extends('layouts.simmak')

@section('title', '{{ $finding->finding_code }} — SIMMAK PPKP')
@section('page-title', 'Detail Temuan: {{ $finding->finding_code }}')

@section('content')
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">{{ $finding->finding_code }} — {{ $finding->title }}</h2>
            <p class="text-sm text-slate-500">
                Klinik: {{ $finding->clinic?->name ?? '—' }} ·
                Eksekusi: {{ $finding->execution?->execution_code ?? '—' }}
            </p>
        </div>

        <div class="flex gap-2">
            @can('audit.finding.update', $finding)
                @if (in_array($finding->status, ['draft', 'under_review', 'returned_for_revision'], true))
                    <a href="{{ route('findings.edit', $finding) }}" class="sim-btn sim-btn-primary sim-btn-sm">
                        Sunting
                    </a>
                @endif
            @endcan
            @can('audit.finding.review', $finding)
                @if ($finding->status === 'under_review')
                    <form method="POST" action="{{ route('findings.review', $finding) }}" class="inline" onsubmit="return confirm('Konfirmasi temuan ini?');">
                        @csrf
                        <input type="hidden" name="review_decision" value="confirm">
                        <button type="submit" class="sim-btn sim-btn-primary sim-btn-sm">
                            Konfirmasi
                        </button>
                    </form>
                    <form method="POST" action="{{ route('findings.review', $finding) }}" class="inline" onsubmit="return confirm('Kembalikan untuk revisi?');">
                        @csrf
                        <input type="hidden" name="review_decision" value="return">
                        <input type="hidden" name="review_reason" value="Perlu revisi">
                        <button type="submit" class="sim-btn sim-btn-secondary sim-btn-sm">
                            Kembalikan
                        </button>
                    </form>
                @endif
            @endcan
            @can('audit.finding.close', $finding)
                @if ($finding->status !== 'closed')
                    <form method="POST" action="{{ route('findings.close', $finding) }}" class="inline" onsubmit="return confirm('Tutup temuan ini?');">
                        @csrf
                        <input type="hidden" name="close_reason" value="Telah diselesaikan">
                        <button type="submit" class="sim-btn sim-btn-primary sim-btn-sm">
                            Tutup Temuan
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('findings.reopen', $finding) }}" class="inline" onsubmit="return confirm('Buka kembali temuan ini?');">
                        @csrf
                        <input type="hidden" name="reopen_reason" value="Perlu peninjauan ulang">
                        <button type="submit" class="sim-btn sim-btn-secondary sim-btn-sm">
                            Buka Kembali
                        </button>
                    </form>
                @endif
            @endcan
            @can('escalate', $finding)
                @if ($finding->severity === 'critical' && ! $finding->is_escalated && $finding->status !== 'closed')
                    <form method="POST" action="{{ route('findings.escalate', $finding) }}" class="inline-flex items-center gap-2" onsubmit="return confirm('Eskalasi temuan kritis ini untuk prioritas tinggi?');">
                        @csrf
                        <input type="text" name="escalation_reason" required maxlength="1000" placeholder="Alasan eskalasi..." class="sim-form-input w-48 border-sim-danger">
                        <button type="submit" class="sim-btn sim-btn-danger sim-btn-sm">
                            Eskalasi Kritis
                        </button>
                    </form>
                @endif
            @endcan
            <a href="{{ route('findings.index') }}" class="sim-btn sim-btn-outline sim-btn-sm">
                Kembali
            </a>
        </div>
    </div>

    @if ($finding->is_escalated)
        <div class="simmak-card mb-6 border-red-200 bg-red-50">
            <div class="simmak-card-body p-4 flex items-start gap-3">
                <svg class="h-6 w-6 flex-shrink-0 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                <div>
                    <h3 class="font-semibold text-red-800">Temuan Dieskalasi — Prioritas Tinggi</h3>
                    <p class="text-sm text-red-700 mt-1">
                        Temuan ini telah ditandai sebagai prioritas tinggi pada {{ $finding->escalated_at?->format('d M Y H:i') }} oleh {{ $finding->escalatedBy?->name ?? '—' }} dan memerlukan tindak lanjut segera tanpa menunggu laporan final.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="simmak-card lg:col-span-2">
            <div class="simmak-card-header">
                <h3 class="font-semibold text-slate-800">Detail Temuan</h3>
            </div>
            <div class="simmak-card-body space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <span class="text-xs text-slate-500">Kode Temuan</span>
                        <div class="font-mono text-sm text-slate-700">{{ $finding->finding_code }}</div>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500">Tingkat Risiko</span>
                        <span class="simmak-badge {{ $finding->severity === 'critical' ? 'bg-red-100 text-red-800' : ($finding->severity === 'major' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700') }}">
                            {{ ucfirst($finding->severity) }}
                        </span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500">Tipe</span>
                        <span class="text-sm text-slate-600">{{ ucfirst(str_replace('_', ' ', $finding->finding_type ?? '-')) }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500">Status</span>
                        <span class="simmak-badge">{{ \App\Support\UiLabel::findingStatus($finding->status) }}</span>
                    </div>
                    <div class="lg:col-span-2">
                        <span class="text-xs text-slate-500">Kondisi Aktual</span>
                        <div class="text-sm text-slate-700 whitespace-pre-wrap">{{ $finding->condition ?? '—' }}</div>
                    </div>
                    <div class="lg:col-span-2">
                        <span class="text-xs text-slate-500">Persyaratan Standar</span>
                        <div class="text-sm text-slate-700 whitespace-pre-wrap">{{ $finding->requirement ?? '—' }}</div>
                    </div>
                    <div class="lg:col-span-2">
                        <span class="text-xs text-slate-500">Rekomendasi</span>
                        <div class="text-sm text-slate-700 whitespace-pre-wrap">{{ $finding->recommendation ?? '—' }}</div>
                    </div>
                    <div class="lg:col-span-2">
                        <span class="text-xs text-slate-500">Tanggapan Pihak Diaudit</span>
                        <div class="text-sm text-slate-700 whitespace-pre-wrap">{{ $finding->auditee_response ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="simmak-card lg:col-span-1">
            <div class="simmak-card-header">
                <h3 class="font-semibold text-slate-800">Penanggung Jawab</h3>
            </div>
            <div class="simmak-card-body space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-slate-500">PIC</span>
                    <span>{{ $finding->owner?->name ?? $finding->assignee?->name ?? '—' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-500">Tenggat</span>
                    <span class="{{ $finding->due_at && $finding->due_at->isPast() && $finding->status !== 'closed' ? 'text-red-600' : 'text-slate-600' }}">
                        {{ $finding->due_at?->format('d M Y') ?? '—' }}
                    </span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-500">Dievaluasi oleh</span>
                    <span>{{ $finding->reviewer?->name ?? '—' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-500">Ditutup pada</span>
                    <span>{{ $finding->closed_at?->format('d M Y H:i') ?? '—' }}</span>
                </div>
                @if ($finding->is_escalated)
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Eskalasi</span>
                        <span class="text-red-600 font-medium">{{ $finding->escalated_at?->format('d M Y H:i') ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Dieskalasi oleh</span>
                        <span>{{ $finding->escalatedBy?->name ?? '—' }}</span>
                    </div>
                    @if ($finding->escalation_reason)
                        <div class="mt-2 rounded-lg bg-red-50 border border-red-200 p-3">
                            <div class="text-xs font-medium text-red-700 mb-1">Alasan Eskalasi</div>
                            <p class="text-sm text-red-800">{{ $finding->escalation_reason }}</p>
                        </div>
                    @endif
                @endif
            </div>

            <div class="simmak-card-header mt-4">
                <h3 class="font-semibold text-slate-800">CAPA</h3>
            </div>
            <div class="simmak-card-body space-y-3">
                @if ($finding->capaCase)
                    <div class="border border-slate-100 rounded-lg p-3">
                        <div class="font-medium text-slate-800">{{ $finding->capaCase->case_number }}</div>
                        <div class="text-xs text-slate-600">{{ \App\Support\UiLabel::capaStatus($finding->capaCase->status) }}</div>
                        <a href="{{ route('capa.show', $finding->capaCase) }}" class="mt-1 text-sm font-medium text-teal-700 hover:underline">Lihat Detail CAPA →</a>
                    </div>
                @else
                    @can('audit.action.update')
                        <form method="POST" action="{{ route('findings.create-capa', $finding) }}" class="inline" onsubmit="return confirm('Buat CAPA dari temuan ini?');">
                            @csrf
                            <button type="submit" class="sim-btn sim-btn-primary w-full">
                                Buat CAPA
                            </button>
                        </form>
                    @else
                        <p class="text-xs text-slate-500">Tidak berwenang membuat CAPA.</p>
                    @endcan
                @endif
            </div>
        </div>
    </div>

    @if ($finding->evidenceLinks->isNotEmpty())
        <div class="simmak-card mt-6">
            <div class="simmak-card-header">
                <h3 class="font-semibold text-slate-800">Bukti Terkait</h3>
            </div>
            <div class="simmak-card-body space-y-3">
                @foreach ($finding->evidenceLinks as $link)
                    <div class="flex items-center justify-between p-3 border border-slate-100 rounded-lg">
                        <div>
                            <div class="font-medium text-slate-800">{{ $link->evidence->original_filename }}</div>
                            <div class="text-xs text-slate-500">{{ $link->evidence->mime_type }}</div>
                        </div>
                        <a href="{{ route('audit.evidence.download', $link->evidence) }}" class="text-sm font-medium text-teal-700 hover:underline">
                            Unduh
                            <svg class="h-3 w-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endsection
