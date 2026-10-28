@extends('layouts.simmak')

@section('title', 'Self Assessment — SIMMAK PPKP')
@section('page-title', 'Self Assessment Akreditasi')

@section('content')
    <div class="mb-4">
        <a href="{{ route('accreditation.index') }}" class="text-sm font-medium text-teal-700 hover:underline" class="sim-btn sim-btn-back">← Kembali ke Dashboard Akreditasi</a>
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
                    <table class="sim-table">
                        <thead>
                            <tr>
                                <th>Standar</th>
                                <th>Judul</th>
                                <th>Skor</th>
                                <th>Kepatuhan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                <tr>
                                    <td class="font-mono text-xs">{{ $item->standard?->standard_code }}</td>
                                    <td>{{ $item->standard?->title }}</td>
                                    <td class="font-semibold">{{ (int) $item->score }}%</td>
                                    <td>
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
