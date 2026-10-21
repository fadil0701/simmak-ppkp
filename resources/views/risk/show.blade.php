@extends('layouts.simmak')

@section('title', $risk->risk_code . ' — SIMMAK PPKP')
@section('page-title', 'Detail Risiko')

@section('content')
    @php $riskService = app(\App\Services\RiskService::class); @endphp

    @if (session('status'))
        <div class="sim-alert sim-alert-success mb-4"><span class="sim-alert-icon">!</span>{{ session('status') }}</div>
    @endif

    <div class="mb-4">
        <a href="{{ route('risk.index') }}" class="text-sm font-medium text-teal-700 hover:underline">← Kembali ke Manajemen Risiko</a>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="simmak-card p-5">
            <div class="text-sm text-slate-500">Level Risiko</div>
            <span class="simmak-badge mt-2 inline-block {{ $riskService->riskLevelColor($risk->risk_level) }}">
                {{ $riskService->riskLevelLabel($risk->risk_level) }}
            </span>
        </div>
        <div class="simmak-card p-5">
            <div class="text-sm text-slate-500">Skor Inherent</div>
            <div class="text-3xl font-bold text-red-600">{{ $risk->inherent_score }}</div>
            <div class="text-xs text-slate-500">L{{ $risk->inherent_likelihood }} × I{{ $risk->inherent_impact }}</div>
        </div>
        <div class="simmak-card p-5">
            <div class="text-sm text-slate-500">Skor Residual</div>
            <div class="text-3xl font-bold text-amber-600">{{ $risk->residual_score }}</div>
            <div class="text-xs text-slate-500">L{{ $risk->residual_likelihood }} × I{{ $risk->residual_impact }}</div>
        </div>
        <div class="simmak-card p-5">
            <div class="text-sm text-slate-500">Tinjauan Berikutnya</div>
            <div class="mt-1 text-lg font-semibold">{{ $risk->review_date?->format('d M Y') ?? '—' }}</div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 simmak-card">
            <div class="simmak-card-header">
                <div class="font-mono text-xs text-slate-500">{{ $risk->risk_code }}</div>
                <h2 class="text-xl font-bold text-slate-800">{{ $risk->title }}</h2>
            </div>
            <div class="simmak-card-body space-y-4">
                <div>
                    <h4 class="text-sm font-semibold text-slate-700">Deskripsi Risiko</h4>
                    <p class="mt-1 text-sm text-slate-600">{{ $risk->risk_description ?? '—' }}</p>
                </div>
                @if ($risk->risk_source)
                    <div>
                        <h4 class="text-sm font-semibold text-slate-700">Sumber Risiko</h4>
                        <p class="mt-1 text-sm text-slate-600">{{ $risk->risk_source }}</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="simmak-card">
            <div class="simmak-card-header"><h3 class="font-semibold text-slate-800">Informasi</h3></div>
            <div class="simmak-card-body space-y-3 text-sm">
                <div><span class="text-slate-500">Register</span><div class="font-medium">{{ $risk->register?->name }}</div></div>
                <div><span class="text-slate-500">Kategori</span><div class="font-medium">{{ $risk->category?->name }}</div></div>
                <div><span class="text-slate-500">Klinik</span><div class="font-medium">{{ $risk->clinic?->name ?? 'Organisasi' }}</div></div>
                <div><span class="text-slate-500">Pemilik</span><div class="font-medium">{{ $risk->owner?->name ?? '—' }}</div></div>
                <div><span class="text-slate-500">Status</span><div><span class="simmak-badge bg-blue-100 text-blue-800">{{ \App\Support\UiLabel::riskStatus($risk->status) }}</span></div></div>
            </div>
        </div>
    </div>

    <div class="mt-6 simmak-card">
        <div class="simmak-card-header">
            <h3 class="font-semibold text-slate-800">Rencana Mitigasi</h3>
        </div>
        <div class="simmak-card-body space-y-4">
            @forelse ($risk->mitigationPlans as $plan)
                <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <p class="text-sm text-slate-700">{{ $plan->plan_description }}</p>
                        <span class="simmak-badge bg-blue-100 text-blue-800">{{ ucfirst(str_replace('_', ' ', $plan->status)) }}</span>
                    </div>
                    <div class="mt-2 flex flex-wrap gap-4 text-xs text-slate-500">
                        <span>PIC: {{ $plan->owner?->name ?? '—' }}</span>
                        <span>Target: {{ $plan->target_date?->format('d M Y') ?? '—' }}</span>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">Belum ada rencana mitigasi.</p>
            @endforelse

            <form method="POST" action="{{ route('risk.store-mitigation', $risk) }}" class="space-y-3 border-t border-slate-100 pt-4">
                @csrf
                <div>
                    <label class="sim-form-label">Deskripsi Rencana Mitigasi<span class="sim-form-label-required"></span></label>
                    <textarea name="plan_description" rows="3" required class="sim-form-textarea"></textarea>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="sim-form-label">Target Tanggal</label>
                        <input type="date" name="target_date" class="sim-form-input">
                    </div>
                    <div>
                        <label class="sim-form-label">Status<span class="sim-form-label-required"></span></label>
                        <select name="status" required class="sim-form-select">
                            @foreach (['planned', 'in_progress', 'completed', 'cancelled'] as $s)
                                <option value="{{ $s }}">{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <button type="submit" class="sim-btn sim-btn-primary">Tambah Mitigasi</button>
            </form>
        </div>
    </div>
@endsection
