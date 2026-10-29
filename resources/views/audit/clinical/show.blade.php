@extends('layouts.simmak')

@section('title', $audit->audit_number.' — SIMMAK PPKP')
@section('page-title', 'Detail Audit Klinis: '.$audit->audit_number)

@section('content')
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">{{ $audit->audit_number }} — {{ $audit->title }}</h2>
            <p class="text-sm text-slate-500">
                {{ $audit->clinic?->name ?? '—' }} · {{ $audit->audit_date?->format('d M Y') ?? '—' }}
                @if ($audit->assignment)
                    · Jadwal {{ $audit->assignment->assignment_code }}
                @endif
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            @can('update', $audit)
                <x-action action="edit" href="{{ route('audit.clinical.edit', $audit) }}" icon="pencil" label="Edit" size="sm" />
            @endcan
            @can('delete', $audit)
                @if ($audit->findings->isEmpty() && $audit->evidences->isEmpty())
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
                <div><span class="text-xs text-slate-500">Klinik / Unit</span><p class="font-medium">{{ $audit->clinic?->name ?? '—' }}</p></div>
                <div><span class="text-xs text-slate-500">Auditor</span><p class="font-medium">{{ $audit->auditor?->name ?? '—' }}</p></div>
                <div><span class="text-xs text-slate-500">Penanggung Jawab</span><p class="font-medium">{{ $audit->pic?->name ?? '—' }}</p></div>
                @if ($audit->medical_record_ref)
                    <div><span class="text-xs text-slate-500">Rekam Medis</span><p class="font-mono font-medium">{{ $audit->medical_record_ref }}</p></div>
                @endif
                <div>
                    <span class="text-xs text-slate-500">Periode</span>
                    <p class="font-medium">
                        {{ $audit->period_start?->format('d M Y') ?? '—' }} – {{ $audit->period_end?->format('d M Y') ?? '—' }}
                    </p>
                </div>
                <div>
                    <span class="text-xs text-slate-500">Nilai Kesesuaian</span>
                    <p class="font-medium">{{ $audit->conformity_score !== null ? $audit->conformity_score.'%' : '—' }}</p>
                </div>
                <div class="sm:col-span-2">
                    <span class="text-xs text-slate-500">Standar Acuan</span>
                    <p class="font-medium">
                        @if ($audit->standard)
                            {{ $audit->standard->code }} — {{ $audit->standard->title }}
                        @else
                            {{ $audit->standard_reference ?: '—' }}
                        @endif
                    </p>
                    @if ($audit->standard && $audit->standard_reference)
                        <p class="text-xs text-slate-500">Rujukan: {{ $audit->standard_reference }}</p>
                    @endif
                </div>

                @foreach ([
                    'scope' => 'Ruang Lingkup',
                    'criteria' => 'Standar / Kriteria',
                    'indicators' => 'Indikator',
                    'objective_evidence' => 'Bukti Objektif',
                    'assessment_result' => 'Hasil Penilaian',
                    'nonconformity_notes' => 'Uraian Ketidaksesuaian',
                    'problem_analysis' => 'Analisis Ketidaksesuaian / Masalah',
                    'notes' => 'Catatan',
                    'conclusion' => 'Kesimpulan',
                ] as $field => $label)
                    @if (filled($audit->{$field}))
                        <div class="sm:col-span-2">
                            <span class="text-xs text-slate-500">{{ $label }}</span>
                            <p class="mt-1 whitespace-pre-wrap text-sm text-slate-700">{{ $audit->{$field} }}</p>
                        </div>
                    @endif
                @endforeach
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
    {{-- Elemen penilaian: indikator/kriteria + hasil per elemen (skala 0/5/10). --}}
    <div class="simmak-card mt-6">
        <div class="simmak-card-header flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-sm font-semibold text-slate-700">Elemen Penilaian</h3>
            <span class="text-xs text-slate-500">{{ $audit->items->count() }} elemen</span>
        </div>
        <div class="simmak-card-body overflow-x-auto">
            <table class="sim-table w-full">
                <thead>
                    <tr>
                        <th class="w-20">Kode</th>
                        <th>Elemen Penilaian</th>
                        <th class="w-32">Indikator</th>
                        <th class="w-32">Hasil</th>
                        <th class="w-16 text-center">Skor</th>
                        <th class="w-48">Catatan Temuan</th>
                        <th class="w-20 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($audit->items as $item)
                        <tr>
                            <td class="font-mono text-xs text-slate-500">{{ $item->code ?? '—' }}</td>
                            <td class="text-sm text-slate-700">
                                {{ $item->element }}
                                @if ($item->criteria)
                                    <div class="text-xs text-slate-500">Kriteria: {{ $item->criteria }}</div>
                                @endif
                                @if ($item->objective_evidence)
                                    <div class="text-xs text-slate-500">Bukti: {{ $item->objective_evidence }}</div>
                                @endif
                            </td>
                            <td class="text-sm text-slate-600">{{ $item->indicator ?? '—' }}</td>
                            <td>
                                <span class="sim-badge sim-badge-status
                                    @switch($item->result)
                                        @case('conform') border-sim-success bg-sim-success-bg text-sim-success
                                        @case('partial') border-sim-warning bg-sim-warning-bg text-sim-warning
                                        @case('non_conform') border-sim-danger bg-sim-danger-bg text-sim-danger
                                        @default border-sim-neutral bg-sim-neutral-bg text-sim-neutral
                                    @endswitch
                                ">
                                    {{ \App\Support\UiLabel::clinicalResult($item->result) }}
                                </span>
                            </td>
                            <td class="text-center font-mono text-xs">{{ $item->score ?? '—' }}</td>
                            <td class="text-sm text-slate-600">{{ $item->finding_note ?? '—' }}</td>
                            <td class="text-right">
                                @can('update', $audit)
                                    <x-action action="delete" href="{{ route('audit.clinical.items.destroy', [$audit, $item]) }}" icon="trash" label="Hapus" size="sm" method="DELETE" confirm="Hapus elemen penilaian ini?" />
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="sim-table-empty">Belum ada elemen penilaian.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @can('update', $audit)
            <div class="simmak-card-body border-t border-slate-100">
                <form method="POST" action="{{ route('audit.clinical.items.store', $audit) }}" class="grid gap-3 sm:grid-cols-2">
                    @csrf
                    <input type="text" name="code" maxlength="60" class="sim-form-input" placeholder="Kode elemen (opsional)">
                    <input type="text" name="indicator" maxlength="255" class="sim-form-input" placeholder="Indikator">
                    <textarea name="element" rows="2" required class="sim-form-textarea sm:col-span-2" placeholder="Elemen penilaian (wajib)"></textarea>
                    <textarea name="criteria" rows="2" class="sim-form-textarea sm:col-span-2" placeholder="Kriteria/standar elemen (opsional)"></textarea>
                    <textarea name="objective_evidence" rows="2" class="sim-form-textarea sm:col-span-2" placeholder="Bukti objektif yang diperiksa"></textarea>
                    <select name="result" class="sim-form-select" required>
                        @foreach ($results as $result)
                            <option value="{{ $result }}">{{ \App\Support\UiLabel::clinicalResult($result) }}</option>
                        @endforeach
                    </select>
                    <select name="score" class="sim-form-select">
                        <option value="">— Skor (opsional) —</option>
                        @foreach ($scores as $score)
                            <option value="{{ $score }}">{{ $score }}</option>
                        @endforeach
                    </select>
                    <textarea name="finding_note" rows="2" class="sim-form-textarea sm:col-span-2" placeholder="Catatan temuan/ketidaksesuaian elemen"></textarea>
                    <div class="sm:col-span-2">
                        <x-action action="save" icon="check-circle" label="Tambah Elemen" size="sm" />
                    </div>
                </form>
            </div>
        @endcan
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

    {{-- Dokumentasi pendukung memakai tabel evidence (tampil juga di Arsip Bukti). --}}
    <div class="simmak-card mt-6">
        <div class="simmak-card-header flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-sm font-semibold text-slate-700">Dokumentasi Pendukung</h3>
            <span class="text-xs text-slate-500">{{ $audit->evidences->count() }} berkas</span>
        </div>
        <div class="simmak-card-body space-y-3">
            @forelse ($audit->evidences as $evidence)
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
                        @can('delete', $evidence)
                            <x-action action="delete" href="{{ route('audit.clinical.evidence.destroy', [$audit, $evidence]) }}" icon="trash" label="Hapus" size="sm" method="DELETE" confirm="Hapus dokumentasi ini?" />
                        @endcan
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">Belum ada dokumentasi pendukung.</p>
            @endforelse

            @can('update', $audit)
                <form method="POST" action="{{ route('audit.clinical.evidence.store', $audit) }}" enctype="multipart/form-data"
                      class="grid gap-3 border-t border-slate-100 pt-3 sm:grid-cols-2">
                    @csrf
                    <input type="file" name="file" required class="sim-form-input sm:col-span-2"
                           accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.csv,.txt">
                    <input type="text" name="description" maxlength="500" class="sim-form-input" placeholder="Keterangan berkas (opsional)">
                    <div class="flex items-end">
                        <x-action action="upload" icon="upload" label="Unggah Dokumentasi" size="sm" />
                    </div>
                </form>
            @endcan
        </div>
    </div>
@endsection
