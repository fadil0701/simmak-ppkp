@extends('layouts.simmak')

@section('title', 'Sumber Regulasi — SIMMAK PPKP')
@section('page-title', 'Sumber Regulasi')

@section('content')
    <div class="sim-page-header">
        <div>
            <h2 class="sim-page-title">Register Sumber Regulasi</h2>
            <p class="sim-page-subtitle">
                Dokumen hukum/acuan yang dipakai standar audit. Data baru selalu berstatus
                <strong>Belum Diverifikasi</strong> sampai diperiksa terhadap dokumen resmi.
            </p>
        </div>
        @can('audit.standard.manage')
            <a href="{{ route('audit.regulatory.create') }}" class="sim-btn sim-btn-create">Tambah Sumber Regulasi</a>
        @endcan
    </div>

    @if (session('status'))
        <div class="sim-alert sim-alert-success mb-4">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="sim-alert sim-alert-error mb-4">{{ session('error') }}</div>
    @endif

    <div class="sim-card mb-4">
        <div class="sim-card-body">
            <form method="GET" class="grid gap-3 sm:grid-cols-3">
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="Cari nomor / judul / penerbit" class="sim-form-input">
                <select name="status" class="sim-form-select">
                    <option value="">Semua Status Verifikasi</option>
                    @foreach (\App\Http\Controllers\RegulatorySourceController::STATUSES as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <div class="flex gap-2">
                    <button type="submit" class="sim-btn sim-btn-neutral">Filter</button>
                    <a href="{{ route('audit.regulatory.index') }}" class="sim-btn sim-btn-back">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="sim-card">
        <div class="sim-card-header flex items-center justify-between">
            <h3 class="sim-card-title">Daftar Sumber Regulasi ({{ $sources->total() }})</h3>
        </div>
        <div class="sim-card-body p-0 overflow-x-auto">
            <table class="sim-table">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-3 py-2">Nomor Dokumen</th>
                        <th class="px-3 py-2">Judul</th>
                        <th class="px-3 py-2">Penerbit</th>
                        <th class="px-3 py-2">Masa Berlaku</th>
                        <th class="px-3 py-2 text-center">Status Verifikasi</th>
                        <th class="px-3 py-2 text-center">Dipakai Standar</th>
                        <th class="px-3 py-2 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sources as $source)
                        <tr class="border-t border-sim-border-light">
                            <td class="px-3 py-2 font-mono text-xs">{{ $source->document_number }}</td>
                            <td class="px-3 py-2">
                                <div class="font-medium text-sim-ink">{{ $source->title }}</div>
                                @if ($source->source_url)
                                    <a href="{{ $source->source_url }}" target="_blank" rel="noopener"
                                       class="text-xs text-sim-primary hover:underline">{{ \Illuminate\Support\Str::limit($source->source_url, 60) }}</a>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-sim-muted">{{ $source->issuer ?? '—' }}</td>
                            <td class="px-3 py-2 text-xs text-sim-muted">
                                {{ $source->effective_from?->format('d/m/Y') ?? '—' }}
                                s.d.
                                {{ $source->effective_until?->format('d/m/Y') ?? 'sekarang' }}
                            </td>
                            <td class="px-3 py-2 text-center">
                                <span class="sim-badge sim-badge-status {{ \App\Http\Controllers\RegulatorySourceController::statusBadgeClass($source->verification_status) }}">
                                    {{ \App\Http\Controllers\RegulatorySourceController::statusLabel($source->verification_status) }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-center text-sim-muted">{{ $source->standards_count }}</td>
                            <td class="px-3 py-2 text-center">
                                <div class="sim-action-group">
                                    @can('audit.standard.manage')
                                        <x-action action="edit" href="{{ route('audit.regulatory.edit', $source) }}" icon="pencil" label="Edit" size="sm" />
                                        <x-action action="delete" href="{{ route('audit.regulatory.destroy', $source) }}" icon="trash" label="Hapus" size="sm" method="DELETE" confirm="Hapus sumber regulasi ini?" />
                                    @else
                                        <span class="text-sim-muted">—</span>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-sim-muted py-8">Belum ada sumber regulasi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($sources->hasPages())
            <div class="sim-card-footer">{{ $sources->links() }}</div>
        @endif
    </div>
@endsection
