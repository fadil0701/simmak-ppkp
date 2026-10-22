@extends('layouts.simmak')

@section('title', $capa->case_number.' — SIMMAK PPKP')
@section('page-title', 'Detail CAPA')

@section('content')
    <div class="mb-4">
        <x-action href="{{ route('capa.index') }}" icon="chevron-left" label="Kembali" variant="ghost" size="sm" />
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="simmak-card lg:col-span-2">
            <div class="simmak-card-header">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-mono text-sm text-slate-500">{{ $capa->case_number }}</p>
                        <h2 class="text-lg font-bold text-slate-900">Kasus CAPA</h2>
                    </div>
                    <div class="flex gap-2">
                        <span class="simmak-badge {{ $capa->priority === 'critical' ? 'bg-red-100 text-red-800' : 'bg-orange-100 text-orange-800' }}">{{ \App\Support\UiLabel::capaPriority($capa->priority) }}</span>
                        <span class="simmak-badge bg-blue-100 text-blue-800">{{ \App\Support\UiLabel::capaStatus($capa->status) }}</span>
                    </div>
                </div>
            </div>
            <div class="simmak-card-body space-y-4">
                <div>
                    <span class="text-xs text-slate-500">Pernyataan Masalah</span>
                    <p class="mt-1 text-sm text-slate-700">{{ $capa->problem_statement }}</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><span class="text-xs text-slate-500">Klinik</span><p class="font-medium">{{ $capa->clinic?->name ?? '—' }}</p></div>
                    <div><span class="text-xs text-slate-500">Penanggung Jawab</span><p class="font-medium">{{ $capa->owner?->name ?? '—' }}</p></div>
                    <div><span class="text-xs text-slate-500">Sumber</span><p class="font-medium">{{ ucfirst(str_replace('_', ' ', $capa->source_type)) }}</p></div>
                    <div><span class="text-xs text-slate-500">Target Penutupan</span><p class="font-medium {{ $capa->target_close_date->isPast() && $capa->status !== 'closed' ? 'text-red-600' : '' }}">{{ $capa->target_close_date->format('d M Y') }}</p></div>
                </div>
                @if ($capa->sourceFinding)
                    <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                        <span class="text-xs font-medium text-slate-500">Temuan Audit Terkait</span>
                        <a href="{{ route('findings.show', $capa->sourceFinding) }}" class="mt-1 block font-medium text-teal-700 hover:underline">
                            {{ $capa->sourceFinding->finding_code }} — {{ $capa->sourceFinding->title }}
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <div class="simmak-card">
            <div class="simmak-card-header">
                <h3 class="text-sm font-semibold text-slate-700">Tindakan CAPA</h3>
            </div>
            <div class="simmak-card-body space-y-3">
                @forelse ($capa->actions as $action)
                    <div class="border-b border-slate-50 pb-3 last:border-0">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium uppercase text-slate-500">{{ str_replace('_', ' ', $action->action_type) }}</span>
                            <span class="simmak-badge bg-slate-100 text-slate-700">{{ ucfirst($action->status) }}</span>
                        </div>
                        <p class="mt-1 text-sm text-slate-700">{{ $action->description }}</p>
                        @if ($action->due_date)
                            <p class="mt-1 text-xs text-slate-500">Due: {{ $action->due_date->format('d M Y') }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Belum ada tindakan terdaftar.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="simmak-card">
            <div class="simmak-card-header">
                <h3 class="text-sm font-semibold text-slate-700">Analisis Akar Masalah (RCA)</h3>
            </div>
            <div class="simmak-card-body space-y-4">
                @foreach ($capa->rca as $analysis)
                    <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase text-slate-500">{{ str_replace('_', ' ', $analysis->method) }}</span>
                            <span class="text-xs text-slate-500">{{ $analysis->created_at->format('d M Y') }}</span>
                        </div>
                        @if ($analysis->method === 'five_why' && isset($analysis->content['whys']))
                            <ol class="mt-2 list-decimal space-y-1 pl-4 text-sm text-slate-700">
                                @foreach ($analysis->content['whys'] as $why)
                                    <li><strong>{{ $why['question'] ?? '' }}</strong> — {{ $why['answer'] ?? '' }}</li>
                                @endforeach
                            </ol>
                        @elseif ($analysis->method === 'fishbone' && isset($analysis->content['categories']))
                            @foreach ($analysis->content['categories'] as $cat)
                                <div class="mt-2">
                                    <div class="text-xs font-semibold text-slate-600">{{ $cat['name'] }}</div>
                                    <ul class="mt-1 list-disc pl-4 text-sm text-slate-700">
                                        @foreach ($cat['causes'] ?? [] as $cause)
                                            <li>{{ $cause }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        @endif
                        <p class="mt-2 text-sm font-medium text-teal-800">Akar masalah: {{ $analysis->content['root_cause'] ?? '—' }}</p>
                    </div>
                @endforeach

                <form method="POST" action="{{ route('capa.store-rca', $capa) }}" class="space-y-3 border-t border-slate-100 pt-4">
                    @csrf
                    <div class="sim-form-group">
                        <label class="sim-form-label">Metode RCA</label>
                        <select name="method" id="rca-method" class="sim-form-select" onchange="document.getElementById('five-why-fields').classList.toggle('hidden', this.value !== 'five_why'); document.getElementById('fishbone-fields').classList.toggle('hidden', this.value !== 'fishbone');">
                            <option value="five_why">5 Why</option>
                            <option value="fishbone">Fishbone (Ishikawa)</option>
                        </select>
                    </div>
                    <div id="five-why-fields" class="space-y-2">
                        @for ($i = 1; $i <= 5; $i++)
                            <div class="grid gap-2 sm:grid-cols-2">
                                <input type="text" name="whys[{{ $i - 1 }}][question]" placeholder="Pertanyaan Why #{{ $i }}"
                                       class="sim-form-input">
                                <input type="text" name="whys[{{ $i - 1 }}][answer]" placeholder="Jawaban"
                                       class="sim-form-input">
                            </div>
                        @endfor
                    </div>
                    <div id="fishbone-fields" class="hidden space-y-2">
                        @foreach (['Manusia', 'Metode', 'Mesin', 'Material'] as $idx => $catName)
                            <div class="sim-form-group">
                                <input type="hidden" name="categories[{{ $idx }}][name]" value="{{ $catName }}">
                                <label class="sim-form-label">{{ $catName }} (satu penyebab per baris)</label>
                                <textarea name="categories[{{ $idx }}][causes]" rows="2" class="sim-form-textarea" placeholder="Penyebab..."></textarea>
                            </div>
                        @endforeach
                    </div>
                    <div class="sim-form-group">
                        <label class="sim-form-label">Akar Masalah</label>
                        <textarea name="root_cause" rows="2" required class="sim-form-textarea"></textarea>
                    </div>
                    <x-action icon="check-circle" label="Simpan RCA" />
                </form>
            </div>
        </div>

        <div class="simmak-card">
            <div class="simmak-card-header">
                <h3 class="text-sm font-semibold text-slate-700">Verifikasi Efektivitas</h3>
            </div>
            <div class="simmak-card-body space-y-4">
                @foreach ($capa->verifications as $verification)
                    <div class="border-b border-slate-50 pb-3 last:border-0">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-slate-800">{{ $verification->verification_date->format('d M Y') }}</span>
                            <span class="simmak-badge {{ $verification->is_effective ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">{{ ucfirst(str_replace('_', ' ', $verification->result)) }}</span>
                        </div>
                        <p class="mt-1 text-sm text-slate-600">{{ $verification->notes ?? '—' }}</p>
                        <p class="text-xs text-slate-500">Verifier: {{ $verification->verifier?->name }}</p>
                    </div>
                @endforeach

                <form method="POST" action="{{ route('capa.store-verification', $capa) }}" class="space-y-3 border-t border-slate-100 pt-4">
                    @csrf
                    <div class="sim-form-group">
                        <label class="sim-form-label">Tanggal Verifikasi</label>
                        <input type="date" name="verification_date" value="{{ now()->toDateString() }}" required
                               class="sim-form-input">
                    </div>
                    <div class="sim-form-group">
                        <label class="sim-form-label">Hasil</label>
                        <select name="result" required class="sim-form-select">
                            <option value="pending">Pending</option>
                            <option value="effective">Efektif</option>
                            <option value="not_effective">Tidak Efektif</option>
                        </select>
                    </div>
                    <div class="sim-form-group">
                        <label class="sim-form-label">Catatan</label>
                        <textarea name="notes" rows="2" class="sim-form-textarea"></textarea>
                    </div>
                    <x-action icon="check-circle" label="Catat Verifikasi" />
                </form>
            </div>
        </div>
    </div>
@endsection
