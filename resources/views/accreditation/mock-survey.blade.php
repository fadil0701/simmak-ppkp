@extends('layouts.simmak')

@section('title', 'Survei Simulasi — SIMMAK PPKP')
@section('page-title', 'Hasil Survei Simulasi')

@section('content')
    <div class="mb-4">
        <a href="{{ route('accreditation.index') }}" class="text-sm font-medium text-teal-700 hover:underline">← Kembali ke Dashboard Akreditasi</a>
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
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Tanggal</th>
                            <th class="px-5 py-3">Skor</th>
                            <th class="px-5 py-3">Surveor</th>
                            <th class="px-5 py-3">Catatan</th>
                            <th class="px-5 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($surveys as $survey)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-3">{{ $survey->survey_date?->format('d M Y') ?? '—' }}</td>
                                <td class="px-5 py-3 font-semibold text-teal-700">{{ $survey->score }}%</td>
                                <td class="px-5 py-3 text-slate-600">{{ $survey->surveyor_name }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $survey->notes ?? '—' }}</td>
                                <td class="px-5 py-3"><span class="simmak-badge bg-blue-100 text-blue-800">{{ \App\Support\UiLabel::auditStatus($survey->status) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-8 text-center text-slate-500">Belum ada data survei simulasi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="simmak-card p-8 text-center text-slate-600">Belum ada program akreditasi aktif.</div>
    @endif
@endsection
