@extends('layouts.simmak')

@section('title', 'Impor EP — ' . $standard->code)
@section('page-title', 'Impor Elemen Penilaian')

@section('content')
    <div class="max-w-5xl mx-auto space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="sim-page-title">{{ $standard->code }} — {{ $standard->title }}</h2>
                <p class="sim-page-subtitle">Impor daftar EP dari berkas Excel/CSV.</p>
            </div>
            <a href="{{ route('audit.standards.show', $standard) }}" class="sim-btn sim-btn-outline sim-btn-sm">Kembali</a>
        </div>

        @if (session('status'))
            <div class="sim-alert sim-alert-success">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="sim-alert sim-alert-error">{{ session('error') }}</div>
        @endif

        <div class="sim-card">
            <div class="sim-card-header">Unggah Berkas</div>
            <div class="sim-card-body space-y-3">
                <form method="POST" action="{{ route('audit.standards.import.preview', $standard) }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <input type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required class="sim-form-input">
                    <p class="text-xs text-sim-muted">
                        Format kolom: <strong>Kode Standar</strong>, <strong>Kode EP</strong>, <strong>Elemen Penilaian</strong>,
                        <strong>Kelengkapan Bukti</strong>, <strong>Skor Maksimal</strong> (opsional, default 10).
                    </p>
                    <x-action icon="eye" label="Lihat Pratinjau" />
                </form>
            </div>
        </div>

        @if ($errors)
            <div class="sim-alert sim-alert-error">
                <ul class="list-disc pl-5">
                    @foreach ($errors as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($invalid)
            <div class="sim-alert sim-alert-error">
                <p class="font-semibold mb-1">Baris tidak valid ({{ count($invalid) }}):</p>
                <ul class="list-disc pl-5 text-sm">
                    @foreach ($invalid as $row)
                        <li>Baris {{ $row['baris'] }} — {{ $row['pesan'] }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($duplicates)
            <div class="sim-alert sim-alert-error">
                <p class="font-semibold mb-1">Duplikat terdeteksi ({{ count($duplicates) }}):</p>
                <ul class="list-disc pl-5 text-sm">
                    @foreach ($duplicates as $duplicate)
                        <li>{{ $duplicate }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($rows)
            <div class="sim-card">
                <div class="sim-card-header flex items-center justify-between">
                    <h3 class="sim-card-title">Pratinjau ({{ count($rows) }} baris valid)</h3>
                    <span class="text-xs text-sim-muted">{{ $fileName }}</span>
                </div>
                <div class="sim-card-body p-0 overflow-x-auto">
                    <table class="sim-table text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-3 py-2">Kode Standar</th>
                                <th class="px-3 py-2">Kode EP</th>
                                <th class="px-3 py-2">Elemen Penilaian</th>
                                <th class="px-3 py-2">Kelengkapan Bukti</th>
                                <th class="px-3 py-2 text-center">Skor Maks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr class="border-t border-sim-border-light align-top">
                                    <td class="px-3 py-2 font-mono text-xs">{{ $row['kode_standar'] }}</td>
                                    <td class="px-3 py-2 font-mono text-xs">{{ $row['kode_ep'] }}</td>
                                    <td class="px-3 py-2">{{ $row['elemen_penilaian'] }}</td>
                                    <td class="px-3 py-2 text-xs text-sim-muted">{{ $row['kelengkapan_bukti'] }}</td>
                                    <td class="px-3 py-2 text-center">{{ $row['skor_maks'] !== '' ? $row['skor_maks'] : 10 }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="sim-card-footer flex justify-end">
                    <form method="POST" action="{{ route('audit.standards.import.store', $standard) }}">
                        @csrf
                        <x-action icon="check-circle" :label="'Simpan '.count($rows).' EP'" />
                    </form>
                </div>
            </div>
        @endif
    </div>
@endsection
