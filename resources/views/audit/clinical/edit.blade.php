@extends('layouts.simmak')

@section('title', 'Edit Audit Klinis — SIMMAK PPKP')
@section('page-title', 'Edit Audit Klinis: '.$audit->audit_number)

@section('content')
    <div class="mb-4">
        <x-action action="back" href="{{ route('audit.clinical.show', $audit) }}" icon="chevron-left" label="Kembali" size="sm" />
    </div>

    <div class="sim-card max-w-3xl">
        <div class="sim-card-header">
            <h3 class="sim-card-title">Form Audit Klinis</h3>
        </div>
        <div class="sim-card-body">
            <form method="POST" action="{{ route('audit.clinical.update', $audit) }}" class="space-y-4">
                @csrf
                @method('PUT')

                @include('audit.clinical._form', ['audit' => $audit])

                <div class="flex justify-end gap-2 border-t border-sim-border-light pt-4">
                    <x-action action="back" href="{{ route('audit.clinical.show', $audit) }}" icon="chevron-left" label="Batal" />
                    <x-action action="save" icon="check-circle" label="Perbarui Audit" />
                </div>
            </form>
        </div>
    </div>
@endsection
