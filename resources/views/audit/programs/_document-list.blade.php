@php
    $documents = $documents ?? collect();
    $heading = $heading ?? 'Dokumen Acuan Program Audit';
@endphp

<div class="simmak-card {{ $class ?? '' }}">
    <div class="simmak-card-header flex flex-wrap items-center justify-between gap-2">
        <div>
            <h3 class="text-sm font-semibold text-slate-700">{{ $heading }}</h3>
            <p class="text-xs text-slate-500">Berkas milik program audit terkait. Tidak disalin per auditor.</p>
        </div>
        <span class="text-xs text-slate-500">{{ $documents->count() }} dokumen</span>
    </div>
    <div class="simmak-card-body space-y-2">
        @forelse ($documents as $document)
            <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-slate-100 p-3">
                <div class="min-w-0">
                    <div class="truncate text-sm font-medium text-slate-800">{{ $document->title }}</div>
                    <div class="text-xs text-slate-500">
                        {{ $document->original_filename }}
                        @if ($document->description)
                            · {{ $document->description }}
                        @endif
                        · {{ $document->uploaded_at?->format('d M Y H:i') ?? '—' }}
                    </div>
                </div>
                <div class="flex flex-shrink-0 gap-1">
                    @can('view', $document)
                        <x-action action="detail" href="{{ route('audit.program-documents.show', $document) }}" icon="file-text" label="Lihat" size="sm" />
                    @endcan
                    @can('download', $document)
                        <x-action action="download" href="{{ route('audit.program-documents.download', $document) }}" icon="download" label="Unduh" size="sm" />
                    @endcan
                </div>
            </div>
        @empty
            <p class="text-sm text-slate-500">Belum ada dokumen acuan pada program audit ini.</p>
        @endforelse
    </div>
</div>
