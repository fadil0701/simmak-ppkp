@extends('layouts.simmak')

@section('title', 'Tambah Audit Klinis — SIMMAK PPKP')
@section('page-title', 'Tambah Audit Klinis')
@section('page-subtitle', 'Catat audit klinis berdasarkan jadwal penugasan yang sudah disetujui.')

@section('content')
    <div class="mb-4">
        <x-action action="back" href="{{ route('audit.clinical.index') }}" icon="chevron-left" label="Kembali" size="sm" />
    </div>

    <div class="sim-card max-w-3xl">
        <div class="sim-card-header">
            <h3 class="sim-card-title">Form Audit Klinis</h3>
        </div>
        <div class="sim-card-body">
            <form method="POST" action="{{ route('audit.clinical.store') }}" class="space-y-4">
                @csrf

                @include('audit.clinical._form', ['audit' => null])

                <div class="flex justify-end gap-2 border-t border-sim-border-light pt-4">
                    <x-action action="back" href="{{ route('audit.clinical.index') }}" icon="chevron-left" label="Batal" />
                    <x-action action="save" icon="check-circle" label="Simpan Audit" />
                </div>
            </form>
        </div>
    </div>
@endsection
