@extends('layouts.simmak')

@section('title', 'Sunting Temuan — SIMMAK PPKP')
@section('page-title', 'Sunting Temuan')

@section('content')
    <div class="mb-4">
        <a href="{{ route('findings.show', $finding) }}" class="text-sm font-medium text-teal-700 hover:underline">← Kembali ke Detail Temuan</a>
    </div>

    <div class="simmak-card max-w-3xl">
        <div class="simmak-card-header">
            <h3 class="font-semibold text-slate-800">Sunting Temuan: {{ $finding->finding_code }}</h3>
        </div>
        <div class="simmak-card-body space-y-4">
            <form method="POST" action="{{ route('findings.update', $finding) }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="sim-form-group">
                    <label class="sim-form-label">Judul Temuan <span class="sim-form-label-required"></span></label>
                    <input type="text" name="title" maxlength="255"
                           value="{{ old('title', $finding->title) }}"
                           class="sim-form-input {{ $errors->has('title') ? 'border-sim-danger' : '' }}"
                           required>
                    @if ($errors->has('title'))
                        <p class="sim-form-error">{{ $errors->first('title') }}</p>
                    @endif
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sim-form-group">
                        <label class="sim-form-label">Tingkat Risiko <span class="sim-form-label-required"></span></label>
                        <select name="severity" class="sim-form-select {{ $errors->has('severity') ? 'border-sim-danger' : '' }}" required>
                            <option value="minor" {{ old('severity', $finding->severity) === 'minor' ? 'selected' : '' }}>Ringan</option>
                            <option value="major" {{ old('severity', $finding->severity) === 'major' ? 'selected' : '' }}>Major</option>
                            <option value="critical" {{ old('severity', $finding->severity) === 'critical' ? 'selected' : '' }}>Kritikal</option>
                        </select>
                        @if ($errors->has('severity'))
                            <p class="sim-form-error">{{ $errors->first('severity') }}</p>
                        @endif
                    </div>

                    <div class="sim-form-group">
                        <label class="sim-form-label">Tipe Temuan</label>
                        <select name="finding_type" class="sim-form-select {{ $errors->has('finding_type') ? 'border-sim-danger' : '' }}">
                            <option value="non_conformity" {{ old('finding_type', $finding->finding_type) === 'non_conformity' ? 'selected' : '' }}>Ketidaksesuaian</option>
                            <option value="observation" {{ old('finding_type', $finding->finding_type) === 'observation' ? 'selected' : '' }}>Observasi</option>
                            <option value="improvement" {{ old('finding_type', $finding->finding_type) === 'improvement' ? 'selected' : '' }}>Peluang Perbaikan</option>
                        </select>
                        @if ($errors->has('finding_type'))
                            <p class="sim-form-error">{{ $errors->first('finding_type') }}</p>
                        @endif
                    </div>
                </div>

                <div class="sim-form-group">
                    <label class="sim-form-label">Kondisi Aktual</label>
                    <textarea name="condition" rows="3" class="sim-form-textarea">{{ old('condition', $finding->condition) }}</textarea>
                    @if ($errors->has('condition'))
                        <p class="sim-form-error">{{ $errors->first('condition') }}</p>
                    @endif
                </div>

                <div class="sim-form-group">
                    <label class="sim-form-label">Persyaratan</label>
                    <textarea name="requirement" rows="3" class="sim-form-textarea">{{ old('requirement', $finding->requirement) }}</textarea>
                    @if ($errors->has('requirement'))
                        <p class="sim-form-error">{{ $errors->first('requirement') }}</p>
                    @endif
                </div>

                <div class="sim-form-group">
                    <label class="sim-form-label">Rekomendasi</label>
                    <textarea name="recommendation" rows="3" class="sim-form-textarea">{{ old('recommendation', $finding->recommendation) }}</textarea>
                    @if ($errors->has('recommendation'))
                        <p class="sim-form-error">{{ $errors->first('recommendation') }}</p>
                    @endif
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sim-form-group">
                        <label class="sim-form-label">PIC Penanggung Jawab</label>
                        <select name="owner_user_id" class="sim-form-select {{ $errors->has('owner_user_id') ? 'border-sim-danger' : '' }}">
                            <option value="">— Pilih PIC —</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" {{ old('owner_user_id', $finding->owner_user_id) === $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('owner_user_id'))
                            <p class="sim-form-error">{{ $errors->first('owner_user_id') }}</p>
                        @endif
                    </div>

                    <div class="sim-form-group">
                        <label class="sim-form-label">Tenggat</label>
                        <input type="date" name="due_at"
                               value="{{ old('due_at', $finding->due_at?->format('Y-m-d')) }}"
                               class="sim-form-input {{ $errors->has('due_at') ? 'border-sim-danger' : '' }}">
                        @if ($errors->has('due_at'))
                            <p class="sim-form-error">{{ $errors->first('due_at') }}</p>
                        @endif
                    </div>
                </div>

                <div class="flex justify-end pt-4 border-t border-slate-100">
                    <button type="submit" class="sim-btn sim-btn-primary">
                        Perbarui Temuan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
