@extends('layouts.simmak')

@section('title', 'Form Pelaksanaan Audit — SIMMAK PPKP')
@section('page-title', 'Form Pelaksanaan Audit')

@section('content')
    <div class="mb-4 flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">{{ $execution->execution_code }} — {{ $execution->title }}</h2>
                <p class="text-sm text-slate-500">
                    Klinik: {{ $execution->clinic?->name ?? '—' }} ·
                    Instrumen: {{ $execution->instrumentVersion?->version_label ?? '—' }} ·
                    Periode: {{ $execution->started_at?->format('d M Y') ?? '—' }}
                </p>
            </div>
            <a href="{{ route('audit.executions.index') }}" class="sim-btn sim-btn-outline sim-btn-sm">Kembali</a>
        </div>

        @if (session('status'))
            <div class="sim-alert sim-alert-success">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="sim-alert sim-alert-error">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="sim-alert sim-alert-error">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    {{-- Ringkasan: petugas cukup mengisi kolom SKOR, FAKTA DAN ANALISIS, REKOMENDASI --}}
    <div class="simmak-card mb-4">
        <div class="simmak-card-body p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="font-semibold text-slate-800">Progres Pengisian Tabel</h3>
                    <p class="text-sm text-slate-500">
                        {{ $stats['completed'] }} dari {{ $stats['total'] }} EP telah dinilai
                        ({{ $stats['percentage'] }}%). Angka ini hanya indikator kelengkapan pengisian, bukan persentase kepatuhan.
                    </p>
                </div>
                <div class="w-full sm:w-64">
                    <div class="w-full bg-slate-200 rounded-full h-2">
                        <div class="bg-teal-700 h-2 rounded-full" style="width: {{ $stats['percentage'] }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @can('execute', $execution)
        @if ($execution->status === 'preparation')
            <div class="simmak-card mb-4">
                <div class="simmak-card-body flex items-center justify-between">
                    <p class="text-sm text-slate-600">Eksekusi belum dimulai. Mulai audit sebelum mengisi tabel.</p>
                    <form method="POST" action="{{ route('audit.executions.start', $execution) }}" onsubmit="return confirm('Mulai eksekusi audit?');">
                        @csrf
                        <x-action icon="play-circle" label="Mulai Audit" size="sm" />
                    </form>
                </div>
            </div>
        @elseif ($execution->status === 'in_progress')
            <form method="POST" action="{{ route('audit.executions.table', $execution) }}" id="form-tabel-audit">
                @csrf
                @foreach ($sections as $section)
                    <div class="simmak-card mb-4">
                        <div class="simmak-card-header flex items-center justify-between">
                            <div>
                                <h3 class="font-semibold text-slate-800">{{ $section->code }} — {{ $section->title }}</h3>
                                @if ($section->description)
                                    <p class="text-xs text-slate-500 mt-1">{{ $section->description }}</p>
                                @endif
                            </div>
                            @php $sectionResponses = $execution->responses->where('criterion.section_id', $section->id); @endphp
                            <span class="simmak-badge bg-blue-100 text-blue-800 text-xs">
                                {{ $sectionResponses->whereNotNull('response_value')->count() }}/{{ $section->criteria->count() }} terisi
                            </span>
                        </div>
                        <div class="simmak-card-body p-0 overflow-x-auto">
                            <table class="sim-table text-sm">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="w-16 px-3 py-2">EP</th>
                                        <th class="px-3 py-2 min-w-[280px]">Elemen Penilaian</th>
                                        <th class="px-3 py-2 min-w-[220px]">Kelengkapan Bukti</th>
                                        <th class="px-3 py-2 w-24 text-center">SKOR</th>
                                        <th class="px-3 py-2 w-20 text-center">SKOR Maks</th>
                                        <th class="px-3 py-2 min-w-[240px]">Fakta dan Analisis</th>
                                        <th class="px-3 py-2 min-w-[220px]">Rekomendasi</th>
                                        <th class="px-3 py-2 w-32 text-center">Temuan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($section->criteria as $criterion)
                                        @php $response = $execution->responses->firstWhere('standard_criterion_id', $criterion->id); @endphp
                                        @php $maxScore = $criterion->max_score ?: 10; @endphp
                                        <tr class="border-t border-slate-100 align-top">
                                            <td class="px-3 py-2 font-mono text-xs text-slate-500">{{ $criterion->code }}</td>
                                            <td class="px-3 py-2 text-slate-800">{{ $criterion->title }}</td>
                                            <td class="px-3 py-2 text-xs text-slate-600">
                                                @if ($criterion->evidence_guidance)
                                                    <ul class="list-disc pl-4 space-y-0.5">
                                                        @foreach (preg_split('/\r\n|\r|\n/', $criterion->evidence_guidance) as $line)
                                                            @if (trim($line) !== '')
                                                                <li>{{ trim($line) }}</li>
                                                            @endif
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <span class="text-slate-400">—</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <input type="number" min="0" max="{{ $maxScore }}"
                                                       name="rows[{{ $criterion->id }}][score]"
                                                       value="{{ old("rows.{$criterion->id}.score", $response?->score) }}"
                                                       class="sim-form-input text-center px-2 py-1 text-sm">
                                                <input type="hidden" name="rows[{{ $criterion->id }}][standard_criterion_id]" value="{{ $criterion->id }}">
                                            </td>
                                            <td class="px-3 py-2 text-center text-slate-500">{{ $maxScore }}</td>
                                            <td class="px-3 py-2">
                                                <textarea name="rows[{{ $criterion->id }}][facts_analysis]" rows="2"
                                                          class="sim-form-textarea text-sm"
                                                          placeholder="Fakta dan analisis">{{ old("rows.{$criterion->id}.facts_analysis", $response?->facts_analysis) }}</textarea>
                                            </td>
                                            <td class="px-3 py-2">
                                                <textarea name="rows[{{ $criterion->id }}][recommendation]" rows="2"
                                                          class="sim-form-textarea text-sm"
                                                          placeholder="Rekomendasi">{{ old("rows.{$criterion->id}.recommendation", $response?->recommendation) }}</textarea>
                                            </td>
                                            <td class="px-3 py-2 text-center space-y-1">
                                                <select name="severity[{{ $criterion->id }}]" class="sim-form-input text-xs px-2 py-1">
                                                    <option value="minor">Ringan</option>
                                                    <option value="major">Major</option>
                                                    <option value="critical">Kritikal</option>
                                                </select>
                                                <button type="submit" name="create_finding[{{ $criterion->id }}]" value="1"
                                                        class="sim-btn sim-btn-secondary sim-btn-sm w-full"
                                                        onclick="return confirm('Simpan baris ini dan buat temuan audit?')">Catat Temuan</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach

                <div class="sticky bottom-0 bg-white border-t border-slate-200 py-3 flex items-center justify-end gap-2 shadow-sm">
                    <span class="text-xs text-slate-500 mr-auto">Isi kolom SKOR, FAKTA DAN ANALISIS, lalu REKOMENDASI bila diperlukan.</span>
                    <x-action icon="check-circle" label="Simpan Tabel" />
                </div>
            </form>

        @elseif ($execution->status === 'submitted_for_review')
            <div class="simmak-card mb-4">
                <div class="simmak-card-body flex items-center justify-between">
                    <p class="text-sm text-slate-600">Eksekusi sedang dalam tinjauan. Tabel bersifat baca.</p>
                    @can('finalize', $execution)
                        <form method="POST" action="{{ route('audit.executions.finalize', $execution) }}" onsubmit="return confirm('Finalisasi hasil audit?');">
                            @csrf
                            <x-action icon="check-circle" label="Finalisasi" size="sm" />
                        </form>
                    @endcan
                </div>
            </div>
        @endif
    @endcan

    @if ($sections->isNotEmpty())
        @if (! auth()->user()->can('execute', $execution) || $execution->status !== 'in_progress')
            <div class="space-y-4">
                @foreach ($sections as $section)
                    <div class="simmak-card">
                        <div class="simmak-card-header">
                            <h3 class="font-semibold text-slate-800">{{ $section->code }} — {{ $section->title }}</h3>
                        </div>
                        <div class="simmak-card-body p-0 overflow-x-auto">
                            <table class="sim-table text-sm">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="w-16 px-3 py-2">EP</th>
                                        <th class="px-3 py-2 min-w-[280px]">Elemen Penilaian</th>
                                        <th class="px-3 py-2 w-24 text-center">SKOR</th>
                                        <th class="px-3 py-2 w-20 text-center">SKOR Maks</th>
                                        <th class="px-3 py-2 min-w-[240px]">Fakta dan Analisis</th>
                                        <th class="px-3 py-2 min-w-[220px]">Rekomendasi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($section->criteria as $criterion)
                                        @php $response = $execution->responses->firstWhere('standard_criterion_id', $criterion->id); @endphp
                                        <tr class="border-t border-slate-100 align-top">
                                            <td class="px-3 py-2 font-mono text-xs text-slate-500">{{ $criterion->code }}</td>
                                            <td class="px-3 py-2 text-slate-800">{{ $criterion->title }}</td>
                                            <td class="px-3 py-2 text-center font-medium">{{ $response?->score ?? '—' }}</td>
                                            <td class="px-3 py-2 text-center text-slate-500">{{ $criterion->max_score ?: 10 }}</td>
                                            <td class="px-3 py-2 text-slate-700">{{ $response?->facts_analysis ?? '—' }}</td>
                                            <td class="px-3 py-2 text-slate-700">{{ $response?->recommendation ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @else
        <div class="simmak-card">
            <div class="simmak-card-body text-center py-12">
                <p class="text-sm text-slate-500">Versi instrumen ini belum memiliki EP. Tambahkan EP pada Standar &amp; Instrumen terlebih dahulu.</p>
            </div>
        </div>
    @endif
@endsection
