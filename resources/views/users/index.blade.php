@extends('layouts.simmak')

@section('title', 'Pengguna — SIMMAK PPKP')
@section('page-title', 'Manajemen Pengguna')
@section('page-subtitle', 'Kelola akun pengguna, peran, dan akses klinik.')

@section('content')
    <div class="sim-page-header">
        <div>
            <h2 class="sim-page-title">Daftar Pengguna</h2>
            <p class="sim-page-subtitle">Kelola akun pengguna lokal, peran, dan akses per klinik.</p>
        </div>
        @can('users.manage')
            <x-action action="create" href="{{ route('users.create') }}" icon="plus" label="Tambah Pengguna" />
        @endcan
    </div>

    <div class="sim-card mb-6">
        <div class="sim-card-body">
            <form method="GET" action="{{ route('users.index') }}" class="grid gap-3 sm:grid-cols-4">
                <div class="sim-form-group sm:col-span-2">
                    <label class="sim-form-label" for="q">Pencarian</label>
                    <input type="text" id="q" name="q" value="{{ request('q') }}" class="sim-form-input" placeholder="Nama, username, atau surel">
                </div>
                <div class="sim-form-group">
                    <label class="sim-form-label" for="role_filter">Peran</label>
                    <select id="role_filter" name="role" class="sim-form-select">
                        <option value="">Semua peran</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->name }}" @selected(request('role') === $role->name)>{{ \App\Support\RoleLabel::for($role->name) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sim-form-group">
                    <label class="sim-form-label" for="status_filter">Status</label>
                    <div class="flex gap-2">
                        <select id="status_filter" name="status" class="sim-form-select">
                            <option value="">Semua</option>
                            <option value="active" @selected(request('status') === 'active')>Aktif</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
                        </select>
                        <x-action action="search" icon="sliders" label="Filter" />
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="sim-card">
        <div class="overflow-x-auto">
            <table class="sim-table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Surel</th>
                        <th>Klinik pertama</th>
                        <th>Peran jabatan</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td class="font-medium text-sim-ink">{{ $user->name }}</td>
                            <td class="text-sim-mono text-xs text-sim-muted">{{ $user->username ?? '—' }}</td>
                            <td class="text-sim-muted">{{ $user->email }}</td>
                            <td class="text-sim-muted">{{ $user->defaultClinic?->name ?? 'Semua Klinik' }}</td>
                            <td>
                                @forelse ($user->roles as $role)
                                    <span class="sim-badge sim-badge-status bg-sim-active text-sim-primary">{{ \App\Support\RoleLabel::for($role->name) }}</span>
                                @empty
                                    <span class="text-sim-muted">—</span>
                                @endforelse
                            </td>
                            <td>
                                <span class="sim-badge sim-badge-status {{ $user->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-sim-surface-alt text-sim-muted' }}">
                                    {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="flex justify-end gap-2">
                                    <x-action action="detail" href="{{ route('users.show', $user) }}" icon="file-text" label="Detail" size="sm" />
                                    @can('users.manage')
                                        <x-action action="edit" href="{{ route('users.edit', $user) }}" icon="pencil" label="Edit" size="sm" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="sim-table-empty">Belum ada pengguna yang cocok dengan filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())
            <div class="sim-card-footer">{{ $users->links() }}</div>
        @endif
    </div>
@endsection
