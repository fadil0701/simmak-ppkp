@extends('layouts.simmak')

@section('title', 'Tambah Pengguna — SIMMAK PPKP')
@section('page-title', 'Tambah Pengguna')
@section('page-subtitle', 'Hubungkan ke pegawai, pilih peran jabatan, lalu tugas di sistem. Batas klinik mengikuti master pegawai.')

@section('breadcrumb')
    <li class="flex items-center gap-1 text-sim-muted">
        <a href="{{ route('users.index') }}">Pengguna</a>
        <svg class="h-3.5 w-3.5 text-sim-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
        <span>Tambah</span>
    </li>
@endsection

@section('content')
    <div class="sim-card max-w-4xl">
        <div class="sim-card-header flex flex-wrap items-center justify-between gap-2">
            <h3 class="sim-card-title">Data Pengguna Baru</h3>
            <p class="text-xs text-sim-muted">Isi berurutan dari bagian 1 sampai 6.</p>
        </div>
        <div class="sim-card-body">
            @include('users.partials.form', [
                'user' => null,
                'action' => route('users.store'),
                'method' => 'POST',
                'submitLabel' => 'Simpan Pengguna',
                'cancelUrl' => route('users.index'),
                'prefilledEmployeeId' => $prefilledEmployeeId ?? null,
            ])
        </div>
    </div>
@endsection
