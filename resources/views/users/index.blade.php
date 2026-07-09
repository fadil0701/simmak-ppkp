@extends('layouts.simmak')

@section('title', 'Pengguna — SIMMAK PPKP')
@section('page-title', 'Manajemen Pengguna')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-600">Daftar pengguna lokal organisasi dan akses peran.</p>
        <a href="{{ route('users.create') }}" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800">+ Tambah Pengguna</a>
    </div>

    <div class="simmak-card">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-100 bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Nama</th>
                        <th class="px-5 py-3">Username</th>
                        <th class="px-5 py-3">Email</th>
                        <th class="px-5 py-3">Klinik Default</th>
                        <th class="px-5 py-3">Peran</th>
                        <th class="px-5 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-medium text-slate-800">{{ $user->name }}</td>
                            <td class="px-5 py-3 font-mono text-xs text-slate-600">{{ $user->username }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $user->email }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $user->defaultClinic?->name ?? '—' }}</td>
                            <td class="px-5 py-3">
                                @foreach ($user->roles as $role)
                                    <span class="simmak-badge bg-teal-100 text-teal-800">{{ $role->name }}</span>
                                @endforeach
                            </td>
                            <td class="px-5 py-3">
                                <span class="simmak-badge {{ $user->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">Belum ada pengguna.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">{{ $users->links() }}</div>
        @endif
    </div>
@endsection
