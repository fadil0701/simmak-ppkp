@extends('layouts.simmak')

@section('title', 'Standar Akreditasi — SIMMAK PPKP')
@section('page-title', 'Standar Akreditasi')

@section('content')
    <div class="mb-4">
        <a href="{{ route('accreditation.index') }}" class="text-sm font-medium text-teal-700 hover:underline" class="sim-btn sim-btn-back">← Kembali ke Dashboard Akreditasi</a>
    </div>

    @if (! $program)
        <div class="simmak-card p-8 text-center text-slate-500">Belum ada program akreditasi aktif.</div>
    @else
        <div class="mb-6 simmak-card p-5">
            <h2 class="font-semibold text-slate-800">{{ $program->type?->name ?? 'Standar Akreditasi' }}</h2>
            <p class="text-sm text-slate-600">{{ $program->type?->issuing_body ?? 'KARS' }} · {{ $clinic?->name ?? $program->clinic?->name }}</p>
            <p class="mt-2 text-xs text-slate-500">Daftar baca-saja bab dan standar akreditasi.</p>
        </div>

        <div class="space-y-4">
            @forelse ($chapters as $chapter)
                <div class="simmak-card">
                    <div class="simmak-card-header">
                        <h3 class="font-semibold text-slate-800">Bab {{ $chapter->chapter_number }} — {{ $chapter->title }}</h3>
                        @if ($chapter->description)
                            <p class="mt-1 text-sm text-slate-600">{{ $chapter->description }}</p>
                        @endif
                    </div>
                    <div class="overflow-x-auto">
                        <table class="sim-table">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Standar</th>
                                    <th>Deskripsi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($chapter->standards as $standard)
                                    <tr>
                                        <td class="font-mono text-xs font-medium text-teal-700">{{ $standard->standard_code }}</td>
                                        <td class="font-medium text-slate-800">{{ $standard->title }}</td>
                                        <td class="text-slate-600">{{ \Illuminate\Support\Str::limit($standard->description, 120) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="px-5 py-4 text-center text-slate-500">Tidak ada standar dalam bab ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="simmak-card p-8 text-center text-slate-500">Belum ada data standar akreditasi.</div>
            @endforelse
        </div>
    @endif
@endsection
