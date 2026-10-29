@extends('layouts.simmak')

@section('title', 'Edit Program Audit — SIMMAK PPKP')
@section('page-title', 'Edit Program Audit')
@section('page-subtitle', 'Edit detail program audit. Hanya program dengan status draft yang dapat diubah.')

@section('breadcrumb')
    <li class="flex items-center gap-1 text-sim-muted">
        <a href="{{ route('audit.programs.index') }}">Program Audit</a>
        <svg class="h-3.5 w-3.5 text-sim-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
        <span>Edit Program</span>
    </li>
@endsection

@section('content')
    <div class="sim-card max-w-2xl">
        <div class="sim-card-header">
            <h3 class="sim-card-title">Edit Program Audit</h3>
        </div>
        <div class="sim-card-body space-y-4">
            <form method="POST" action="{{ route('audit.programs.update', $program) }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="sim-form-group">
                    <label class="sim-form-label">Kode Program</label>
                    <input type="text" value="{{ $program->program_code }}" readonly tabindex="-1"
                           class="sim-form-input bg-sim-surface-alt">
                    <p class="text-xs text-sim-muted mt-1">Nomor yang sudah terbit tidak diubah.</p>
                </div>

                <div class="sim-form-group">
                    <label class="sim-form-label">Jenis Audit</label>
                    <select name="audit_type" class="sim-form-select">
                        <option value="">— Pilih jenis audit —</option>
                        <option value="periodic" {{ old('audit_type', $program->audit_type) === 'periodic' ? 'selected' : '' }}>Audit Periodik</option>
                        <option value="thematic" {{ old('audit_type', $program->audit_type) === 'thematic' ? 'selected' : '' }}>Audit Tematik</option>
                        <option value="special" {{ old('audit_type', $program->audit_type) === 'special' ? 'selected' : '' }}>Audit Khusus</option>
                        <option value="follow_up" {{ old('audit_type', $program->audit_type) === 'follow_up' ? 'selected' : '' }}>Audit Ulang</option>
                    </select>
                    @if ($errors->has('audit_type'))
                        <p class="sim-form-error">{{ $errors->first('audit_type') }}</p>
                    @endif
                </div>

                <div class="sim-form-group">
                    <label class="sim-form-label">Judul Program <span class="sim-form-label-required"></span></label>
                    <input type="text" name="name" maxlength="255"
                           value="{{ old('name', $program->name) }}"
                           class="sim-form-input {{ $errors->has('name') ? 'border-sim-danger' : '' }}"
                           placeholder="Contoh: Program Audit Tahunan 2026" required>
                    @if ($errors->has('name'))
                        <p class="sim-form-error">{{ $errors->first('name') }}</p>
                    @endif
                </div>

                <div class="sim-form-group">
                    <label class="sim-form-label">Tujuan Program</label>
                    <textarea name="objective" rows="3"
                              class="sim-form-textarea {{ $errors->has('objective') ? 'border-sim-danger' : '' }}"
                              placeholder="Tujuan dan ruang lingkup program audit">{{ old('objective', $program->objective) }}</textarea>
                    @if ($errors->has('objective'))
                        <p class="sim-form-error">{{ $errors->first('objective') }}</p>
                    @endif
                </div>

                <div class="sim-form-group">
                    <label class="sim-form-label">Catatan Ruang Lingkup</label>
                    <textarea name="scope_notes" rows="3"
                              class="sim-form-textarea {{ $errors->has('scope_notes') ? 'border-sim-danger' : '' }}"
                              placeholder="Catatan tambahan mengenai ruang lingkup audit">{{ old('scope_notes', $program->scope_notes) }}</textarea>
                    @if ($errors->has('scope_notes'))
                        <p class="sim-form-error">{{ $errors->first('scope_notes') }}</p>
                    @endif
                </div>

                <div class="sim-form-group">
                    <label class="sim-form-label">Tahun Anggaran</label>
                    <select name="fiscal_year_id" class="sim-form-select">
                        <option value="">— Pilih tahun anggaran —</option>
                        @foreach ($fiscalYears as $fy)
                            <option value="{{ $fy->id }}" {{ old('fiscal_year_id', $program->fiscal_year_id) === $fy->id ? 'selected' : '' }}>{{ $fy->name }}</option>
                        @endforeach
                    </select>
                    @if ($errors->has('fiscal_year_id'))
                        <p class="sim-form-error">{{ $errors->first('fiscal_year_id') }}</p>
                    @endif
                    @can('settings.manage')
                        <p class="text-xs text-sim-muted mt-1">
                            Kelola di <a href="{{ route('fiscal-years.index') }}" class="text-sim-primary hover:underline">Master Tahun Anggaran</a>.
                        </p>
                    @endcan
                </div>

                <div class="flex justify-end gap-2 pt-4 border-t border-sim-border-light">
                    <x-action action="back" href="{{ route('audit.programs.show', $program) }}" icon="chevron-left" label="Kembali" />
                    <x-action action="edit" icon="check-circle" label="Simpan Perubahan" />
                </div>
            </form>
        </div>
    </div>
@endsection