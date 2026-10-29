@extends('layouts.simmak')

@section('title', $finding->finding_code.' — SIMMAK PPKP')
@section('page-title', 'Detail Temuan: '.$finding->finding_code)

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
                    <x-action action="edit" href="{{ route('findings.edit', $finding) }}" icon="pencil" label="Edit" size="sm" />
                @endif
            @endcan
            @can('audit.finding.review', $finding)
                @if ($finding->status === 'under_review')
                    <x-action action="approve" href="{{ route('findings.review', $finding) }}" icon="check-circle" label="Konfirmasi" size="sm"
                              method="POST" confirm="Konfirmasi temuan ini?" :fields="['review_decision' => 'confirm']" />
                    <x-action action="back" href="{{ route('findings.review', $finding) }}" icon="x-mark" label="Kembalikan" size="sm"
                              method="POST" confirm="Kembalikan temuan ini untuk revisi?"
                              :fields="['review_decision' => 'return', 'review_reason' => 'Perlu revisi']" />
                @endif
            @endcan
            @can('close', $finding)
                <x-action action="save" href="{{ route('findings.close', $finding) }}" icon="check-circle" label="Tutup Temuan" size="sm"
                          method="POST" confirm="Tutup temuan ini setelah verifikasi efektif?" :fields="['close_reason' => 'Verifikasi efektif tercatat']" />
            @endcan
            @can('reopen', $finding)
                <x-action action="back" href="{{ route('findings.reopen', $finding) }}" icon="x-mark" label="Buka Kembali" size="sm"
                          method="POST" confirm="Buka kembali temuan ini?" :fields="['reopen_reason' => 'Perlu peninjauan ulang']" />
            @endcan
            @can('escalate', $finding)
                @if ($finding->severity === 'critical' && ! $finding->is_escalated && $finding->status !== 'closed')
                    <form method="POST" action="{{ route('findings.escalate', $finding) }}" class="inline-flex items-center gap-2">
                        @csrf
                        <input type="text" name="escalation_reason" required maxlength="1000" placeholder="Alasan eskalasi..." class="sim-form-input w-48 border-sim-danger">
                        <x-action action="escalate" icon="alert-triangle" label="Eskalasi" size="sm"
                                  confirm="Eskalasi temuan kritis ini untuk prioritas tinggi?" />
                    </form>
                @endif
            @endcan
            <x-action action="back" href="{{ route('findings.index') }}" icon="chevron-left" label="Kembali" size="sm" />
        </div>
    </div>

    @if ($finding->clinicalAudit)
        <div class="simmak-card mb-6 border-teal-100 bg-teal-50">
            <div class="simmak-card-body p-4">
                <span class="text-xs font-medium text-teal-700">Sumber temuan: Audit Klinis</span>
                <a href="{{ route('audit.clinical.show', $finding->clinicalAudit) }}"
                   class="mt-1 block font-medium text-teal-800 hover:underline">
                    {{ $finding->clinicalAudit->audit_number }} — {{ $finding->clinicalAudit->title }}
                </a>
                @if ($finding->clinicalAudit->assignment)
                    <p class="mt-1 text-xs text-teal-700">Jadwal {{ $finding->clinicalAudit->assignment->assignment_code }}</p>
                @endif
                <p class="mt-1 text-xs text-teal-700">Temuan ini masuk ke daftar Temuan Audit bersama dan ditindaklanjuti melalui modul Tindak Lanjut.</p>
            </div>
        </div>
    @endif

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
                <h3 class="font-semibold text-slate-800">Tindak Lanjut</h3>
            </div>
            <div class="simmak-card-body space-y-3">
                @if ($finding->capaCase)
                    <div class="border border-slate-100 rounded-lg p-3">
                        <div class="font-medium text-slate-800">{{ $finding->capaCase->case_number }}</div>
                        <div class="text-xs text-slate-600">{{ \App\Support\UiLabel::capaStatus($finding->capaCase->status) }}</div>
                        <x-action action="detail" href="{{ route('capa.show', $finding->capaCase) }}" icon="tool" label="Detail Tindak Lanjut" size="sm" class="mt-2" />
                    </div>
                @else
                    @if (in_array($finding->status, ['confirmed', 'in_capa', 'action_required', 'pending_verification'], true))
                        @canany(['audit.action.update', 'capa.manage'])
                            <x-action action="create" href="{{ route('findings.create-capa', $finding) }}" icon="plus" label="Buat Tindak Lanjut" class="w-full"
                                      method="POST" confirm="Buat Tindak Lanjut dari temuan ini?" />
                        @else
                            <p class="text-xs text-slate-500">Tidak berwenang membuat tindak lanjut.</p>
                        @endcanany
                    @else
                        <p class="text-xs text-slate-500">Konfirmasi temuan terlebih dahulu sebelum membuat tindak lanjut.</p>
                    @endif
                @endif
            </div>
        </div>
    </div>

    {{-- Dokumen dipisah: bukti temuan (level temuan) vs dokumen tiap tindak
         lanjut, supaya berkas satu tindak lanjut tidak tampil sebagai milik
         tindak lanjut lain. --}}
    <div class="simmak-card mt-6">
        <div class="simmak-card-header">
            <h3 class="font-semibold text-slate-800">Dokumen Audit</h3>
        </div>
        <div class="simmak-card-body space-y-4">
            <div>
                <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Bukti Temuan</h4>
                @forelse ($finding->evidenceLinks as $link)
                    @if ($link->evidence)
                        <div class="mt-2 flex items-center justify-between rounded-lg border border-slate-100 p-3">
                            <div class="min-w-0">
                                <div class="truncate font-medium text-slate-800">{{ $link->evidence->original_filename }}</div>
                                <div class="text-xs text-slate-500">{{ $link->relationship_note ?: $link->evidence->mime_type }}</div>
                            </div>
                            <x-action action="download" href="{{ route('audit.evidence.download', $link->evidence) }}" icon="download" label="Unduh" size="sm" />
                        </div>
                    @endif
                @empty
                    <p class="mt-2 text-sm text-slate-500">Belum ada bukti yang dilampirkan pada temuan ini.</p>
                @endforelse

                @foreach ($finding->reports as $report)
                    <div class="mt-2 flex items-center justify-between rounded-lg border border-slate-100 p-3">
                        <div class="min-w-0">
                            <div class="truncate font-medium text-slate-800">{{ $report->title }}</div>
                            <div class="text-xs text-slate-500">{{ $report->report_number }} · {{ strtoupper($report->file_type ?: 'PDF') }}</div>
                        </div>
                        @can('view', $report)
                            <x-action action="download" href="{{ route('capa.download-report', $report) }}" icon="download" label="Unduh" size="sm" />
                        @endcan
                    </div>
                @endforeach
            </div>

            <div class="border-t border-slate-100 pt-4">
                <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Dokumen per Tindak Lanjut</h4>
                @if ($finding->capaCase && $finding->capaCase->actions->isNotEmpty())
                    <p class="mt-1 text-xs text-slate-500">Kasus {{ $finding->capaCase->case_number }} — setiap tindakan memiliki laporan dan bukti arsipnya sendiri.</p>

                    @foreach ($finding->capaCase->actions as $action)
                        <div class="mt-3 rounded-lg border border-slate-100 bg-slate-50 p-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="text-sm font-medium text-slate-800">
                                    {{ $action->action_type === 'preventive' ? 'Pencegahan' : 'Korektif' }} — {{ \Illuminate\Support\Str::limit($action->description, 70) }}
                                </div>
                                <span class="simmak-badge bg-slate-100 text-slate-700">{{ \App\Support\UiLabel::capaActionStatus($action->status) }}</span>
                            </div>

                            <div class="mt-2">
                                <span class="text-[11px] font-semibold uppercase text-slate-500">Laporan ({{ $action->reports->count() }})</span>
                                @forelse ($action->reports as $report)
                                    <div class="mt-1 flex items-center justify-between gap-2 rounded-md border border-slate-100 bg-white p-2">
                                        <div class="min-w-0">
                                            <div class="truncate text-sm text-slate-800">{{ $report->title }}</div>
                                            <div class="text-[11px] text-slate-500">{{ $report->report_number }} · {{ $report->generated_at?->format('d M Y') ?? '—' }}</div>
                                        </div>
                                        @can('view', $report)
                                            <x-action action="download" href="{{ route('capa.download-report', $report) }}" icon="download" label="Unduh" size="sm" />
                                        @endcan
                                    </div>
                                @empty
                                    <p class="mt-1 text-xs text-slate-500">Belum ada laporan.</p>
                                @endforelse
                            </div>

                            <div class="mt-2">
                                <span class="text-[11px] font-semibold uppercase text-slate-500">Bukti Arsip ({{ $action->evidenceLinks->count() }})</span>
                                @forelse ($action->evidenceLinks as $link)
                                    @if ($link->evidence)
                                        <div class="mt-1 flex items-center justify-between gap-2 rounded-md border border-slate-100 bg-white p-2">
                                            <div class="min-w-0">
                                                <a href="{{ route('audit.evidence.show', $link->evidence) }}"
                                                   class="block truncate text-sm text-teal-700 hover:underline">{{ $link->evidence->original_filename }}</a>
                                                <div class="text-[11px] text-slate-500">{{ $link->description ?: '—' }}</div>
                                            </div>
                                            <x-action action="download" href="{{ route('audit.evidence.download', $link->evidence) }}" icon="download" label="Unduh" size="sm" />
                                        </div>
                                    @endif
                                @empty
                                    <p class="mt-1 text-xs text-slate-500">Belum ada bukti arsip.</p>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                @else
                    <p class="mt-2 text-sm text-slate-500">Belum ada tindak lanjut sehingga belum ada dokumen tindak lanjut.</p>
                @endif
            </div>
        </div>
    </div>
@endsection
