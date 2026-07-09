@extends('layouts.simmak')

@section('title', 'Standar Akreditasi — SIMMAK PPKP')
@section('page-title', 'Standar Akreditasi')

@section('content')
    <div class="mb-4">
        <a href="{{ route('accreditation.index') }}" class="text-sm font-medium text-teal-700 hover:underline">← Kembali ke Dashboard Akreditasi</a>
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
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-slate-100 bg-slate-50 text-xs uppercase text-slate-500">
                                <tr>
                                    <th class="px-5 py-3">Kode</th>
                                    <th class="px-5 py-3">Standar</th>
                                    <th class="px-5 py-3">Deskripsi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($chapter->standards as $standard)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-5 py-3 font-mono text-xs font-medium text-teal-700">{{ $standard->standard_code }}</td>
                                        <td class="px-5 py-3 font-medium text-slate-800">{{ $standard->title }}</td>
                                        <td class="px-5 py-3 text-slate-600">{{ \Illuminate\Support\Str::limit($standard->description, 120) }}</td>
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
