@extends('layouts.simmak')

@section('title', $capa->case_number.' — SIMMAK PPKP')
@section('page-title', 'Detail Tindak Lanjut')

@section('content')
    <div class="mb-4">
        <x-action action="back" href="{{ route('capa.index') }}" icon="chevron-left" label="Kembali" size="sm" />
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="simmak-card lg:col-span-2">
            <div class="simmak-card-header">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
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
                <div>
                    <span class="text-xs text-slate-500">Pernyataan Masalah</span>
                    <p class="mt-1 text-sm text-slate-700">{{ $capa->problem_statement }}</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><span class="text-xs text-slate-500">Klinik</span><p class="font-medium">{{ $capa->clinic?->name ?? '—' }}</p></div>
                    <div><span class="text-xs text-slate-500">Penanggung Jawab</span><p class="font-medium">{{ $capa->owner?->name ?? '—' }}</p></div>
                    <div><span class="text-xs text-slate-500">Sumber</span><p class="font-medium">{{ ucfirst(str_replace('_', ' ', $capa->source_type)) }}</p></div>
                    <div><span class="text-xs text-slate-500">Target Penutupan</span><p class="font-medium {{ $capa->target_close_date->isPast() && $capa->status !== 'closed' ? 'text-red-600' : '' }}">{{ $capa->target_close_date->format('d M Y') }}</p></div>
                </div>
                @if ($capa->isOverdue())
                    <p class="text-sm font-medium text-red-700">Terlambat terhadap tenggat — status kasus tidak diubah otomatis.</p>
                @endif
                @if ($capa->sourceFinding)
                    <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                        <span class="text-xs font-medium text-slate-500">Temuan Audit Terkait</span>
                        <a href="{{ route('findings.show', $capa->sourceFinding) }}" class="mt-1 block font-medium text-teal-700 hover:underline">
                            {{ $capa->sourceFinding->finding_code }} — {{ $capa->sourceFinding->title }}
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <div class="simmak-card">
            <div class="simmak-card-header">
                <h3 class="text-sm font-semibold text-slate-700">Tindak Lanjut</h3>
            </div>
            <div class="simmak-card-body space-y-3">
                @forelse ($capa->actions as $action)
                    <div class="border-b border-slate-50 pb-3 last:border-0">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium uppercase text-slate-500">{{ $action->action_type === 'preventive' ? 'Pencegahan' : 'Korektif' }}</span>
                            <span class="simmak-badge bg-slate-100 text-slate-700">{{ \App\Support\UiLabel::capaActionStatus($action->status) }}</span>
                        </div>
                        <p class="mt-1 text-sm text-slate-700">{{ $action->description }}</p>
                        <p class="mt-1 text-xs text-slate-500">PIC: {{ $action->assignee?->name ?? '—' }}
                            @if ($action->due_date)
                                · Tenggat {{ $action->due_date->format('d M Y') }}
                            @endif
                        </p>
                        @if ($action->hasSubmittedEvidence())
                            <p class="mt-2 text-xs text-slate-600 whitespace-pre-wrap">Bukti: {{ $action->evidence_notes }}</p>
                            @foreach ($action->evidenceLinks as $link)
                                @if ($link->evidence)
                                    <p class="text-xs">
                                        <a href="{{ route('audit.evidence.download', $link->evidence) }}" class="text-teal-700 hover:underline">{{ $link->evidence->original_filename }}</a>
                                    </p>
                                @endif
                            @endforeach
                            <p class="text-[11px] text-slate-400">Dikirim {{ $action->evidence_submitted_at?->format('d M Y H:i') }} · menunggu validasi auditor</p>
                        @elseif ($capa->status !== 'closed')
                            @can('update', $action)
                                <form method="POST" action="{{ route('capa.submit-evidence', $action) }}" class="mt-2 space-y-2" enctype="multipart/form-data">
                                    @csrf
                                    <label class="sim-form-label">Uraian tindak lanjut</label>
                                    <textarea name="evidence_notes" rows="2" class="sim-form-textarea" placeholder="Uraikan tindakan yang sudah dikerjakan.">{{ old('evidence_notes') }}</textarea>
                                    <label class="sim-form-label">Upload bukti (PDF/gambar/Office, maks. 10 MB)</label>
                                    <input type="file" name="file" class="sim-form-input text-sm" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                                    <x-action action="upload" icon="upload" label="Kirim Bukti Tindak Lanjut" size="sm" />
                                </form>
                            @endcan
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Belum ada tindakan terdaftar.</p>
                @endforelse

                @can('update', $capa)
                    @if ($capa->status !== 'closed')
                        <form method="POST" action="{{ route('capa.store-action', $capa) }}" class="space-y-2 border-t border-slate-100 pt-3">
                            @csrf
                            <h4 class="text-xs font-semibold uppercase text-slate-500">Tambah tindakan</h4>
                            <select name="action_type" class="sim-form-select" required>
                                <option value="corrective">Korektif</option>
                                <option value="preventive">Pencegahan</option>
                            </select>
                            <textarea name="description" rows="2" required class="sim-form-textarea" placeholder="Rencana tindakan"></textarea>
                            <select name="assigned_to" class="sim-form-select">
                                <option value="">— PIC tindakan (default: penanggung jawab kasus) —</option>
                                @foreach ($users as $assignee)
                                    <option value="{{ $assignee->id }}" @selected($assignee->id === $capa->owner_id)>{{ $assignee->name }}</option>
                                @endforeach
                            </select>
                            <input type="date" name="due_date" class="sim-form-input" value="{{ $capa->target_close_date?->format('Y-m-d') }}">
                            <x-action action="create" icon="plus" label="Tambah Tindakan" size="sm" />
                        </form>
                    @endif
                @endcan
            </div>
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
