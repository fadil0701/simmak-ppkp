@extends('layouts.simmak')

@section('title', 'Catat Risiko — SIMMAK PPKP')
@section('page-title', 'Catat Risiko Baru')

@section('content')
    <div class="mx-auto max-w-2xl simmak-card">
        <div class="simmak-card-body">
            @if ($errors->any())
                <div class="sim-alert sim-alert-error mb-4">
                    <span class="sim-alert-icon">!</span>{{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('risk.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="sim-form-label">Register Risiko<span class="sim-form-label-required"></span></label>
                    <select name="risk_register_id" required class="sim-form-select">
                        <option value="">Pilih register...</option>
                        @foreach ($registers as $register)
                            <option value="{{ $register->id }}" @selected(old('risk_register_id') == $register->id)>{{ $register->register_code }} — {{ $register->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="sim-form-label">Kategori Risiko<span class="sim-form-label-required"></span></label>
                    <select name="risk_category_id" required class="sim-form-select">
                        <option value="">Pilih kategori...</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('risk_category_id') == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="sim-form-label">Judul Risiko<span class="sim-form-label-required"></span></label>
                    <input type="text" name="title" value="{{ old('title') }}" required
                           class="sim-form-input">
                </div>
                <div>
                    <label class="sim-form-label">Deskripsi</label>
                    <textarea name="risk_description" rows="3" class="sim-form-textarea">{{ old('risk_description') }}</textarea>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="sim-form-label">Kemungkinan Inheren (1–5)<span class="sim-form-label-required"></span></label>
                        <input type="number" name="inherent_likelihood" value="{{ old('inherent_likelihood', 3) }}" min="1" max="5" required
                               class="sim-form-input">
                    </div>
                    <div>
                        <label class="sim-form-label">Dampak Inheren (1–5)<span class="sim-form-label-required"></span></label>
                        <input type="number" name="inherent_impact" value="{{ old('inherent_impact', 3) }}" min="1" max="5" required
                               class="sim-form-input">
                    </div>
                    <div>
                        <label class="sim-form-label">Residual Likelihood (1–5)<span class="sim-form-label-required"></span></label>
                        <input type="number" name="residual_likelihood" value="{{ old('residual_likelihood', 2) }}" min="1" max="5" required
                               class="sim-form-input">
                    </div>
                    <div>
                        <label class="sim-form-label">Residual Impact (1–5)<span class="sim-form-label-required"></span></label>
                        <input type="number" name="residual_impact" value="{{ old('residual_impact', 2) }}" min="1" max="5" required
                               class="sim-form-input">
                    </div>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="sim-btn sim-btn-save">Simpan</button>
                    <a href="{{ route('risk.index') }}" class="sim-btn sim-btn-neutral">Batal</a>
                </div>
            </form>
        </div>
    </div>
@endsection
