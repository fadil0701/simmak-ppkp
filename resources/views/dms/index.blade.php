@extends('layouts.simmak')

@section('title', 'Dokumen — SIMMAK PPKP')
@section('page-title', 'Manajemen Dokumen (DMS)')

@section('content')
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="simmak-card p-5">
            <div class="text-sm text-slate-500">Total Dokumen</div>
            <div class="mt-1 text-3xl font-bold text-slate-900">{{ $stats['total'] }}</div>
        </div>
        <div class="simmak-card p-5">
            <div class="text-sm text-slate-500">Draf</div>
            <div class="mt-1 text-3xl font-bold text-amber-600">{{ $stats['draft'] }}</div>
        </div>
        <div class="simmak-card p-5">
            <div class="text-sm text-slate-500">Terbit</div>
            <div class="mt-1 text-3xl font-bold text-teal-700">{{ $stats['published'] }}</div>
        </div>
        <div class="simmak-card p-5">
            <div class="text-sm text-slate-500">Kedaluwarsa / Segera</div>
            <div class="mt-1 text-3xl font-bold text-red-600">{{ $stats['expired'] + $stats['expiring_soon'] }}</div>
        </div>
    </div>

    <div class="simmak-card">
        <div class="simmak-card-header flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h3 class="font-semibold text-slate-800">Daftar Dokumen</h3>
            <a href="{{ route('dms.create') }}" class="sim-btn sim-btn-primary inline-flex items-center gap-2">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Dokumen Baru
            </a>
        </div>

        <form method="GET" class="border-b border-slate-100 px-5 py-4">
            <div class="flex flex-col gap-3 sm:flex-row">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari dokumen..."
                       class="sim-form-input flex-1">
                <select name="status" class="sim-form-select">
                    <option value="">Semua Status</option>
                    <option value="draft" @selected(request('status') === 'draft')>Draf</option>
                    <option value="published" @selected(request('status') === 'published')>Terbit</option>
                    <option value="expired" @selected(request('status') === 'expired')>Kedaluwarsa</option>
                </select>
                <button type="submit" class="sim-btn sim-btn-secondary">Filter</button>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="sim-table">
                <thead>
                    <tr>
                        <th>No. Dokumen</th>
                        <th>Judul</th>
                        <th>Kategori</th>
                        <th>Status</th>
                        <th>Kedaluwarsa</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($documents as $doc)
                        <tr>
                            <td class="font-mono text-xs text-slate-600">
                                <a href="{{ route('dms.show', $doc) }}" class="hover:text-teal-700 hover:underline">{{ $doc->document_number }}</a>
                            </td>
                            <td class="font-medium text-slate-800">
                                <a href="{{ route('dms.show', $doc) }}" class="hover:text-teal-700">{{ $doc->title }}</a>
                            </td>
                            <td class="text-slate-600">{{ $doc->documentType?->category?->name ?? '—' }}</td>
                            <td>
                                @php
                                    $statusColors = ['draft' => 'bg-slate-100 text-slate-700', 'published' => 'bg-emerald-100 text-emerald-800', 'expired' => 'bg-red-100 text-red-800'];
                                    $statusLabels = ['draft' => 'Draf', 'published' => 'Terbit', 'expired' => 'Kedaluwarsa', 'review' => 'Tinjauan', 'approved' => 'Disetujui'];
                                @endphp
                                <span class="simmak-badge {{ $statusColors[$doc->status] ?? 'bg-slate-100' }}">{{ $statusLabels[$doc->status] ?? $doc->status }}</span>
                            </td>
                            <td class="text-slate-600">{{ $doc->expiry_date?->format('d M Y') ?? '—' }}</td>
                            <td>
                                @if ($doc->status === 'draft')
                                    <form method="POST" action="{{ route('dms.publish', $doc) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-sm font-medium text-teal-700 hover:underline">Terbitkan</button>
                                    </form>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="sim-table-empty">Belum ada dokumen.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($documents->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">{{ $documents->links() }}</div>
        @endif
    </div>
@endsection
