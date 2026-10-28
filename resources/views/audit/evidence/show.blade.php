@extends('layouts.simmak')

@section('title', 'Detail Bukti Audit')

@section('content')
<div class="max-w-4xl mx-auto space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold text-gray-900">Detail Bukti Audit</h1>
        <x-action action="back" href="{{ route('audit.evidence.index') }}" icon="chevron-left" label="Kembali" size="sm" />
    </div>

    <div class="bg-white rounded-lg shadow p-6 space-y-3">
        <div>
            <span class="text-sm text-gray-500">Nama Berkas</span>
            <p class="font-medium">{{ $evidence->original_filename }}</p>
        </div>
        <div>
            <span class="text-sm text-gray-500">Tipe MIME</span>
            <p>{{ $evidence->mime_type }}</p>
        </div>
        <div>
            <span class="text-sm text-gray-500">Ukuran</span>
            <p>{{ number_format($evidence->size_bytes / 1024, 2) }} KB</p>
        </div>
        <div>
            <span class="text-sm text-gray-500">Jenis Bukti</span>
            <p>{{ $evidence->evidence_type ?? '-' }}</p>
        </div>
        <div>
            <span class="text-sm text-gray-500">Klasifikasi Akses</span>
            <p>{{ $evidence->access_classification ?? '-' }}</p>
        </div>
        <div>
            <span class="text-sm text-gray-500">Deskripsi</span>
            <p>{{ $evidence->description ?? '-' }}</p>
        </div>
        <div>
            <span class="text-sm text-gray-500">Diunggah oleh</span>
            <p>{{ $evidence->uploader->name ?? '-' }}</p>
        </div>
        <div>
            <span class="text-sm text-gray-500">Checksum (SHA-256)</span>
            <p class="font-mono text-xs break-all">{{ $evidence->checksum }}</p>
        </div>
    </div>

    @if (! $fileAvailable)
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            <div class="flex items-start gap-2">
                <span class="inline-flex">@include('partials.icons.alert-triangle')</span>
                <div>
                    <p class="font-semibold">Berkas tidak ditemukan di penyimpanan</p>
                    <p class="mt-1">Catatan bukti masih ada di basis data, tetapi berkas fisiknya tidak tersedia pada disk <code class="font-mono">{{ $evidence->storage_disk }}</code> ({{ $evidence->storage_path }}). Unggah ulang berkas agar bukti ini dapat diunduh dan diaudit kembali.</p>
                </div>
            </div>
        </div>
    @endif

    <div class="flex gap-2">
        @can('download', $evidence)
            @if ($fileAvailable)
                <x-action action="download" href="{{ route('audit.evidence.download', $evidence) }}" icon="download" label="Unduh" />
            @else
                <span class="sim-btn sim-btn-download cursor-not-allowed opacity-50" title="Berkas tidak ditemukan di penyimpanan">
                    <span class="inline-flex">@include('partials.icons.download')</span>
                    <span>Unduh</span>
                </span>
            @endif
        @endcan
        @can('delete', $evidence)
            <x-action action="delete" href="{{ route('audit.evidence.destroy', $evidence) }}" icon="trash" label="Hapus" method="DELETE" confirm="Hapus bukti ini?" />
        @endcan
    </div>
</div>
@endsection