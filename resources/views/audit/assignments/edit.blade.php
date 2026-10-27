@extends('layouts.simmak')

@section('title', 'Sunting Penugasan — SIMMAK PPKP')
@section('page-title', 'Sunting Penugasan')
@section('page-subtitle', $assignment->assignment_code . ' — ' . $assignment->title)

@section('breadcrumb')
    <li class="flex items-center gap-1 text-sim-muted">
        <a href="{{ route('audit.assignments.index') }}">Penugasan Auditor</a>
        <svg class="h-3.5 w-3.5 text-sim-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
        <a href="{{ route('audit.assignments.show', $assignment) }}">{{ $assignment->assignment_code }}</a>
        <svg class="h-3.5 w-3.5 text-sim-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
        <span>Sunting</span>
    </li>
@endsection

@section('content')
    <div class="mb-4">
        <x-action href="{{ route('audit.assignments.show', $assignment) }}" icon="chevron-left" label="Kembali ke detail" variant="ghost" size="sm" />
    </div>

    <div class="mb-4 sim-alert sim-alert-info">
        <span class="sim-alert-icon">i</span>
        <span>
            Kode tugas dan program audit terkunci setelah penugasan dibuat. Perubahan program berarti
            membuat penugasan baru agar jejak audit tetap utuh.
        </span>
    </div>

    <div class="sim-card max-w-3xl">
        <div class="sim-card-header">
            <h3 class="sim-card-title">Formulir Penugasan Auditor</h3>
        </div>
        <div class="sim-card-body">
            @include('audit.assignments._form', [
                'action' => route('audit.assignments.update', $assignment),
                'method' => 'PUT',
                'submitLabel' => 'Simpan Perubahan',
                'cancelUrl' => route('audit.assignments.show', $assignment),
                'assignment' => $assignment,
            ])
        </div>
    </div>
@endsection
