@extends('layouts.simmak')

@section('title', 'Checklist Audit — SIMMAK PPKP')
@section('page-title', 'Pelaksanaan Checklist Audit')

@section('content')
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">{{ $execution->execution_code }} — {{ $execution->title }}</h2>
            <p class="text-sm text-slate-500">
                Klinik: {{ $execution->clinic?->name ?? '—' }} ·
                Instrumen: {{ $execution->instrumentVersion?->version_label ?? '—' }}
            </p>
        </div>

        <div class="flex gap-2">
            @can('audit.execution.execute', $execution)
                @if ($execution->status === 'preparation')
                    <form method="POST" action="{{ route('audit.executions.start', $execution) }}" class="inline" onsubmit="return confirm('Mulai eksekusi audit?');">
                        @csrf
                        <button type="submit" class="sim-btn sim-btn-primary sim-btn-sm">
                            Mulai Audit
                        </button>
                    </form>
                @elseif ($execution->status === 'in_progress')
                    @if ($errors->has('completeness'))
                        <div class="sim-alert sim-alert-error mb-2">
                            <div class="flex items-start gap-2">
                                <svg class="h-4 w-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.93 19h12.14c1.54 0 2.5-1.67 1.73-3L13.73 5c-.77-1.33-2.69-1.33-3.46 0L4.2 16c-.77 1.33.19 3 1.73 3z"/></svg>
                                <span>{{ $errors->first('completeness') }}</span>
                            </div>
                        </div>
                    @endif
                    <form method="POST" action="{{ route('audit.executions.submit', $execution) }}" class="inline" onsubmit="return confirm('Serahkan hasil audit untuk tinjauan?');">
                        @csrf
                        <button type="submit" class="rounded-lg bg-blue-700 px-3 py-1.5 text-sm font-semibold text-white hover:bg-blue-800">
                            Serahkan untuk Tinjauan
                        </button>
                    </form>
                @elseif ($execution->status === 'submitted_for_review')
                    <form method="POST" action="{{ route('audit.executions.finalize', $execution) }}" class="inline" onsubmit="return confirm('Finalisasi hasil audit?');">
                        @csrf
                        <button type="submit" class="rounded-lg bg-emerald-700 px-3 py-1.5 text-sm font-semibold text-white hover:bg-emerald-800">
                            Finalisasi
                        </button>
                    </form>
                @endif
            @endcan
            <a href="{{ route('audit.assignments.index') }}" class="sim-btn sim-btn-secondary sim-btn-sm">
                Kembali
            </a>
        </div>
    </div>

    <!-- Completion summary -->
    <div class="simmak-card mb-6">
        <div class="simmak-card-body p-4">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-semibold text-slate-800">Progres Pemeriksaan</h3>
                    <p class="text-sm text-slate-500">{{ $stats['completed'] }} dari {{ $stats['total'] }} kriteria telah dinilai</p>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold text-teal-700">{{ $stats['percentage'] }}%</div>
                    <div class="text-xs text-slate-500">Tingkat Kelengkapan</div>
                </div>
            </div>
            <div class="w-full bg-slate-200 rounded-full h-2">
                <div class="bg-teal-700 h-2 rounded-full" style="width: {{ $stats['percentage'] }}%"></div>
            </div>
        </div>
    </div>

    @if ($execution->status === 'in_progress' && $incompleteCriteria->isNotEmpty())
        <div class="simmak-card mb-6 border-amber-200">
            <div class="simmak-card-body p-4">
                <div class="flex items-start gap-2 mb-3">
                    <svg class="h-5 w-5 flex-shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.93 19h12.14c1.54 0 2.5-1.67 1.73-3L13.73 5c-.77-1.33-2.69-1.33-3.46 0L4.2 16c-.77 1.33.19 3 1.73 3z"/></svg>
                    <div>
                        <h3 class="font-semibold text-amber-800">{{ $incompleteCriteria->count() }} Kriteria Belum Dinilai</h3>
                        <p class="text-sm text-amber-700">Lengkapi seluruh kriteria berikut sebelum menyerahkan eksekusi untuk tinjauan. Ini adalah indikator kelengkapan pengisian, bukan skor kepatuhan.</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach ($incompleteCriteria as $criterion)
                        <a href="#criterion-{{ $criterion->id }}" class="inline-flex items-center gap-1 rounded-md bg-amber-50 border border-amber-200 px-2 py-1 text-xs font-medium text-amber-800 hover:bg-amber-100">
                            <span class="text-sim-mono">{{ $criterion->code }}</span>
                            <span>{{ \Illuminate\Support\Str::limit($criterion->title, 40) }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    @if ($sections->isNotEmpty())
        <div class="space-y-6">
            @foreach ($sections as $section)
                <div class="simmak-card">
                    <div class="simmak-card-header flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold text-slate-800">{{ $section->code }} — {{ $section->title }}</h3>
                            @if ($section->description)
                                <p class="text-xs text-slate-500 mt-1">{{ $section->description }}</p>
                            @endif
                        </div>
                        @php $sectionResponses = $execution->responses->where('criterion.section_id', $section->id); @endphp
                        @if ($sectionResponses->count() > 0)
                            <span class="simmak-badge bg-blue-100 text-blue-800 text-xs">
                                {{ $sectionResponses->whereNotNull('response_value')->count() }}/{{ $sectionResponses->count() }} selesai
                            </span>
                        @endif
                    </div>
                    <div class="simmak-card-body space-y-3">
                        @foreach ($section->criteria as $criterion)
                            @php $response = $execution->responses->firstWhere('standard_criterion_id', $criterion->id); @endphp
                            <div id="criterion-{{ $criterion->id }}" class="border border-slate-100 rounded-lg p-4 {{ $response && $response->response_value === 'non_compliant' ? 'border-amber-200 bg-amber-50' : '' }}">
                                <div class="flex items-start justify-between mb-2">
                                    <div>
                                        <div class="font-mono text-xs text-slate-500">{{ $criterion->code }}</div>
                                        <div class="font-medium text-slate-800">{{ $criterion->title }}</div>
                                        @if ($criterion->requirement_text)
                                            <p class="text-xs text-slate-600 mt-1">{{ \Illuminate\Support\Str::limit($criterion->requirement_text, 120) }}</p>
                                        @endif
                                    </div>
                                    @if ($response)
                                        <span class="simmak-badge {{ $response->response_value === 'compliant' ? 'bg-emerald-100 text-emerald-800' : ($response->response_value === 'non_compliant' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700') }}">
                                            {{ ucfirst(str_replace('_', ' ', $response->response_value)) }}
                                        </span>
                                    @else
                                        <span class="simmak-badge bg-slate-100 text-slate-400 text-xs">Belum Dinilai</span>
                                    @endif
                                </div>

                                <form method="POST" action="{{ route('audit.executions.response', $execution) }}" class="space-y-3">
                                    @csrf
                                    <input type="hidden" name="standard_criterion_id" value="{{ $criterion->id }}">

                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div class="sim-form-group">
                                            <label class="sim-form-label">Hasil Penilaian<span class="sim-form-label-required"></span></label>
                                            <select name="response_value" class="sim-form-select {{ $errors->has('response_value') ? 'border-sim-danger' : '' }}" required>
                                                <option value="">— Pilih —</option>
                                                <option value="compliant" {{ old('response_value', $response?->response_value) === 'compliant' ? 'selected' : '' }}>Sesuai</option>
                                                <option value="non_compliant" {{ old('response_value', $response?->response_value) === 'non_compliant' ? 'selected' : '' }}>Tidak Sesuai</option>
                                                <option value="partial" {{ old('response_value', $response?->response_value) === 'partial' ? 'selected' : '' }}>Sesuai Sebagian</option>
                                                <option value="not_applicable" {{ old('response_value', $response?->response_value) === 'not_applicable' ? 'selected' : '' }}>Tidak Berlaku</option>
                                                <option value="not_assessable" {{ old('response_value', $response?->response_value) === 'not_assessable' ? 'selected' : '' }}>Tidak Dapat Dinilai</option>
                                            </select>
                                            @if ($errors->has('response_value'))
                                                <p class="sim-form-error">{{ $errors->first('response_value') }}</p>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="sim-form-group">
                                        <label class="sim-form-label">Catatan Pemeriksaan</label>
                                        <textarea name="assessment_notes" rows="2" class="sim-form-textarea {{ $errors->has('assessment_notes') ? 'border-sim-danger' : '' }}" placeholder="Catatan hasil pemeriksaan">{{ old('assessment_notes', $response?->assessment_notes) }}</textarea>
                                        @if ($errors->has('assessment_notes'))
                                            <p class="sim-form-error">{{ $errors->first('assessment_notes') }}</p>
                                        @endif
                                    </div>

                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div class="sim-form-group">
                                            <label class="sim-form-label">Metode Pemeriksaan</label>
                                            <input type="text" name="method_used" value="{{ old('method_used', $response?->method_used) }}" placeholder="Contoh: Wawancara, Observasi" class="sim-form-input">
                                        </div>
                                        <div class="sim-form-group">
                                            <label class="sim-form-label">Sampel</label>
                                            <input type="text" name="sample_description" value="{{ old('sample_description', $response?->sample_description) }}" placeholder="Contoh: 5 rekam medis" class="sim-form-input">
                                        </div>
                                    </div>

                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div class="sim-form-group">
                                            @if ($response?->response_value === 'not_applicable' || old('response_value') === 'not_applicable')
                                                <label class="sim-form-label">Alasan Tidak Berlaku</label>
                                                <textarea name="not_applicable_reason" rows="2" class="sim-form-textarea">{{ old('not_applicable_reason', $response?->not_applicable_reason) }}</textarea>
                                            @endif
                                        </div>
                                        <div class="sim-form-group">
                                            @if ($response?->response_value === 'not_assessable' || old('response_value') === 'not_assessable')
                                                <label class="sim-form-label">Alasan Tidak Dapat Dinilai</label>
                                                <textarea name="not_assessable_reason" rows="2" class="sim-form-textarea">{{ old('not_assessable_reason', $response?->not_assessable_reason) }}</textarea>
                                            @endif
                                        </div>
                                    </div>

                                    <button type="submit" class="sim-btn sim-btn-primary w-auto">
                                        Simpan Jawaban
                                    </button>
                                </form>

                                @if ($response && $response->assessed_at)
                                    <div class="mt-2 flex items-center gap-2 text-xs text-slate-500">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span>Diperbarui {{ $response->assessed_at->diffForHumans() }} oleh {{ $response->assessedBy?->name ?? '—' }}</span>
                                    </div>
                                @endif

                                @if ($response && $response->revisions->isNotEmpty())
                                    <details class="mt-2 group">
                                        <summary class="cursor-pointer text-xs font-medium text-slate-500 hover:text-slate-700 inline-flex items-center gap-1 select-none">
                                            <svg class="h-3 w-3 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                            Riwayat perubahan ({{ $response->revisions->count() }})
                                        </summary>
                                        <div class="mt-2 space-y-2 border-l-2 border-slate-100 pl-3">
                                            @foreach ($response->revisions as $revision)
                                                <div class="text-xs text-slate-500">
                                                    <div class="flex items-center gap-2">
                                                        <span class="font-medium text-slate-600">{{ ucfirst(str_replace('_', ' ', $revision->response_value ?? '—')) }}</span>
                                                        <span>·</span>
                                                        <span>{{ $revision->assessed_at?->format('d M Y H:i') ?? '—' }}</span>
                                                    </div>
                                                    <div class="mt-0.5">oleh {{ $revision->assessedBy?->name ?? '—' }}</div>
                                                    @if ($revision->assessment_notes)
                                                        <div class="mt-0.5 text-slate-400 italic">"{{ \Illuminate\Support\Str::limit($revision->assessment_notes, 80) }}"</div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </details>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @else
            <div class="simmak-card">
                <div class="simmak-card-body text-center py-12">
                    <p class="text-sm text-slate-500">Tidak ada kriteria standar untuk versi instrumen ini.</p>
                </div>
            </div>
        @endif
@endsection
