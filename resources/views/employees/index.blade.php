@extends('layouts.simmak')

@section('title', 'Master Pegawai — SIMMAK PPKP')
@section('page-title', 'Master Pegawai')

@section('content')
    <div class="sim-page-header">
        <div>
            <h2 class="sim-page-title">Master Pegawai</h2>
            <p class="sim-page-subtitle">Data pegawai sumber untuk pembuatan akun pengguna dan penugasan audit.</p>
        </div>
        @can('employees.manage')
            <a href="{{ route('employees.create') }}" class="sim-btn sim-btn-create">Tambah Pegawai</a>
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
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama / NIP / jabatan" class="sim-form-input">
                <select name="clinic_id" class="sim-form-select">
                    <option value="">Semua Klinik</option>
                    @foreach ($clinics as $clinic)
                        <option value="{{ $clinic->id }}" @selected(request('clinic_id') === $clinic->id)>
                            {{ $clinic->code }} — {{ $clinic->name }}
                        </option>
                    @endforeach
                </select>
                <select name="status" class="sim-form-select">
                    <option value="">Semua Status</option>
                    <option value="active" @selected(request('status') === 'active')>Aktif</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
                </select>
                <div class="flex gap-2">
                    <button type="submit" class="sim-btn sim-btn-neutral">Filter</button>
                    <a href="{{ route('employees.index') }}" class="sim-btn sim-btn-back">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="sim-card">
        <div class="sim-card-header flex items-center justify-between">
            <h3 class="sim-card-title">Daftar Pegawai ({{ $employees->total() }})</h3>
        </div>
        <div class="sim-card-body p-0 overflow-x-auto">
            <table class="sim-table">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-3 py-2">NIP</th>
                        <th class="px-3 py-2">Nama Pegawai</th>
                        <th class="px-3 py-2">Jabatan</th>
                        <th class="px-3 py-2">Klinik</th>
                        <th class="px-3 py-2">Email</th>
                        <th class="px-3 py-2 text-center">Status</th>
                        <th class="px-3 py-2 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $employee)
                        <tr class="border-t border-sim-border-light">
                            <td class="px-3 py-2 font-mono text-xs">{{ $employee->employee_number }}</td>
                            <td class="px-3 py-2 font-medium text-sim-ink">{{ $employee->full_name }}</td>
                            <td class="px-3 py-2 text-sim-muted">
                                {{ $employee->positionMaster?->name ?? $employee->position ?? '—' }}
                            </td>
                            <td class="px-3 py-2 text-sim-muted">
                                @php
                                    $heldClinics = $employee->clinicAssignments
                                        ->sortByDesc('is_primary')
                                        ->map(fn ($assignment) => $assignment->clinic)
                                        ->filter();
                                @endphp
                                @if ($heldClinics->isEmpty())
                                    {{ $employee->clinic?->name ?? '—' }}
                                @else
                                    {{ $heldClinics->first()->name }}
                                    @if ($heldClinics->count() > 1)
                                        <div class="text-xs">+{{ $heldClinics->count() - 1 }} klinik lain</div>
                                    @endif
                                @endif
                            </td>
                            <td class="px-3 py-2 text-xs">{{ $employee->email ?? '—' }}</td>
                            <td class="px-3 py-2 text-center">
                                <span class="sim-badge {{ $employee->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $employee->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <div class="sim-action-group">
                                    @can('employees.manage')
                                        <x-action action="edit" href="{{ route('employees.edit', $employee) }}" icon="pencil" label="Edit" size="sm" />
                                        <x-action action="delete" href="{{ route('employees.destroy', $employee) }}" icon="trash" label="Hapus" size="sm" method="DELETE" confirm="Hapus data pegawai ini?" />
                                    @endif
                                    @can('users.manage')
                                        @if (! $employee->users()->exists())
                                            <x-action action="create" href="{{ route('users.create', ['employee_id' => $employee->id]) }}" icon="users" label="Buat Akun" size="sm" />
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-sim-muted py-8">Belum ada data pegawai.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($employees->hasPages())
            <div class="sim-card-footer">{{ $employees->links() }}</div>
        @endif
    </div>
@endsection
