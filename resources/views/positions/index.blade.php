@extends('layouts.simmak')

@section('title', 'Master Jabatan — SIMMAK PPKP')
@section('page-title', 'Master Jabatan')

@section('content')
    <div class="sim-page-header">
        <div>
            <h2 class="sim-page-title">Master Jabatan</h2>
            <p class="sim-page-subtitle">Jabatan hanya kelengkapan dokumen pegawai. Peran sistem (Super Admin, Auditor, Auditi, dan seterusnya) diatur di menu Pengguna, bukan di sini.</p>
        </div>
        @can('positions.manage')
            <a href="{{ route('positions.create') }}" class="sim-btn sim-btn-primary">Tambah Jabatan</a>
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
            <form method="GET" class="grid gap-3 sm:grid-cols-4">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari kode / nama jabatan" class="sim-form-input">
                <select name="category" class="sim-form-select">
                    <option value="">Semua Kategori</option>
                    @foreach (\App\Http\Controllers\PositionController::CATEGORIES as $value => $label)
                        <option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="status" class="sim-form-select">
                    <option value="">Semua Status</option>
                    <option value="active" @selected(request('status') === 'active')>Aktif</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
                </select>
                <div class="flex gap-2">
                    <button type="submit" class="sim-btn sim-btn-primary">Filter</button>
                    <a href="{{ route('positions.index') }}" class="sim-btn sim-btn-outline">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="sim-card">
        <div class="sim-card-header flex items-center justify-between">
            <h3 class="sim-card-title">Daftar Jabatan ({{ $positions->total() }})</h3>
        </div>
        <div class="sim-card-body p-0 overflow-x-auto">
            <table class="sim-table">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-3 py-2">Kode</th>
                        <th class="px-3 py-2">Nama Jabatan</th>
                        <th class="px-3 py-2">Kategori</th>
                        <th class="px-3 py-2 text-center">Jumlah Pegawai</th>
                        <th class="px-3 py-2 text-center">Status</th>
                        <th class="px-3 py-2 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($positions as $position)
                        <tr class="border-t border-sim-border-light">
                            <td class="px-3 py-2 font-mono text-xs">{{ $position->code }}</td>
                            <td class="px-3 py-2 font-medium text-sim-ink">{{ $position->name }}</td>
                            <td class="px-3 py-2 text-sim-muted">
                                {{ \App\Http\Controllers\PositionController::CATEGORIES[$position->category] ?? $position->category }}
                            </td>
                            <td class="px-3 py-2 text-center text-sim-muted">{{ $position->employees_count }}</td>
                            <td class="px-3 py-2 text-center">
                                <span class="sim-badge {{ $position->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $position->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <div class="sim-action-group">
                                    @can('positions.manage')
                                        <x-action href="{{ route('positions.edit', $position) }}" icon="pencil" label="Ubah" variant="secondary" size="sm" />
                                        <x-action href="{{ route('positions.destroy', $position) }}" icon="trash" label="Hapus" variant="danger" size="sm" method="DELETE" confirm="Hapus jabatan ini?" />
                                    @else
                                        <span class="text-sim-muted">—</span>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-sim-muted py-8">Belum ada data jabatan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($positions->hasPages())
            <div class="sim-card-footer">{{ $positions->links() }}</div>
        @endif
    </div>
@endsection
