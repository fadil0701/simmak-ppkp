@extends('layouts.simmak')

@section('title', 'Buat Dokumen — SIMMAK PPKP')
@section('page-title', 'Buat Dokumen Baru')

@section('content')
    <div class="mx-auto max-w-2xl simmak-card">
        <div class="simmak-card-body">
            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('dms.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Jenis Dokumen</label>
                    <select name="document_type_id" required class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-100">
                        <option value="">Pilih jenis...</option>
                        @foreach ($types as $type)
                            <option value="{{ $type->id }}" @selected(old('document_type_id') == $type->id)>
                                {{ $type->category?->name }} — {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Judul Dokumen</label>
                    <input type="text" name="title" value="{{ old('title') }}" required
                           class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-100">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Deskripsi</label>
                    <textarea name="description" rows="3" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-100">{{ old('description') }}</textarea>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Ringkasan Perubahan</label>
                    <input type="text" name="change_summary" value="{{ old('change_summary', 'Versi awal dokumen') }}"
                           class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-100">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Upload File (PDF, DOC, XLS — max 10MB)</label>
                    <input type="file" name="file" accept=".pdf,.doc,.docx,.xls,.xlsx"
                           class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm file:mr-3 file:rounded file:border-0 file:bg-teal-50 file:px-3 file:py-1 file:text-sm file:font-medium file:text-teal-700">
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="rounded-lg bg-teal-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-teal-800">Simpan</button>
                    <a href="{{ route('dms.index') }}" class="rounded-lg border border-slate-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Batal</a>
                </div>
            </form>
        </div>
    </div>
@endsection
