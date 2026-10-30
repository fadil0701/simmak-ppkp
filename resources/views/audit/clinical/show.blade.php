@extends('layouts.simmak')

@section('title', $audit->audit_number.' — SIMMAK PPKP')
@section('page-title', 'Detail Audit Klinis: '.$audit->audit_number)

@section('content')
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">{{ $audit->audit_number }} — {{ $audit->title }}</h2>
            <p class="text-sm text-slate-500">
                {{ $audit->clinic?->name ?? '—' }}
                @if ($audit->assignment)
                    · Jadwal {{ $audit->assignment->assignment_code }}
                    @if ($audit->assignment->scheduled_start || $audit->assignment->scheduled_end)
                        ({{ $audit->assignment->scheduled_start?->format('d M Y') ?? '—' }}
                        – {{ $audit->assignment->scheduled_end?->format('d M Y') ?? '—' }})
                    @endif
                @endif
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            @can('update', $audit)
                <x-action action="edit" href="{{ route('audit.clinical.edit', $audit) }}" icon="pencil" label="Edit" size="sm" />
            @endcan
            @can('delete', $audit)
                @if ($audit->findings->isEmpty() && $audit->initialEvidences->isEmpty())
                    <x-action action="delete" href="{{ route('audit.clinical.destroy', $audit) }}" icon="trash" label="Hapus" size="sm" method="DELETE" confirm="Hapus audit klinis ini?" />
                @endif
            @endcan
            <x-action action="back" href="{{ route('audit.clinical.index') }}" icon="chevron-left" label="Kembali" size="sm" />
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="simmak-card lg:col-span-2">
            <div class="simmak-card-header flex flex-wrap items-center justify-between gap-2">
                <h3 class="text-sm font-semibold text-slate-700">Informasi Audit</h3>
                <span class="simmak-badge bg-slate-100 text-slate-700">{{ \App\Support\UiLabel::clinicalAuditStatus($audit->status) }}</span>
            </div>
            <div class="simmak-card-body grid gap-4 sm:grid-cols-2">
                <div>
                    <span class="text-xs text-slate-500">Jadwal Penugasan</span>
                    <p class="font-medium">
                        {{ $audit->assignment?->assignment_code ?? '—' }}
                        @if ($audit->assignment?->scheduled_start || $audit->assignment?->scheduled_end)
                            <span class="font-normal text-slate-500">
                                ({{ $audit->assignment->scheduled_start?->format('d M Y') ?? '—' }}
                                – {{ $audit->assignment->scheduled_end?->format('d M Y') ?? '—' }})
                            </span>
                        @endif
                    </p>
                </div>
                <div>
                    <span class="text-xs text-slate-500">Nomor Audit Klinis</span>
                    <p class="font-mono font-medium">{{ $audit->audit_number }}</p>
                </div>
                <div>
                    <span class="text-xs text-slate-500">Klinik / Unit</span>
                    <p class="font-medium">
                        @if ($audit->clinic)
                            {{ $audit->clinic->code }} — {{ $audit->clinic->name }}
                        @else
                            —
                        @endif
                    </p>
                </div>
                <div>
                    <span class="text-xs text-slate-500">Auditor</span>
                    <p class="font-medium">{{ $audit->auditor?->name ?? '—' }}</p>
                </div>
                <div>
                    <span class="text-xs text-slate-500">Rekam Medis</span>
                    <p class="font-mono font-medium">{{ $audit->medical_record_ref ?: '—' }}</p>
                </div>
                <div>
                    <span class="text-xs text-slate-500">Ketidaksesuaian</span>
                    <p class="font-medium">{{ filled($audit->nonconformity_notes) ? 'Ada temuan' : 'Tidak ada' }}</p>
                </div>
                <div class="sm:col-span-2">
                    <span class="text-xs text-slate-500">Uraian Ketidaksesuaian</span>
                    <p class="mt-1 whitespace-pre-wrap text-sm text-slate-700">{{ $audit->nonconformity_notes ?: '—' }}</p>
                </div>
                <div class="sm:col-span-2">
                    <span class="text-xs text-slate-500">Analisis Ketidaksesuaian / Masalah</span>
                    <p class="mt-1 whitespace-pre-wrap text-sm text-slate-700">{{ $audit->problem_analysis ?: '—' }}</p>
                </div>
            </div>
        </div>

        <div class="simmak-card">
            <div class="simmak-card-header">
                <h3 class="text-sm font-semibold text-slate-700">Status Audit</h3>
            </div>
            <div class="simmak-card-body space-y-3">
                @can('update', $audit)
                    <form method="POST" action="{{ route('audit.clinical.status', $audit) }}" class="space-y-2">
                        @csrf
                        <select name="status" class="sim-form-select">
                            @foreach (['draft', 'in_progress', 'completed', 'closed'] as $status)
                                <option value="{{ $status }}" {{ $audit->status === $status ? 'selected' : '' }}>
                                    {{ \App\Support\UiLabel::clinicalAuditStatus($status) }}
                                </option>
                            @endforeach
                        </select>
                        <x-action action="save" icon="check-circle" label="Perbarui Status" size="sm" />
                    </form>
                @else
                    <p class="text-sm text-slate-600">Status saat ini: {{ \App\Support\UiLabel::clinicalAuditStatus($audit->status) }}</p>
                @endcan

                <div class="border-t border-slate-100 pt-3">
                    <p class="text-xs text-slate-500">Temuan audit klinis masuk ke menu Temuan Audit bersama.</p>
                    <a href="{{ route('findings.index') }}" class="mt-1 inline-block text-sm font-medium text-teal-700 hover:underline">
                        Buka daftar Temuan Audit →
                    </a>
                </div>
            </div>
        </div>
    </div>
    {{-- Temuan audit klinis memakai modul Temuan Audit bersama (tindak lanjut via CAPA). --}}
    <div class="simmak-card mt-6">
        <div class="simmak-card-header flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-sm font-semibold text-slate-700">Temuan Audit Klinis</h3>
            <div class="flex items-center gap-2">
                <span class="text-xs text-slate-500">{{ $audit->findings->count() }} temuan</span>
                @can('audit.finding.create')
                    <x-action action="create" href="{{ route('findings.create', ['clinical_audit_id' => $audit->id]) }}" icon="plus" label="Buat Temuan" size="sm" />
                @endcan
            </div>
        </div>
        <div class="simmak-card-body overflow-x-auto">
            <table class="sim-table w-full">
                <thead>
                    <tr>
                        <th class="w-28">Kode</th>
                        <th>Judul</th>
                        <th class="w-28 text-center">Severity</th>
                        <th class="w-32 text-center">Status</th>
                        <th class="w-32">Tindak Lanjut</th>
                        <th class="w-24 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($audit->findings as $finding)
                        <tr>
                            <td class="font-mono text-xs text-slate-500">{{ $finding->finding_code }}</td>
                            <td class="text-sm text-slate-700">{{ $finding->title }}</td>
                            <td class="text-center text-xs text-slate-600">{{ \App\Support\UiLabel::findingSeverity($finding->severity) }}</td>
                            <td class="text-center text-xs text-slate-600">{{ \App\Support\UiLabel::findingStatus($finding->status) }}</td>
                            <td class="text-xs">
                                @if ($finding->capaCase)
                                    <a href="{{ route('capa.show', $finding->capaCase) }}" class="font-medium text-teal-700 hover:underline">
                                        {{ $finding->capaCase->case_number }}
                                    </a>
                                @else
                                    <span class="text-slate-400">Belum dibuat</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <x-action action="detail" href="{{ route('findings.show', $finding) }}" icon="file-text" label="Detail" size="sm" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="sim-table-empty">Belum ada temuan dari audit klinis ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Dokumen temuan awal yang sudah tersimpan tetap dapat diakses.
         Unggah baru dikelola di Detail Temuan / Tindak Lanjut, bukan di sini. --}}
    @if ($audit->initialEvidences->isNotEmpty())
        <div class="simmak-card mt-6">
            <div class="simmak-card-header flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h3 class="text-sm font-semibold text-slate-700">Dokumen Temuan Awal</h3>
                    <p class="text-xs text-slate-500">Berkas pendukung audit klinis ini. Bukti penyelesaian tindak lanjut tidak ditampilkan di sini.</p>
                </div>
                <span class="text-xs text-slate-500">{{ $audit->initialEvidences->count() }} berkas</span>
            </div>
            <div class="simmak-card-body space-y-3">
                @foreach ($audit->initialEvidences as $evidence)
                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-slate-100 p-3">
                        <div class="min-w-0">
                            <a href="{{ route('audit.evidence.show', $evidence) }}" class="block truncate text-sm font-medium text-teal-700 hover:underline">
                                {{ $evidence->original_filename }}
                            </a>
                            <div class="text-xs text-slate-500">
                                {{ $evidence->description ?: '—' }} · {{ $evidence->uploader?->name ?? '—' }}
                                · {{ $evidence->uploaded_at?->format('d M Y H:i') ?? '—' }}
                            </div>
                        </div>
                        <div class="flex gap-1">
                            <x-action action="download" href="{{ route('audit.evidence.download', $evidence) }}" icon="download" label="Unduh" size="sm" />
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endsection
