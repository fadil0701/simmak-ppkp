@extends('layouts.simmak')

@section('title', 'Catat Risiko — SIMMAK PPKP')
@section('page-title', 'Catat Risiko Baru')

@section('content')
    <div class="mx-auto max-w-2xl simmak-card">
        <div class="simmak-card-body">
            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('risk.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Register Risiko</label>
                    <select name="risk_register_id" required class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-100">
                        <option value="">Pilih register...</option>
                        @foreach ($registers as $register)
                            <option value="{{ $register->id }}" @selected(old('risk_register_id') == $register->id)>{{ $register->register_code }} — {{ $register->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Kategori Risiko</label>
                    <select name="risk_category_id" required class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-100">
                        <option value="">Pilih kategori...</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('risk_category_id') == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Judul Risiko</label>
                    <input type="text" name="title" value="{{ old('title') }}" required
                           class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-100">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Deskripsi</label>
                    <textarea name="risk_description" rows="3" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-100">{{ old('risk_description') }}</textarea>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Kemungkinan Inheren (1–5)</label>
                        <input type="number" name="inherent_likelihood" value="{{ old('inherent_likelihood', 3) }}" min="1" max="5" required
                               class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-100">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Dampak Inheren (1–5)</label>
                        <input type="number" name="inherent_impact" value="{{ old('inherent_impact', 3) }}" min="1" max="5" required
                               class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-100">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Residual Likelihood (1–5)</label>
                        <input type="number" name="residual_likelihood" value="{{ old('residual_likelihood', 2) }}" min="1" max="5" required
                               class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-100">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Residual Impact (1–5)</label>
                        <input type="number" name="residual_impact" value="{{ old('residual_impact', 2) }}" min="1" max="5" required
                               class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-100">
                    </div>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="rounded-lg bg-teal-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-teal-800">Simpan</button>
                    <a href="{{ route('risk.index') }}" class="rounded-lg border border-slate-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Batal</a>
                </div>
            </form>
        </div>
    </div>
@endsection
