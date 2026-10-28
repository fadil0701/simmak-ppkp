@extends('layouts.simmak')

@section('title', 'Survei Simulasi — SIMMAK PPKP')
@section('page-title', 'Hasil Survei Simulasi')

@section('content')
    <div class="mb-4">
        <a href="{{ route('accreditation.index') }}" class="text-sm font-medium text-teal-700 hover:underline" class="sim-btn sim-btn-back">← Kembali ke Dashboard Akreditasi</a>
    </div>

    @if ($program)
        <div class="mb-6 grid gap-4 sm:grid-cols-3">
            <div class="simmak-card p-5">
                <div class="text-sm text-slate-500">Skor Survei Simulasi</div>
                <div class="mt-1 text-3xl font-bold text-teal-700">{{ $program->latestMockSurvey?->score ?? 0 }}%</div>
            </div>
            <div class="simmak-card p-5">
                <div class="text-sm text-slate-500">Tanggal Survei</div>
                <div class="mt-1 text-lg font-semibold">{{ $program->latestMockSurvey?->survey_date?->format('d M Y') ?? '—' }}</div>
            </div>
            <div class="simmak-card p-5">
                <div class="text-sm text-slate-500">Surveor</div>
                <div class="mt-1 text-lg font-semibold">{{ $program->latestMockSurvey?->surveyor_name ?? '—' }}</div>
            </div>
        </div>

        <div class="simmak-card">
            <div class="simmak-card-header">
                <h3 class="font-semibold text-slate-800">Riwayat Survei Simulasi</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="sim-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Skor</th>
                            <th>Surveor</th>
                            <th>Catatan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($surveys as $survey)
                            <tr>
                                <td>{{ $survey->survey_date?->format('d M Y') ?? '—' }}</td>
                                <td class="font-semibold text-teal-700">{{ $survey->score }}%</td>
                                <td class="text-slate-600">{{ $survey->surveyor_name }}</td>
                                <td class="text-slate-600">{{ $survey->notes ?? '—' }}</td>
                                <td><span class="simmak-badge bg-blue-100 text-blue-800">{{ \App\Support\UiLabel::auditStatus($survey->status) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="sim-table-empty">Belum ada data survei simulasi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="simmak-card p-8 text-center text-slate-600">Belum ada program akreditasi aktif.</div>
    @endif
@endsection
