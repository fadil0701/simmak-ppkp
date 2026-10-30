@extends('layouts.simmak')

@section('title', $capa->case_number.' — SIMMAK PPKP')
@section('page-title', 'Detail Tindak Lanjut')

@php
    $actor = auth()->user();
    $editableActions = $capa->status === 'closed'
        ? collect()
        : $capa->actions->filter(fn ($action) => $actor?->can('update', $action));
    $pendingActions = $editableActions->filter(fn ($action) => ! $action->hasSubmittedEvidence());
@endphp

@section('content')
    <div class="mb-4">
        <x-action action="back" href="{{ route('capa.index') }}" icon="chevron-left" label="Kembali" size="sm" />
    </div>

    {{-- A. Informasi Tindak Lanjut (otomatis dari temuan/penugasan) --}}
    <div class="simmak-card">
        <div class="simmak-card-header">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Informasi Tindak Lanjut</p>
                    <p class="font-mono text-sm text-slate-500">{{ $capa->case_number }}</p>
                    <h2 class="text-lg font-bold text-slate-900">Kasus Tindak Lanjut</h2>
                </div>
                <div class="flex gap-2">
                    <span class="simmak-badge {{ $capa->priority === 'critical' ? 'bg-red-100 text-red-800' : 'bg-orange-100 text-orange-800' }}">{{ \App\Support\UiLabel::capaPriority($capa->priority) }}</span>
                    <span class="simmak-badge bg-blue-100 text-blue-800">{{ \App\Support\UiLabel::capaStatus($capa->status) }}</span>
                </div>
            </div>
        </div>
        <div class="simmak-card-body space-y-4">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <span class="text-xs text-slate-500">Nomor Tindak Lanjut</span>
                    <p class="font-mono font-medium">{{ $capa->case_number }}</p>
                </div>
                <div>
                    <span class="text-xs text-slate-500">Klinik / Unit</span>
                    <p class="font-medium">{{ $capa->clinic?->name ?? '—' }}</p>
                </div>
                <div>
                    <span class="text-xs text-slate-500">Penanggung Jawab / PIC</span>
                    <p class="font-medium">{{ $capa->owner?->name ?? '—' }}</p>
                </div>
                <div>
                    <span class="text-xs text-slate-500">Target Penyelesaian</span>
                    <p class="font-medium {{ $capa->target_close_date?->isPast() && $capa->status !== 'closed' ? 'text-red-600' : '' }}">
                        {{ $capa->target_close_date?->format('d M Y') ?? '—' }}
                    </p>
                </div>
                <div>
                    <span class="text-xs text-slate-500">Status Tindak Lanjut</span>
                    <p class="font-medium">{{ \App\Support\UiLabel::capaStatus($capa->status) }}</p>
                </div>
                <div>
                    <span class="text-xs text-slate-500">Sumber</span>
                    <p class="font-medium">{{ ucfirst(str_replace('_', ' ', $capa->source_type)) }}</p>
                </div>
            </div>

            @if ($capa->sourceFinding)
                <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                    <span class="text-xs font-medium text-slate-500">Temuan Audit Terkait</span>
                    <a href="{{ route('findings.show', $capa->sourceFinding) }}" class="mt-1 block font-medium text-teal-700 hover:underline">
                        {{ $capa->sourceFinding->finding_code }} — {{ $capa->sourceFinding->title }}
                    </a>
                    @if ($capa->sourceFinding->clinicalAudit)
                        <a href="{{ route('audit.clinical.show', $capa->sourceFinding->clinicalAudit) }}" class="mt-1 block text-xs text-teal-700 hover:underline">
                            Audit klinis {{ $capa->sourceFinding->clinicalAudit->audit_number }}
                        </a>
                    @endif
                </div>
            @endif

            <div>
                <span class="text-xs text-slate-500">Pernyataan Masalah</span>
                <p class="mt-1 text-sm text-slate-700">{{ $capa->problem_statement }}</p>
            </div>
            @if ($capa->isOverdue())
                <p class="text-sm font-medium text-red-700">Terlambat terhadap tenggat — status kasus tidak diubah otomatis.</p>
            @endif
        </div>
    </div>

    {{-- D. Rencana tindakan (terpisah dari laporan pelaksanaan) --}}
    <div class="simmak-card mt-6">
        <div class="simmak-card-header flex flex-wrap items-center justify-between gap-2">
            <div>
                <h3 class="text-sm font-semibold text-slate-700">Rencana Tindakan</h3>
                <p class="text-xs text-slate-500">Rencana, PIC, dan tenggat. Uraian pelaksanaan diisi pada bagian Laporan Tindak Lanjut.</p>
            </div>
            <span class="text-xs text-slate-500">{{ $capa->actions->count() }} tindakan</span>
        </div>
        <div class="simmak-card-body overflow-x-auto">
            <table class="sim-table w-full">
                <thead>
                    <tr>
                        <th class="w-28">Jenis</th>
                        <th>Rencana tindakan</th>
                        <th class="w-40">PIC</th>
                        <th class="w-32">Tenggat</th>
                        <th class="w-36">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($capa->actions as $action)
                        <tr>
                            <td class="text-xs font-medium uppercase text-slate-500">{{ $action->action_type === 'preventive' ? 'Pencegahan' : 'Korektif' }}</td>
                            <td class="text-sm text-slate-700">{{ $action->description }}</td>
                            <td class="text-sm text-slate-600">{{ $action->assignee?->name ?? $capa->owner?->name ?? '—' }}</td>
                            <td class="text-sm {{ $action->due_date?->isPast() && $action->status !== 'verified' ? 'text-red-600' : 'text-slate-600' }}">
                                {{ $action->due_date?->format('d M Y') ?? '—' }}
                            </td>
                            <td>
                                <span class="simmak-badge bg-slate-100 text-slate-700">{{ \App\Support\UiLabel::capaActionStatus($action->status) }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="sim-table-empty">Belum ada rencana tindakan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @can('update', $capa)
                <form method="POST" action="{{ route('capa.store-action', $capa) }}" class="mt-4 grid gap-3 border-t border-slate-100 pt-4 sm:grid-cols-2">
                    @csrf
                    <h4 class="text-xs font-semibold uppercase text-slate-500 sm:col-span-2">Tambah tindakan</h4>
                    <select name="action_type" class="sim-form-select" required>
                        <option value="corrective">Korektif</option>
                        <option value="preventive">Pencegahan</option>
                    </select>
                    <input type="date" name="due_date" class="sim-form-input" value="{{ $capa->target_close_date?->format('Y-m-d') }}">
                    <textarea name="description" rows="2" required class="sim-form-textarea sm:col-span-2" placeholder="Rencana tindakan (bukan uraian pelaksanaan)"></textarea>
                    <select name="assigned_to" class="sim-form-select sm:col-span-2">
                        <option value="">— PIC tindakan (default: penanggung jawab kasus) —</option>
                        @foreach ($users as $assignee)
                            <option value="{{ $assignee->id }}" @selected($assignee->id === $capa->owner_id)>{{ $assignee->name }}</option>
                        @endforeach
                    </select>
                    <div>
                        <x-action action="create" icon="plus" label="Tambah Tindakan" size="sm" />
                    </div>
                </form>
            @endcan
        </div>
    </div>

    {{-- B. Laporan Tindak Lanjut --}}
    <div class="simmak-card mt-6">
        <div class="simmak-card-header flex flex-wrap items-center justify-between gap-2">
            <div>
                <h3 class="text-sm font-semibold text-slate-700">Laporan Tindak Lanjut</h3>
                <p class="text-xs text-slate-500">Uraian dan dokumen pelaksanaan yang sudah dikerjakan, terpisah dari rencana di atas.</p>
            </div>
            <span class="text-xs text-slate-500">{{ $capa->actions->sum(fn ($action) => $action->reports->count()) }} laporan</span>
        </div>
        <div class="simmak-card-body space-y-4">
            @forelse ($capa->actions as $action)
                @if (filled($action->evidence_notes) || $action->reports->isNotEmpty())
                    <div class="rounded-lg border border-slate-100 p-3">
                        <p class="text-xs font-medium text-slate-500">
                            {{ $action->action_type === 'preventive' ? 'Pencegahan' : 'Korektif' }}
                            · {{ \Illuminate\Support\Str::limit($action->description, 80) }}
                        </p>
                        @if (filled($action->evidence_notes))
                            <p class="mt-2 whitespace-pre-wrap text-sm text-slate-700">{{ $action->evidence_notes }}</p>
                            @if ($action->evidence_submitted_at)
                                <p class="mt-1 text-[11px] text-slate-400">Dikirim {{ $action->evidence_submitted_at->format('d M Y H:i') }}</p>
                            @endif
                        @endif
                        @foreach ($action->reports as $report)
                            <div class="mt-2 flex items-start justify-between gap-2 rounded-md border border-slate-100 bg-slate-50 p-2">
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-slate-800">{{ $report->title }}</div>
                                    <div class="text-[11px] text-slate-500">
                                        {{ $report->original_filename ?: $report->report_number }}
                                        · {{ $report->generated_at?->format('d M Y H:i') ?? '—' }}
                                    </div>
                                </div>
                                <div class="flex flex-shrink-0 gap-1">
                                    @can('view', $report)
                                        <x-action action="download" href="{{ route('capa.download-report', $report) }}" icon="download" label="Unduh" size="sm" />
                                    @endcan
                                    @can('delete', $report)
                                        <x-action action="delete" href="{{ route('capa.destroy-report', [$action, $report]) }}" icon="trash" label="Hapus" size="sm" method="DELETE" confirm="Hapus laporan ini dari tindakan tersebut?" />
                                    @endcan
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @empty
                <p class="text-sm text-slate-500">Belum ada tindakan, sehingga belum ada laporan pelaksanaan.</p>
            @endforelse

            @if ($capa->actions->isNotEmpty() && $capa->actions->every(fn ($action) => $action->reports->isEmpty() && blank($action->evidence_notes)))
                <p class="text-sm text-slate-500">Belum ada laporan pelaksanaan untuk tindak lanjut ini.</p>
            @endif

            @if ($editableActions->isNotEmpty())
                @if ($pendingActions->isNotEmpty())
                    <form method="POST" action="{{ route('capa.submit-case-evidence', $capa) }}" class="space-y-2 border-t border-slate-100 pt-4">
                        @csrf
                        <label class="sim-form-label">Uraian tindak lanjut yang telah dilakukan</label>
                        @if ($pendingActions->count() > 1)
                            <select name="capa_action_id" class="sim-form-select" required>
                                @foreach ($pendingActions as $action)
                                    <option value="{{ $action->id }}">
                                        {{ $action->action_type === 'preventive' ? 'Pencegahan' : 'Korektif' }} — {{ \Illuminate\Support\Str::limit($action->description, 70) }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <input type="hidden" name="capa_action_id" value="{{ $pendingActions->first()->id }}">
                        @endif
                        <textarea name="evidence_notes" rows="3" required class="sim-form-textarea" placeholder="Jelaskan tindakan yang sudah dikerjakan. Jangan mengulang rencana di atas.">{{ old('evidence_notes') }}</textarea>
                        <x-action action="save" icon="check-circle" label="Kirim Uraian Pelaksanaan" size="sm" />
                    </form>
                @endif

                @can('audit.report.view')
                    <form method="POST" action="{{ route('capa.store-case-report', $capa) }}" enctype="multipart/form-data"
                          class="space-y-2 border-t border-slate-100 pt-4">
                        @csrf
                        <label class="sim-form-label">Unggah laporan atau dokumen pendukung pelaksanaan</label>
                        @if ($editableActions->count() > 1)
                            <select name="capa_action_id" class="sim-form-select" required>
                                @foreach ($editableActions as $action)
                                    <option value="{{ $action->id }}">
                                        {{ $action->action_type === 'preventive' ? 'Pencegahan' : 'Korektif' }} — {{ \Illuminate\Support\Str::limit($action->description, 70) }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <input type="hidden" name="capa_action_id" value="{{ $editableActions->first()->id }}">
                        @endif
                        <input type="text" name="title" maxlength="255" required class="sim-form-input text-sm"
                               placeholder="Judul laporan tindak lanjut">
                        <input type="file" name="file" required class="sim-form-input text-sm"
                               accept=".pdf,.doc,.docx,.xls,.xlsx">
                        <x-action action="upload" icon="upload" label="Unggah Laporan" size="sm" />
                    </form>
                @endcan
            @elseif ($capa->actions->isEmpty())
                <p class="text-xs text-slate-500">Tambah rencana tindakan terlebih dahulu sebelum mengunggah laporan.</p>
            @endif
        </div>
    </div>

    {{-- C. Bukti Arsip Tindak Lanjut --}}
    <div class="simmak-card mt-6">
        <div class="simmak-card-header flex flex-wrap items-center justify-between gap-2">
            <div>
                <h3 class="text-sm font-semibold text-slate-700">Bukti Arsip Tindak Lanjut</h3>
                <p class="text-xs text-slate-500">Bukti pelaksanaan atau penyelesaian. Terpisah dari laporan dan dari dokumen temuan awal.</p>
            </div>
            <span class="text-xs text-slate-500">{{ $capa->actions->sum(fn ($action) => $action->evidenceLinks->count()) }} berkas</span>
        </div>
        <div class="simmak-card-body space-y-4">
            @forelse ($capa->actions as $action)
                @if ($action->evidenceLinks->isNotEmpty())
                    <div class="rounded-lg border border-slate-100 p-3">
                        <p class="text-xs font-medium text-slate-500">
                            {{ $action->action_type === 'preventive' ? 'Pencegahan' : 'Korektif' }}
                            · {{ \Illuminate\Support\Str::limit($action->description, 80) }}
                        </p>
                        @foreach ($action->evidenceLinks as $link)
                            @if ($link->evidence)
                                <div class="mt-2 flex items-center justify-between gap-2 rounded-md border border-slate-100 bg-slate-50 p-2">
                                    <div class="min-w-0">
                                        <a href="{{ route('audit.evidence.show', $link->evidence) }}"
                                           class="block truncate text-sm text-teal-700 hover:underline">{{ $link->evidence->original_filename }}</a>
                                        <div class="text-[11px] text-slate-500">
                                            {{ $link->description ?: ($link->evidence->description ?: '—') }}
                                            · {{ $link->evidence->uploaded_at?->format('d M Y H:i') ?? '—' }}
                                        </div>
                                    </div>
                                    <div class="flex flex-shrink-0 gap-1">
                                        <x-action action="download" href="{{ route('audit.evidence.download', $link->evidence) }}" icon="download" label="Unduh" size="sm" />
                                        @can('update', $action)
                                            <x-action action="delete" href="{{ route('capa.detach-evidence', [$action, $link]) }}" icon="trash" label="Lepas" size="sm" method="DELETE"
                                                      confirm="Lepas tautan berkas ini dari tindakan? Berkas tetap tersimpan di Arsip Bukti." />
                                        @endcan
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            @empty
                <p class="text-sm text-slate-500">Belum ada tindakan, sehingga belum ada bukti arsip.</p>
            @endforelse

            @if ($capa->actions->isNotEmpty() && $capa->actions->every(fn ($action) => $action->evidenceLinks->isEmpty()))
                <p class="text-sm text-slate-500">Belum ada bukti arsip untuk tindak lanjut ini.</p>
            @endif

            @if ($editableActions->isNotEmpty())
                <form method="POST" action="{{ route('capa.attach-case-evidence', $capa) }}" class="space-y-2 border-t border-slate-100 pt-4" enctype="multipart/form-data">
                    @csrf
                    <label class="sim-form-label">Unggah bukti tindak lanjut</label>
                    @if ($editableActions->count() > 1)
                        <select name="capa_action_id" class="sim-form-select" required>
                            @foreach ($editableActions as $action)
                                <option value="{{ $action->id }}">
                                    {{ $action->action_type === 'preventive' ? 'Pencegahan' : 'Korektif' }} — {{ \Illuminate\Support\Str::limit($action->description, 70) }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        <input type="hidden" name="capa_action_id" value="{{ $editableActions->first()->id }}">
                    @endif
                    <input type="file" name="file" required class="sim-form-input text-sm"
                           accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.csv,.txt">
                    <input type="text" name="description" maxlength="500" class="sim-form-input text-sm"
                           placeholder="Keterangan bukti (opsional)">
                    <x-action action="upload" icon="upload" label="Unggah Bukti Arsip" size="sm" />
                </form>
            @elseif ($capa->actions->isEmpty())
                <p class="text-xs text-slate-500">Tambah rencana tindakan terlebih dahulu sebelum mengunggah bukti.</p>
            @endif
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="simmak-card">
            <div class="simmak-card-header">
                <h3 class="text-sm font-semibold text-slate-700">Analisis Akar Masalah (RCA)</h3>
            </div>
            <div class="simmak-card-body space-y-4">
                @foreach ($capa->rca as $analysis)
                    <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase text-slate-500">{{ str_replace('_', ' ', $analysis->method) }}</span>
                            <span class="text-xs text-slate-500">{{ $analysis->created_at->format('d M Y') }}</span>
                        </div>
                        @if ($analysis->method === 'five_why' && isset($analysis->content['whys']))
                            <ol class="mt-2 list-decimal space-y-1 pl-4 text-sm text-slate-700">
                                @foreach ($analysis->content['whys'] as $why)
                                    <li><strong>{{ $why['question'] ?? '' }}</strong> — {{ $why['answer'] ?? '' }}</li>
                                @endforeach
                            </ol>
                        @elseif ($analysis->method === 'fishbone' && isset($analysis->content['categories']))
                            @foreach ($analysis->content['categories'] as $cat)
                                <div class="mt-2">
                                    <div class="text-xs font-semibold text-slate-600">{{ $cat['name'] }}</div>
                                    <ul class="mt-1 list-disc pl-4 text-sm text-slate-700">
                                        @foreach ($cat['causes'] ?? [] as $cause)
                                            <li>{{ $cause }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        @endif
                        <p class="mt-2 text-sm font-medium text-teal-800">Akar masalah: {{ $analysis->content['root_cause'] ?? '—' }}</p>
                    </div>
                @endforeach

                <form method="POST" action="{{ route('capa.store-rca', $capa) }}" class="space-y-3 border-t border-slate-100 pt-4">
                    @csrf
                    <div class="sim-form-group">
                        <label class="sim-form-label">Metode RCA</label>
                        <select name="method" id="rca-method" class="sim-form-select" onchange="document.getElementById('five-why-fields').classList.toggle('hidden', this.value !== 'five_why'); document.getElementById('fishbone-fields').classList.toggle('hidden', this.value !== 'fishbone');">
                            <option value="five_why">5 Why</option>
                            <option value="fishbone">Fishbone (Ishikawa)</option>
                        </select>
                    </div>
                    <div id="five-why-fields" class="space-y-2">
                        @for ($i = 1; $i <= 5; $i++)
                            <div class="grid gap-2 sm:grid-cols-2">
                                <input type="text" name="whys[{{ $i - 1 }}][question]" placeholder="Pertanyaan Why #{{ $i }}"
                                       class="sim-form-input">
                                <input type="text" name="whys[{{ $i - 1 }}][answer]" placeholder="Jawaban"
                                       class="sim-form-input">
                            </div>
                        @endfor
                    </div>
                    <div id="fishbone-fields" class="hidden space-y-2">
                        @foreach (['Manusia', 'Metode', 'Mesin', 'Material'] as $idx => $catName)
                            <div class="sim-form-group">
                                <input type="hidden" name="categories[{{ $idx }}][name]" value="{{ $catName }}">
                                <label class="sim-form-label">{{ $catName }} (satu penyebab per baris)</label>
                                <textarea name="categories[{{ $idx }}][causes]" rows="2" class="sim-form-textarea" placeholder="Penyebab..."></textarea>
                            </div>
                        @endforeach
                    </div>
                    <div class="sim-form-group">
                        <label class="sim-form-label">Akar Masalah</label>
                        <textarea name="root_cause" rows="2" required class="sim-form-textarea"></textarea>
                    </div>
                    <x-action action="save" icon="check-circle" label="Simpan RCA" />
                </form>
            </div>
        </div>

        <div class="simmak-card">
            <div class="simmak-card-header">
                <h3 class="text-sm font-semibold text-slate-700">Verifikasi Efektivitas</h3>
            </div>
            <div class="simmak-card-body space-y-4">
                @foreach ($capa->verifications as $verification)
                    <div class="border-b border-slate-50 pb-3 last:border-0">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-slate-800">{{ $verification->verification_date->format('d M Y') }}</span>
                            <span class="simmak-badge {{ $verification->is_effective ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">{{ \App\Support\UiLabel::capaVerificationResult($verification->result) }}</span>
                        </div>
                        <p class="mt-1 text-sm text-slate-600">{{ $verification->notes ?? '—' }}</p>
                        <p class="text-xs text-slate-500">Verifier: {{ $verification->verifier?->name }}</p>
                    </div>
                @endforeach

                @can('verify', $capa)
                    <form method="POST" action="{{ route('capa.store-verification', $capa) }}" class="space-y-3 border-t border-slate-100 pt-4">
                        @csrf
                        <p class="text-xs text-slate-500">Validasi oleh auditor yang ditugaskan, setelah klinik mengirim bukti. PIC/auditi tidak dapat menutup temuan sendiri.</p>
                        <div class="sim-form-group">
                            <label class="sim-form-label">Tanggal Verifikasi</label>
                            <input type="date" name="verification_date" value="{{ now()->toDateString() }}" required
                                   class="sim-form-input">
                        </div>
                        <div class="sim-form-group">
                            <label class="sim-form-label">Hasil</label>
                            <select name="result" required class="sim-form-select">
                                <option value="effective">Efektif / diterima</option>
                                <option value="needs_improvement">Perlu perbaikan</option>
                                <option value="not_effective">Tidak efektif</option>
                            </select>
                        </div>
                        <div class="sim-form-group">
                            <label class="sim-form-label">Catatan (wajib)</label>
                            <textarea name="notes" rows="2" required class="sim-form-textarea"></textarea>
                        </div>
                        <div class="sim-form-group">
                            <label class="sim-form-label">Tenggat baru (jika belum efektif)</label>
                            <input type="date" name="new_target_close_date" class="sim-form-input" min="{{ now()->toDateString() }}">
                        </div>
                        <x-action action="approve" icon="check-circle" label="Catat Verifikasi" />
                    </form>
                @else
                    @if ($capa->status !== 'closed')
                        <p class="text-xs text-slate-500">Temuan belum tertutup. Auditor yang ditugaskan memvalidasi kesesuaian tindak lanjut setelah bukti dikirim.</p>
                    @endif
                @endcan
            </div>
        </div>
    </div>
@endsection
