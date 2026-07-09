@extends('layouts.simmak')

@section('title', $document->title.' — SIMMAK PPKP')
@section('page-title', 'Detail Dokumen')

@section('content')
    <div class="mb-4">
        <a href="{{ route('dms.index') }}" class="text-sm font-medium text-teal-700 hover:underline">← Kembali ke Daftar Dokumen</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="simmak-card lg:col-span-2">
            <div class="simmak-card-header">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">{{ $document->title }}</h2>
                        <p class="font-mono text-sm text-slate-500">{{ $document->document_number }}</p>
                    </div>
                    @php
                        $statusColors = [
                            'draft' => 'bg-slate-100 text-slate-700',
                            'review' => 'bg-blue-100 text-blue-800',
                            'approved' => 'bg-indigo-100 text-indigo-800',
                            'published' => 'bg-emerald-100 text-emerald-800',
                            'expired' => 'bg-red-100 text-red-800',
                        ];
                    @endphp
                    <span class="simmak-badge {{ $statusColors[$document->status] ?? 'bg-slate-100' }}">{{ \App\Support\UiLabel::documentStatus($document->status) }}</span>
                </div>
            </div>
            <div class="simmak-card-body space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><span class="text-xs text-slate-500">Kategori</span><p class="font-medium">{{ $document->documentType?->category?->name ?? '—' }}</p></div>
                    <div><span class="text-xs text-slate-500">Jenis</span><p class="font-medium">{{ $document->documentType?->name ?? '—' }}</p></div>
                    <div><span class="text-xs text-slate-500">Klinik</span><p class="font-medium">{{ $document->clinic?->name ?? '—' }}</p></div>
                    <div><span class="text-xs text-slate-500">Pemilik</span><p class="font-medium">{{ $document->owner?->name ?? '—' }}</p></div>
                    <div><span class="text-xs text-slate-500">Berlaku Sejak</span><p class="font-medium">{{ $document->effective_date?->format('d M Y') ?? '—' }}</p></div>
                    <div><span class="text-xs text-slate-500">Kedaluwarsa</span><p class="font-medium">{{ $document->expiry_date?->format('d M Y') ?? '—' }}</p></div>
                </div>
                @if ($document->description)
                    <div>
                        <span class="text-xs text-slate-500">Deskripsi</span>
                        <p class="mt-1 text-sm text-slate-700">{{ $document->description }}</p>
                    </div>
                @endif

                @if ($document->status === 'draft')
                    <div class="flex flex-wrap gap-3">
                        <form method="POST" action="{{ route('dms.submit-review', $document) }}">
                            @csrf
                            <button type="submit" class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Ajukan Tinjauan</button>
                        </form>
                        <form method="POST" action="{{ route('dms.publish', $document) }}">
                            @csrf
                            <button type="submit" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Terbitkan Langsung</button>
                        </form>
                    </div>
                @elseif ($document->status === 'review')
                    <form method="POST" action="{{ route('dms.approve-review', $document) }}" class="space-y-3">
                        @csrf
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Komentar Tinjauan (opsional)</label>
                            <textarea name="comments" rows="2" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"></textarea>
                        </div>
                        <button type="submit" class="rounded-lg bg-indigo-700 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-800">Setujui Tinjauan</button>
                    </form>
                @elseif ($document->status === 'approved')
                    <form method="POST" action="{{ route('dms.publish', $document) }}">
                        @csrf
                        <button type="submit" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800">Terbitkan Dokumen</button>
                    </form>
                @endif

                @if ($document->reviews->isNotEmpty())
                    <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                        <h4 class="text-sm font-semibold text-slate-700">Riwayat Tinjauan</h4>
                        <div class="mt-3 space-y-2">
                            @foreach ($document->reviews as $review)
                                <div class="flex items-start justify-between text-sm">
                                    <div>
                                        <span class="font-medium text-slate-800">{{ $review->reviewer?->name }}</span>
                                        <span class="text-xs text-slate-500">· Langkah {{ $review->step_order }}</span>
                                        @if ($review->comments)
                                            <p class="text-xs text-slate-600">{{ $review->comments }}</p>
                                        @endif
                                    </div>
                                    <span class="simmak-badge bg-slate-100 text-slate-700">{{ \App\Support\UiLabel::reviewStatus($review->status) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            @if ($document->qrCode)
                <div class="simmak-card p-5 text-center">
                    <h3 class="mb-3 text-sm font-semibold text-slate-700">QR Verifikasi</h3>
                    <div class="mx-auto inline-block rounded-lg border border-slate-200 bg-white p-3">
                        {!! QrCode::size(140)->margin(1)->generate($document->qrCode->verificationUrl()) !!}
                    </div>
                    <p class="mt-3 text-xs text-slate-500">Scan untuk verifikasi keaslian dokumen</p>
                    <a href="{{ $document->qrCode->verificationUrl() }}" target="_blank" rel="noopener"
                       class="mt-1 inline-block text-[10px] font-medium text-teal-700 hover:underline">
                        Buka halaman verifikasi
                    </a>
                </div>
            @endif

            <div class="simmak-card">
                <div class="simmak-card-header">
                    <h3 class="text-sm font-semibold text-slate-700">Riwayat Versi</h3>
                </div>
                <div class="simmak-card-body space-y-3">
                    @foreach ($document->versions as $version)
                        <div class="border-b border-slate-50 pb-3 last:border-0">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-slate-800">v{{ $version->version_number }}</span>
                                <span class="text-xs text-slate-500">{{ $version->created_at->format('d M Y') }}</span>
                            </div>
                            <p class="text-xs text-slate-500">{{ $version->change_summary }}</p>
                            @if ($version->attachment)
                                <a href="{{ route('attachments.download', $version->attachment) }}" class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-teal-700 hover:underline">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    {{ $version->file_name ?? $version->attachment->original_name }}
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
