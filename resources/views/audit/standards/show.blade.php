@extends('layouts.simmak')

@section('title', $standard->code . ' — SIMMAK PPKP')
@section('page-title', 'Detail Standar: ' . $standard->code)

@section('content')
    <div class="sim-page-header">
        <div>
            <h2 class="sim-page-title">{{ $standard->code }} — {{ $standard->title }}</h2>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <span class="sim-badge sim-badge-status {{ \App\Support\UiLabel::standardStatusTone($standard->status) }}">
                    {{ \App\Support\UiLabel::standardStatus($standard->status) }}
                </span>
                @if ($standard->sourceDocument)
                    <span class="sim-page-subtitle">Sumber: {{ $standard->sourceDocument->document_number }} — {{ $standard->sourceDocument->title }}</span>
                @endif
            </div>
        </div>

        <div class="flex gap-2">
            @can('audit.standard.manage', $standard)
                <a href="{{ route('audit.standards.import.template') }}" 
                   title="Unduh template Excel untuk impor EP" class="sim-btn sim-btn-download">
                    Template Impor
                </a>
                <a href="{{ route('audit.standards.import', $standard) }}" class="sim-btn sim-btn-upload">
                    Impor EP
                </a>
            @endcan
            @can('audit.standard.update', $standard)
                <a href="{{ route('audit.standards.edit', $standard) }}" class="sim-btn sim-btn-edit sim-btn-sm">
                    Edit
                </a>
            @endcan
            @can('audit.standard.manage')
                @if ($standard->status !== 'active')
                    <x-action action="approve" :href="route('audit.standards.status', $standard)" method="PATCH"
                              :fields="['status' => 'active']" icon="check-circle"
                              label="Aktifkan" size="sm" />
                @else
                    <x-action action="cancel" :href="route('audit.standards.status', $standard)" method="PATCH"
                              :fields="['status' => 'inactive']" icon="x-mark"
                              label="Nonaktifkan" size="sm" />
                @endif
            @endcan
            <a href="{{ route('audit.standards.index') }}" class="sim-btn sim-btn-back">
                Kembali
            </a>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="sim-card lg:col-span-2">
            <div class="sim-card-header">
                <h3 class="sim-card-title">Versi Instrumen</h3>
            </div>
            <div class="sim-card-body space-y-3">
                @forelse ($standard->instrumentVersions as $version)
                    <div class="border border-sim-border-light rounded-lg p-4 hover:bg-sim-hover">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <div class="font-mono text-xs text-sim-muted">{{ $version->version_label }}</div>
                                <div class="font-medium text-sim-ink">{{ \App\Support\UiLabel::instrumentStatus($version->status) }}</div>
                            </div>
                            <span class="sim-badge sim-badge-status {{ \App\Support\UiLabel::standardStatusTone($version->status) }}">
                                {{ \App\Support\UiLabel::instrumentStatus($version->status) }}
                            </span>
                        </div>
                        <div class="text-sm text-sim-muted space-y-1">
                            @if ($version->effective_from)
                                <div class="flex justify-between">
                                    <span class="text-xs text-sim-muted">Berlaku dari</span>
                                    <span>{{ $version->effective_from->format('d M Y') }}</span>
                                </div>
                            @endif
                            @if ($version->effective_until)
                                <div class="flex justify-between">
                                    <span class="text-xs text-sim-muted">Berlaku sampai</span>
                                    <span>{{ $version->effective_until->format('d M Y') }}</span>
                                </div>
                            @endif
                            @if ($version->approved_at)
                                <div class="flex justify-between">
                                    <span class="text-xs text-sim-muted">Disahkan pada</span>
                                    <span>{{ $version->approved_at->format('d M Y H:i') }}</span>
                                </div>
                            @endif
                        </div>

                        <div class="mt-3 flex gap-2">
                            @can('audit.standard.manage', $version)
                                @if ($version->status === 'draft')
                                    <form method="POST" action="{{ route('audit.versions.submit-review', $version) }}" class="inline" onsubmit="return confirm('Yakin ingin mengajukan versi ini untuk tinjauan?');">
                                        @csrf
                                        <x-action action="upload" icon="upload" label="Ajukan Tinjauan" size="sm" />
                                    </form>
                                @elseif ($version->status === 'review')
                                    <form method="POST" action="{{ route('audit.versions.approve', $version) }}" class="inline" onsubmit="return confirm('Yakin ingin menyahkan versi ini?');">
                                        @csrf
                                        <x-action action="approve" icon="check-circle" label="Setujui" size="sm" />
                                    </form>
                                    <form method="POST" action="{{ route('audit.versions.submit-review', $version) }}" class="inline" onsubmit="return confirm('Kembalikan ke draft?');">
                                        @csrf
                                        {{ method_field('PATCH') }}
                                        <input type="hidden" name="_method" value="PATCH">
                                        <x-action action="back" icon="x-mark" label="Kembalikan" size="sm" />
                                    </form>
                                @elseif ($version->status === 'approved')
                                    <form method="POST" action="{{ route('audit.versions.activate', $version) }}" class="inline" onsubmit="return confirm('Yakin ingin mengaktifkan versi ini? Versi aktif lainnya akan diarsipkan.');">
                                        @csrf
                                        <x-action action="approve" icon="check-circle" label="Aktifkan" size="sm" />
                                    </form>
                                @endif
                            @endcan
                        </div>

                        @if ($version->sections->isNotEmpty())
                            <div class="mt-4 border-t border-sim-border-light pt-4">
                                <h4 class="text-xs font-semibold text-sim-muted uppercase mb-3">Seksi &amp; Kriteria</h4>
                                <div class="space-y-3">
                                    @foreach ($version->sections->sortBy('sort_order') as $section)
                                        <div class="border border-sim-border-light rounded-lg p-3">
                                            <div class="flex items-center justify-between mb-2 gap-2">
                                                <div>
                                                    <span class="font-mono text-xs text-sim-muted">{{ $section->code }}</span>
                                                    <span class="font-medium text-sm text-sim-ink ml-1">{{ $section->title }}</span>
                                                </div>
                                                <span class="text-xs text-sim-muted">{{ $section->criteria->count() }} kriteria</span>
                                            </div>
                                            @can('audit.standard.manage')
                                                <details class="mb-2" @if ($errors->any() && old('_form') === 'section-'.$section->id) open @endif>
                                                    <summary class="cursor-pointer text-xs font-medium text-sim-primary hover:underline">
                                                        Edit seksi
                                                    </summary>
                                                    @include('audit.standards._section_form', [
                                                        'action' => route('audit.sections.update', $section),
                                                        'method' => 'PUT',
                                                        'section' => $section,
                                                        'submitLabel' => 'Simpan Seksi',
                                                        'formKey' => 'section-'.$section->id,
                                                    ])
                                                </details>
                                            @endcan
                                            @if ($section->criteria->isNotEmpty())
                                                <div class="mt-2 overflow-x-auto">
                                                    <table class="sim-table text-xs">
                                                        <thead class="bg-slate-50">
                                                            <tr>
                                                                <th class="px-2 py-1 w-14">EP</th>
                                                                <th class="px-2 py-1">Elemen Penilaian</th>
                                                                <th class="px-2 py-1">Kelengkapan Bukti</th>
                                                                <th class="px-2 py-1 w-16 text-center">SKOR Maks</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($section->criteria->sortBy('sort_order') as $criterion)
                                                                <tr class="border-t border-sim-border-light align-top">
                                                                    <td class="px-2 py-1 font-mono text-sim-muted">{{ $criterion->code }}</td>
                                                                    <td class="px-2 py-1 text-sim-ink">
                                                                        {{ $criterion->title }}
                                                                        @can('audit.standard.manage')
                                                                            <details class="mt-1" @if ($errors->any() && old('_form') === 'criterion-'.$criterion->id) open @endif>
                                                                                <summary class="cursor-pointer text-xs font-medium text-sim-primary hover:underline">
                                                                                    Edit EP
                                                                                </summary>
                                                                                @include('audit.standards._criterion_form', [
                                                                                    'action' => route('audit.criteria.update', $criterion),
                                                                                    'method' => 'PUT',
                                                                                    'criterion' => $criterion,
                                                                                    'submitLabel' => 'Simpan EP',
                                                                                    'formKey' => 'criterion-'.$criterion->id,
                                                                                ])
                                                                            </details>
                                                                        @endcan
                                                                    </td>
                                                                    <td class="px-2 py-1 text-sim-muted">
                                                                        @if ($criterion->evidence_guidance)
                                                                            <ul class="list-disc pl-4 space-y-0.5">
                                                                                @foreach (preg_split('/\r\n|\r|\n/', $criterion->evidence_guidance) as $line)
                                                                                    @if (trim($line) !== '')
                                                                                        <li>{{ trim($line) }}</li>
                                                                                    @endif
                                                                                @endforeach
                                                                            </ul>
                                                                        @else
                                                                            —
                                                                        @endif
                                                                    </td>
                                                                    <td class="px-2 py-1 text-center text-sim-muted">{{ $criterion->max_score ?: 10 }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif
                                            @can('audit.standard.manage')
                                                <details class="mt-2" @if ($errors->any() && old('_form') === 'criterion-new-'.$section->id) open @endif>
                                                    <summary class="cursor-pointer text-xs font-medium text-sim-primary hover:underline">
                                                        + Tambah Elemen Penilaian (EP)
                                                    </summary>
                                                    @include('audit.standards._criterion_form', [
                                                        'action' => route('audit.sections.criteria.store', $section),
                                                        'method' => 'POST',
                                                        'criterion' => null,
                                                        'submitLabel' => 'Simpan EP',
                                                        'formKey' => 'criterion-new-'.$section->id,
                                                    ])
                                                </details>
                                            @endcan
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        @can('audit.standard.manage')
                            <details class="mt-3" @if ($errors->any() && old('_form') === 'section-new-'.$version->id) open @endif>
                                <summary class="cursor-pointer text-xs font-medium text-sim-primary hover:underline">+ Tambah Seksi</summary>
                                @include('audit.standards._section_form', [
                                    'action' => route('audit.versions.sections.store', $version),
                                    'method' => 'POST',
                                    'section' => null,
                                    'submitLabel' => 'Simpan Seksi',
                                    'formKey' => 'section-new-'.$version->id,
                                ])
                            </details>
                        @endcan
                    </div>
                @empty
                    <div class="py-6 text-center">
                        <p class="text-sm font-medium text-sim-ink">Belum ada versi instrumen untuk standar ini.</p>
                        <p class="mx-auto mt-1 max-w-md text-xs leading-relaxed text-sim-muted">
                            Versi instrumen adalah "salinan" dari daftar EP yang dipakai dalam eksekusi audit.
                            Buat versi pertama, lalu isi EP secara manual atau impor dari berkas Excel.
                        </p>
                        <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
                            @can('audit.standard.manage', $standard)
                                <a href="{{ route('audit.standards.versions.create', $standard) }}" class="sim-btn sim-btn-create w-full text-center">
                                    Buat Versi Pertama
                                </a>
                                <a href="{{ route('audit.standards.import', $standard) }}" class="sim-btn sim-btn-upload">
                                    Impor dari Excel
                                </a>
                            @endcan
                        </div>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="sim-card lg:col-span-1">
            <div class="sim-card-header">
                <h3 class="sim-card-title">Aksi</h3>
            </div>
            <div class="sim-card-body space-y-3">
                @can('audit.standard.manage', $standard)
                    <a href="{{ route('audit.standards.versions.create', $standard) }}" class="sim-btn sim-btn-create">
                        Tambah Versi Instrumen
                    </a>
                @endcan
            </div>
        </div>
    </div>
@endsection
