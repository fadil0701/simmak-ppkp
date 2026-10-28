@extends('layouts.simmak')

@section('title', 'Pengaturan — SIMMAK PPKP')
@section('page-title', 'Pengaturan Organisasi')

@section('content')
    <div class="mx-auto max-w-2xl simmak-card">
        <div class="simmak-card-header">
            <h3 class="font-semibold text-slate-800">Konfigurasi Umum</h3>
            <p class="mt-1 text-sm text-slate-500">Pengaturan organisasi yang berlaku untuk seluruh modul SIMMAK.</p>
        </div>
        <div class="simmak-card-body">
            @if ($errors->any())
                <div class="sim-alert sim-alert-error mb-4">
                    <span class="sim-alert-icon">!</span>{{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('settings.update') }}" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="sim-form-label">Nama Aplikasi<span class="sim-form-label-required"></span></label>
                    <input type="text" name="app_name" value="{{ old('app_name', $app_name) }}" required
                           class="sim-form-input">
                </div>
                <div>
                    <label class="sim-form-label">Target Akreditasi<span class="sim-form-label-required"></span></label>
                    <input type="date" name="accreditation_target" value="{{ old('accreditation_target', $accreditation_target) }}" required
                           class="sim-form-input">
                    <p class="sim-form-hint">Tanggal target survei akreditasi organisasi.</p>
                </div>
                <div class="pt-2">
                    <button type="submit" class="sim-btn sim-btn-save">Simpan Pengaturan</button>
                </div>
            </form>
        </div>
    </div>
@endsection
