@extends('layouts.simmak')

@section('title', 'Ubah Pengguna — SIMMAK PPKP')
@section('page-title', 'Ubah Pengguna')
@section('page-subtitle', 'Perbarui data, peran, dan akses klinik pengguna.')

@section('breadcrumb')
    <li class="flex items-center gap-1 text-sim-muted">
        <a href="{{ route('users.index') }}">Pengguna</a>
        <svg class="h-3.5 w-3.5 text-sim-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
        <a href="{{ route('users.show', $user) }}">{{ $user->name }}</a>
        <svg class="h-3.5 w-3.5 text-sim-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
        <span>Ubah</span>
    </li>
@endsection

@section('content')
    <div class="sim-card max-w-4xl">
        <div class="sim-card-header flex flex-wrap items-center justify-between gap-2">
            <h3 class="sim-card-title">Data Pengguna</h3>
            <p class="text-xs text-sim-muted">{{ $user->username }} · {{ $user->email }}</p>
        </div>
        <div class="sim-card-body">
            @include('users.partials.form', [
                'user' => $user,
                'action' => route('users.update', $user),
                'method' => 'PUT',
                'submitLabel' => 'Simpan Perubahan',
                'cancelUrl' => route('users.show', $user),
                'currentRoles' => $currentRoles ?? [],
                'currentScopes' => $currentScopes ?? [],
                'scopedClinicIds' => $scopedClinicIds ?? [],
                'derivedClinicIds' => $derivedClinicIds ?? [],
            ])
        </div>
    </div>
@endsection
