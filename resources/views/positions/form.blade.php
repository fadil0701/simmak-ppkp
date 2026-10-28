@extends('layouts.simmak')

@php
    $isCreate = is_null($position);
    $title = $isCreate ? 'Tambah Jabatan' : 'Edit Jabatan';
    $route = $isCreate ? route('positions.store') : route('positions.update', $position);
@endphp

@section('title', $title.' — SIMMAK PPKP')
@section('page-title', $title)

@section('content')
    <div class="mb-4">
        <x-action action="back" href="{{ route('positions.index') }}" icon="chevron-left" label="Kembali" size="sm" class="mb-4" />
    </div>

    <div class="sim-card max-w-2xl">
        <div class="sim-card-header">
            <h3 class="sim-card-title">{{ $title }}</h3>
        </div>
        <div class="sim-card-body">
            <form method="POST" action="{{ $route }}" class="space-y-5">
                @csrf
                @if (! $isCreate)
                    @method('PUT')
                @endif

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sim-form-group">
                        <label class="sim-form-label" for="code">Kode Jabatan <span class="sim-form-label-required"></span></label>
                        <input type="text" id="code" name="code" maxlength="50"
                               value="{{ old('code', $position?->code) }}" required
                               class="sim-form-input {{ $errors->has('code') ? 'border-sim-danger' : '' }}"
                               placeholder="Contoh: JBT-DOKTER">
                        @error('code')<p class="sim-form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="sim-form-group">
                        <label class="sim-form-label" for="category">Kategori <span class="sim-form-label-required"></span></label>
                        <select id="category" name="category" required class="sim-form-select {{ $errors->has('category') ? 'border-sim-danger' : '' }}">
                            @foreach (\App\Http\Controllers\PositionController::CATEGORIES as $value => $label)
                                <option value="{{ $value }}" @selected(old('category', $position?->category) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('category')<p class="sim-form-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="sim-form-group">
                    <label class="sim-form-label" for="name">Nama Jabatan <span class="sim-form-label-required"></span></label>
                    <input type="text" id="name" name="name" maxlength="150"
                           value="{{ old('name', $position?->name) }}" required
                           class="sim-form-input {{ $errors->has('name') ? 'border-sim-danger' : '' }}"
                           placeholder="Contoh: Perawat">
                    @error('name')<p class="sim-form-error">{{ $message }}</p>@enderror
                    <p class="sim-form-hint">Jabatan tidak memberi hak akses aplikasi. Role pengguna diatur di menu Pengguna.</p>
                </div>

                <div class="sim-form-group">
                    <label class="sim-form-label" for="description">Keterangan</label>
                    <textarea id="description" name="description" rows="3"
                              class="sim-form-textarea {{ $errors->has('description') ? 'border-sim-danger' : '' }}"
                              placeholder="Deskripsi singkat jabatan">{{ old('description', $position?->description) }}</textarea>
                    @error('description')<p class="sim-form-error">{{ $message }}</p>@enderror
                </div>

                <div class="sim-form-group">
                    <label class="flex items-center gap-2 text-sm font-medium text-sim-ink">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $position?->is_active ?? true))
                               class="h-4 w-4 rounded border-sim-border text-sim-primary focus:ring-sim-primary">
                        Jabatan aktif
                    </label>
                </div>

                <div class="flex justify-end gap-3 border-t border-sim-border-light pt-4">
                    <a href="{{ route('positions.index') }}" class="sim-btn sim-btn-neutral">Batal</a>
                    <x-action action="edit" icon="check-circle" :label="$isCreate ? 'Simpan Jabatan' : 'Simpan Perubahan'" />
                </div>
            </form>
        </div>
    </div>
@endsection
