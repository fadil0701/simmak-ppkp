@extends('layouts.simmak')

@section('title', 'Versi Instrumen — SIMMAK PPKP')
@section('page-title', 'Versi Instrumen')

@section('content')
    <div class="mb-4">
        <a href="{{ route('audit.standards.index') }}" class="sim-btn sim-btn-outline sim-btn-sm">← Kembali ke Daftar Standar</a>
    </div>

    <div class="sim-card">
        <div class="sim-card-header">
            <h3 class="sim-card-title">Versi Instrumen</h3>
        </div>
        <div class="sim-card-body">
            <p class="text-sm text-sim-muted">Ini adalah halaman detail versi. Gunakan halaman detail standar untuk mengelola versi per standar.</p>
        </div>
    </div>
@endsection
