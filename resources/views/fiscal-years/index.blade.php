@extends('layouts.simmak')

@section('title', 'Tahun Anggaran')

@section('content')
<div class="max-w-4xl mx-auto space-y-4">
    <h1 class="sim-page-title">Master Tahun Anggaran</h1>

    @if (session('status'))
        <div class="sim-alert sim-alert-success">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="sim-alert sim-alert-error">{{ session('error') }}</div>
    @endif

    <div class="sim-card">
        <div class="sim-card-header">Tambah Tahun Anggaran</div>
        <div class="sim-card-body">
            <form method="POST" action="{{ route('fiscal-years.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                @csrf
                <input name="code" placeholder="Kode (FY2026)" class="sim-form-input" required>
                <input name="name" placeholder="Nama (Tahun Anggaran 2026)" class="sim-form-input" required>
                <input type="date" name="start_date" class="sim-form-input" required>
                <input type="date" name="end_date" class="sim-form-input" required>
                <div class="md:col-span-4 flex justify-end">
                    <x-action action="save" icon="check-circle" label="Simpan" />
                </div>
            </form>
        </div>
    </div>

    <div class="sim-card">
        <div class="sim-card-header">Daftar Tahun Anggaran</div>
        <div class="sim-card-body">
            <table class="sim-table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Mulai</th>
                        <th>Selesai</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($years as $fy)
                        <tr>
                            <td>{{ $fy->code }}</td>
                            <td>{{ $fy->name }}</td>
                            <td>{{ $fy->start_date?->format('d M Y') }}</td>
                            <td>{{ $fy->end_date?->format('d M Y') }}</td>
                            <td>
                                <div class="sim-action-group">
                                    <x-action action="delete" href="{{ route('fiscal-years.destroy', $fy) }}" icon="trash" label="Hapus" size="sm" method="DELETE" confirm="Hapus tahun anggaran ini?" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-sim-muted">Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection