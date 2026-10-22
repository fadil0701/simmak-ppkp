@extends('layouts.simmak')

@section('title', $standard->code . ' — SIMMAK PPKP')
@section('page-title', 'Detail Standar: ' . $standard->code)

@section('content')
    <div class="sim-page-header">
        <div>
            <h2 class="sim-page-title">{{ $standard->code }} — {{ $standard->title }}</h2>
            @if ($standard->sourceDocument)
                <p class="sim-page-subtitle">Sumber: {{ $standard->sourceDocument->document_number }} — {{ $standard->sourceDocument->title }}</p>
            @endif
        </div>

        <div class="flex gap-2">
            @can('audit.standard.manage', $standard)
                <a href="{{ route('audit.standards.import', $standard) }}" class="sim-btn sim-btn-secondary sim-btn-sm">
                    Impor EP
                </a>
            @endcan
            @can('audit.standard.update', $standard)
                <a href="{{ route('audit.standards.edit', $standard) }}" class="sim-btn sim-btn-primary sim-btn-sm">
                    Sunting
                </a>
            @endcan
            <a href="{{ route('audit.standards.index') }}" class="sim-btn sim-btn-outline">
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
                                <div class="font-medium text-sim-ink">{{ \App\Support\UiLabel::auditStatus($version->status) }}</div>
                            </div>
                            <span class="sim-badge sim-badge-status {{ $version->status === 'active' ? 'border-sim-success bg-sim-success-bg text-sim-success' : 'border-sim-neutral bg-sim-neutral-bg text-sim-neutral' }}">
                                {{ ucfirst($version->status) }}
                            </span>
                            @if ($version->isLocked())
                                <span class="sim-badge sim-badge-status border-sim-warning bg-sim-warning-bg text-sim-warning inline-flex items-center gap-1" title="Versi sudah dipakai eksekusi audit — dikunci, tidak dapat diubah">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                    Dikunci
                                </span>
                            @endif
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
                                        <x-action icon="upload" label="Ajukan Tinjauan" size="sm" />
                                    </form>
                                @elseif ($version->status === 'review')
                                    <form method="POST" action="{{ route('audit.versions.approve', $version) }}" class="inline" onsubmit="return confirm('Yakin ingin menyahkan versi ini?');">
                                        @csrf
                                        <x-action icon="check-circle" label="Setujui" size="sm" />
                                    </form>
                                    <form method="POST" action="{{ route('audit.versions.submit-review', $version) }}" class="inline" onsubmit="return confirm('Kembalikan ke draft?');">
                                        @csrf
                                        {{ method_field('PATCH') }}
                                        <input type="hidden" name="_method" value="PATCH">
                                        <x-action icon="x-mark" label="Kembalikan" variant="secondary" size="sm" />
                                    </form>
                                @elseif ($version->status === 'approved')
                                    <form method="POST" action="{{ route('audit.versions.activate', $version) }}" class="inline" onsubmit="return confirm('Yakin ingin mengaktifkan versi ini? Versi aktif lainnya akan diarsipkan.');">
                                        @csrf
                                        <x-action icon="check-circle" label="Aktifkan" size="sm" />
                                    </form>
                                @endif
                            @endcan
                        </div>

                        @php $isLocked = $version->isLocked(); @endphp
                        @if ($version->sections->isNotEmpty())
                            <div class="mt-4 border-t border-sim-border-light pt-4">
                                <h4 class="text-xs font-semibold text-sim-muted uppercase mb-3">Seksi &amp; Kriteria</h4>
                                <div class="space-y-3">
                                    @foreach ($version->sections->sortBy('sort_order') as $section)
                                        <div class="border border-sim-border-light rounded-lg p-3">
                                            <div class="flex items-center justify-between mb-2">
                                                <div>
                                                    <span class="font-mono text-xs text-sim-muted">{{ $section->code }}</span>
                                                    <span class="font-medium text-sm text-sim-ink ml-1">{{ $section->title }}</span>
                                                </div>
                                                <span class="text-xs text-sim-muted">{{ $section->criteria->count() }} kriteria</span>
                                            </div>
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
                                                                    <td class="px-2 py-1 text-sim-ink">{{ $criterion->title }}</td>
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
                                                @if (! $isLocked && $version->status === 'draft')
                                                    <details class="mt-2">
                                                        <summary class="cursor-pointer text-xs text-sim-primary hover:underline">+ Tambah Kriteria</summary>
                                                        <form method="POST" action="{{ route('audit.sections.criteria.store', $section) }}" class="mt-2 space-y-2">
                                                            @csrf
                                                            <input type="text" name="code" required placeholder="Kode" class="sim-form-input sim-btn-sm">
                                                            <input type="text" name="title" required placeholder="Judul kriteria" class="sim-form-input sim-btn-sm">
                                                            <textarea name="requirement_text" rows="2" placeholder="Teks persyaratan" class="sim-form-textarea sim-btn-sm"></textarea>
                                                            <textarea name="evidence_guidance" rows="2" placeholder="Kelengkapan bukti (satu per baris)" class="sim-form-textarea sim-btn-sm"></textarea>
                                                            <input type="number" name="max_score" min="0" max="100" value="10" placeholder="SKOR maksimal" class="sim-form-input sim-btn-sm">
                                                            <x-action icon="check-circle" label="Simpan Kriteria" size="sm" />
                                                        </form>
                                                    </details>
                                                @endif
                                            @endcan
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        @can('audit.standard.manage')
                            @if (! $isLocked && $version->status === 'draft')
                                <details class="mt-3">
                                    <summary class="cursor-pointer text-xs font-medium text-sim-primary hover:underline">+ Tambah Seksi</summary>
                                    <form method="POST" action="{{ route('audit.versions.sections.store', $version) }}" class="mt-2 space-y-2">
                                        @csrf
                                        <input type="text" name="code" required placeholder="Kode seksi" class="sim-form-input sim-btn-sm">
                                        <input type="text" name="title" required placeholder="Judul seksi" class="sim-form-input sim-btn-sm">
                                        <textarea name="description" rows="2" placeholder="Deskripsi (opsional)" class="sim-form-textarea sim-btn-sm"></textarea>
                                        <x-action icon="check-circle" label="Simpan Seksi" size="sm" />
                                    </form>
                                </details>
                            @endif
                        @endcan
                        @if ($isLocked)
                            <p class="mt-3 text-xs text-sim-warning flex items-center gap-1">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                Versi dikunci — sudah dipakai eksekusi audit. Buat versi baru untuk mengubah isi.
                            </p>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-sim-muted">Belum ada versi instrumen untuk standar ini.</p>
                @endforelse
            </div>
        </div>

        <div class="sim-card lg:col-span-1">
            <div class="sim-card-header">
                <h3 class="sim-card-title">Aksi</h3>
            </div>
            <div class="sim-card-body space-y-3">
                @can('audit.standard.manage', $standard)
                    <a href="{{ route('audit.standards.versions.create', $standard) }}" class="sim-btn sim-btn-primary w-full text-center">
                        Tambah Versi Instrumen
                    </a>
                @endcan
            </div>
        </div>
    </div>
@endsection
