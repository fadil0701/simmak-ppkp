@extends('layouts.simmak')

@section('title', 'Self Assessment — SIMMAK PPKP')
@section('page-title', 'Self Assessment Akreditasi')

@section('content')
    <div class="mb-4">
        <a href="{{ route('accreditation.index') }}" class="text-sm font-medium text-teal-700 hover:underline">← Kembali ke Dashboard Akreditasi</a>
    </div>

    @if (! $assessment)
        <div class="simmak-card p-8 text-center text-slate-600">Belum ada data Self Assessment.</div>
    @else
        <div class="mb-6 grid gap-4 sm:grid-cols-3">
            <div class="simmak-card p-5">
                <div class="text-sm text-slate-500">Skor Keseluruhan</div>
                <div class="text-4xl font-bold text-teal-700">{{ (int) $assessment->overall_score }}%</div>
            </div>
            <div class="simmak-card p-5">
                <div class="text-sm text-slate-500">Tanggal Assessment</div>
                <div class="text-lg font-semibold">{{ $assessment->assessment_date->format('d M Y') }}</div>
            </div>
            <div class="simmak-card p-5">
                <div class="text-sm text-slate-500">Assessor</div>
                <div class="text-lg font-semibold">{{ $assessment->assessor?->name }}</div>
                <span class="simmak-badge mt-1 bg-blue-100 text-blue-800">{{ ucfirst($assessment->status) }}</span>
            </div>
        </div>

        @foreach ($itemsByChapter as $chapterNo => $items)
            <div class="simmak-card mb-4">
                <div class="simmak-card-header">
                    <h3 class="font-semibold text-slate-800">Bab {{ $chapterNo }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-slate-100 bg-slate-50 text-xs uppercase text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Standar</th>
                                <th class="px-5 py-3">Judul</th>
                                <th class="px-5 py-3">Skor</th>
                                <th class="px-5 py-3">Kepatuhan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($items as $item)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-5 py-3 font-mono text-xs">{{ $item->standard?->standard_code }}</td>
                                    <td class="px-5 py-3">{{ $item->standard?->title }}</td>
                                    <td class="px-5 py-3 font-semibold">{{ (int) $item->score }}%</td>
                                    <td class="px-5 py-3">
                                        @php $cc = match($item->compliance_status) { 'compliant' => 'bg-emerald-100 text-emerald-800', 'partial' => 'bg-amber-100 text-amber-800', default => 'bg-red-100 text-red-800' }; @endphp
                                        <span class="simmak-badge {{ $cc }}">{{ str_replace('_', ' ', ucfirst($item->compliance_status)) }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    @endif
@endsection
