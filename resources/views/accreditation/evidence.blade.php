@extends('layouts.simmak')

@section('title', 'Repositori Bukti — SIMMAK PPKP')
@section('page-title', 'Repositori Bukti Akreditasi')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('accreditation.index') }}" class="text-sm font-medium text-teal-700 hover:underline">← Kembali ke Dashboard Akreditasi</a>
        <a href="{{ route('accreditation.standards') }}" class="text-sm font-medium text-teal-700 hover:underline">Lihat Standar →</a>
    </div>

    @if (! $program)
        <div class="simmak-card p-8 text-center text-slate-500">Belum ada program akreditasi aktif.</div>
    @else
        <div class="mb-6 grid gap-4 sm:grid-cols-2">
            <div class="simmak-card p-5"><div class="text-sm text-slate-500">Total Bukti Diperlukan</div><div class="text-3xl font-bold">{{ $stats['total'] }}</div></div>
            <div class="simmak-card p-5"><div class="text-sm text-slate-500">Sudah Terupload</div><div class="text-3xl font-bold text-emerald-600">{{ $stats['uploaded'] }}</div></div>
        </div>

        <div class="simmak-card">
            <div class="overflow-x-auto">
                <table class="sim-table">
                    <thead>
                        <tr>
                            <th>Judul Bukti</th>
                            <th>Status</th>
                            <th>Dokumen Terkait</th>
                            <th>Tautkan Dokumen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($evidence as $item)
                            <tr>
                                <td class="font-medium text-slate-800">{{ $item->title }}</td>
                                <td>
                                    @php $ec = $item->status === 'uploaded' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'; @endphp
                                    <span class="simmak-badge {{ $ec }}">{{ ucfirst($item->status) }}</span>
                                </td>
                                <td class="text-slate-600">
                                    @if ($item->document)
                                        <a href="{{ route('dms.show', $item->document) }}" class="text-teal-700 hover:underline">{{ $item->document->document_number }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if ($item->status !== 'uploaded')
                                        <form method="POST" action="{{ route('accreditation.link-evidence', $item) }}" class="flex flex-wrap items-center gap-2">
                                            @csrf
                                            <select name="document_id" required class="sim-form-select sim-btn-sm">
                                                <option value="">Pilih dokumen...</option>
                                                @foreach ($documents as $doc)
                                                    <option value="{{ $doc->id }}">{{ $doc->document_number }} — {{ \Illuminate\Support\Str::limit($doc->title, 40) }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="sim-btn sim-btn-primary sim-btn-sm">Tautkan</button>
                                        </form>
                                    @else
                                        <span class="text-xs text-slate-400">Sudah tertaut</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="sim-table-empty">Belum ada bukti akreditasi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($evidence->hasPages())
                <div class="border-t border-slate-100 px-5 py-3">{{ $evidence->links() }}</div>
            @endif
        </div>
    @endif
@endsection
