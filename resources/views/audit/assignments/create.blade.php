@extends('layouts.simmak')

@section('title', 'Buat Penugasan Auditor — SIMMAK PPKP')
@section('page-title', 'Buat Penugasan Auditor')
@section('page-subtitle', 'Tetapkan program, klinik sasaran, jadwal, dan tim auditor dalam satu alur berurutan.')

@section('breadcrumb')
    <li class="flex items-center gap-1 text-sim-muted">
        <a href="{{ route('audit.assignments.index') }}">Penugasan Auditor</a>
        <svg class="h-3.5 w-3.5 text-sim-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
        <span>Buat</span>
    </li>
@endsection

@section('content')
    <div class="mb-4">
        <x-action href="{{ route('audit.assignments.index') }}" icon="chevron-left" label="Kembali ke daftar" variant="ghost" size="sm" />
    </div>

    <div class="sim-card max-w-3xl">
        <div class="sim-card-header">
            <h3 class="sim-card-title">Formulir Penugasan Auditor</h3>
        </div>
        <div class="sim-card-body">
            @include('audit.assignments._form', [
                'action' => route('audit.assignments.store'),
                'method' => 'POST',
                'submitLabel' => 'Buat Penugasan',
                'cancelUrl' => route('audit.assignments.index'),
            ])
        </div>
    </div>
@endsection
