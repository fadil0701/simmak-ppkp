@extends('layouts.simmak')

@section('title', 'Detail Bukti Audit')

@section('content')
<div class="max-w-4xl mx-auto space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold text-gray-900">Detail Bukti Audit</h1>
        <x-action href="{{ route('audit.evidence.index') }}" icon="chevron-left" label="Kembali" variant="ghost" size="sm" />
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

    <div class="flex gap-2">
        @can('download', $evidence)
            <x-action href="{{ route('audit.evidence.download', $evidence) }}" icon="download" label="Unduh" />
        @endcan
        @can('delete', $evidence)
            <x-action href="{{ route('audit.evidence.destroy', $evidence) }}" icon="trash" label="Hapus" variant="danger" method="DELETE" confirm="Hapus bukti ini?" />
        @endcan
    </div>
</div>
@endsection