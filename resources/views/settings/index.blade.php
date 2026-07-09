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
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('settings.update') }}" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Nama Aplikasi</label>
                    <input type="text" name="app_name" value="{{ old('app_name', $app_name) }}" required
                           class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-100">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Target Akreditasi</label>
                    <input type="date" name="accreditation_target" value="{{ old('accreditation_target', $accreditation_target) }}" required
                           class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-100">
                    <p class="mt-1 text-xs text-slate-500">Tanggal target survei akreditasi organisasi.</p>
                </div>
                <div class="pt-2">
                    <button type="submit" class="rounded-lg bg-teal-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-teal-800">Simpan Pengaturan</button>
                </div>
            </form>
        </div>
    </div>
@endsection
