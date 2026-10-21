@extends('layouts.simmak')

@section('title', 'Buat Dokumen — SIMMAK PPKP')
@section('page-title', 'Buat Dokumen Baru')

@section('content')
    <div class="mx-auto max-w-2xl simmak-card">
        <div class="simmak-card-body">
            @if ($errors->any())
                <div class="sim-alert sim-alert-error mb-4">
                    <span class="sim-alert-icon">!</span>{{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('dms.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="sim-form-label">Jenis Dokumen<span class="sim-form-label-required"></span></label>
                    <select name="document_type_id" required class="sim-form-select">
                        <option value="">Pilih jenis...</option>
                        @foreach ($types as $type)
                            <option value="{{ $type->id }}" @selected(old('document_type_id') == $type->id)>
                                {{ $type->category?->name }} — {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="sim-form-label">Judul Dokumen<span class="sim-form-label-required"></span></label>
                    <input type="text" name="title" value="{{ old('title') }}" required
                           class="sim-form-input">
                </div>
                <div>
                    <label class="sim-form-label">Deskripsi</label>
                    <textarea name="description" rows="3" class="sim-form-textarea">{{ old('description') }}</textarea>
                </div>
                <div>
                    <label class="sim-form-label">Ringkasan Perubahan</label>
                    <input type="text" name="change_summary" value="{{ old('change_summary', 'Versi awal dokumen') }}"
                           class="sim-form-input">
                </div>
                <div>
                    <label class="sim-form-label">Upload File (PDF, DOC, XLS — max 10MB)</label>
                    <input type="file" name="file" accept=".pdf,.doc,.docx,.xls,.xlsx"
                           class="sim-form-input file:mr-3 file:rounded file:border-0 file:bg-teal-50 file:px-3 file:py-1 file:text-sm file:font-medium file:text-teal-700">
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="sim-btn sim-btn-primary">Simpan</button>
                    <a href="{{ route('dms.index') }}" class="sim-btn sim-btn-outline">Batal</a>
                </div>
            </form>
        </div>
    </div>
@endsection
