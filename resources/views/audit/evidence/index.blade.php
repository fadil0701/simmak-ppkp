@extends('layouts.simmak')

@section('title', 'Bukti Audit — SIMMAK PPKP')
@section('page-title', 'Bukti Audit')

@section('content')
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Bukti Audit</h2>
            <p class="text-sm text-slate-500">Kelola bukti objektif yang mendukung hasil pemeriksaan audit. Berkas disimpan secara privat dan diakses dengan otorisasi.</p>
        </div>

        @can('audit.evidence.upload')
            <a href="{{ route('audit.evidence.create') }}" class="sim-btn sim-btn-primary inline-flex items-center gap-2">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Unggah Bukti
            </a>
        @endcan
    </div>

    <div class="simmak-card">
        <div class="simmak-card-header">
            <h3 class="font-semibold text-slate-800">Daftar Bukti</h3>
        </div>
        <div class="simmak-card-body overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-100 bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Nama Berkas</th>
                        <th class="px-4 py-3">Tipe</th>
                        <th class="px-4 py-3">Ukuran</th>
                        <th class="px-4 py-3">Klasifikasi</th>
                        <th class="px-4 py-3 text-center">Diunggah Oleh</th>
                        <th class="px-4 py-3 text-center">Tanggal</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($evidences as $evidence)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-800">{{ $evidence->original_filename }}</div>
                                @if (in_array($evidence->id, $unavailableIds, true))
                                    <div class="mt-1 inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-amber-200">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                                        Berkas hilang di penyimpanan
                                    </div>
                                @elseif ($evidence->description)
                                    <div class="text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($evidence->description, 60) }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $evidence->mime_type }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ number_format($evidence->size_bytes / 1024, 1) }} KB</td>
                            <td class="px-4 py-3 text-slate-600">{{ ucfirst($evidence->access_classification) }}</td>
                            <td class="px-4 py-3 text-center text-slate-600">{{ $evidence->uploader?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-center text-slate-600">{{ $evidence->uploaded_at?->format('d M Y H:i') ?? '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <div class="sim-action-group">
                                    @if (in_array($evidence->id, $unavailableIds, true))
                                        <x-action href="{{ route('audit.evidence.show', $evidence) }}" icon="eye" label="Detail" variant="outline" size="sm" />
                                        <span class="sim-btn sim-btn-outline sim-btn-sm cursor-not-allowed opacity-50" title="Berkas tidak ditemukan di penyimpanan">
                                            <span class="inline-flex">@include('partials.icons.download')</span>
                                            <span>Unduh</span>
                                        </span>
                                    @else
                                        <x-action href="{{ route('audit.evidence.download', $evidence) }}" icon="download" label="Unduh" variant="outline" size="sm" />
                                    @endif
                                    @can('delete', $evidence)
                                        <x-action href="{{ route('audit.evidence.destroy', $evidence) }}" icon="trash" label="Hapus" variant="danger" size="sm" method="DELETE" confirm="Hapus bukti ini?" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-500">Belum ada bukti audit.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($evidences->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $evidences->links() }}</div>
        @endif
    </div>
@endsection
