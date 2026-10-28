@extends('layouts.simmak')

@section('title', 'Impor EP — ' . $standard->code)
@section('page-title', 'Impor Elemen Penilaian')

@php
    $hasPreview = ! empty($rows);
    $currentStep = $hasPreview ? 3 : (count($parseErrors) || count($invalid) || count($duplicates) ? 2 : 1);
@endphp

@section('content')
    <div class="mx-auto max-w-6xl space-y-5">
        <div class="sim-page-header">
            <div>
                <h2 class="sim-page-title">Impor Elemen Penilaian</h2>
                <p class="sim-page-subtitle">
                    Standar <span class="sim-code">{{ $standard->code }}</span> — {{ $standard->title }}
                </p>
            </div>
            <a href="{{ route('audit.standards.show', $standard) }}" class="sim-btn sim-btn-back">
                Kembali ke Standar
            </a>
        </div>

        {{-- Indikator langkah --}}
        <ol class="grid gap-3 sm:grid-cols-3" aria-label="Langkah impor">
            @foreach ([
                1 => ['judul' => 'Unduh & Isi Template', 'ket' => 'Gunakan format resmi agar terbaca sistem'],
                2 => ['judul' => 'Upload Berkas', 'ket' => 'Sistem memeriksa isi sebelum menyimpan'],
                3 => ['judul' => 'Periksa & Simpan', 'ket' => 'Tinjau pratinjau lalu konfirmasi'],
            ] as $step => $info)
                @php $state = $currentStep > $step ? 'done' : ($currentStep === $step ? 'active' : 'idle'); @endphp
                <li class="flex items-start gap-3 rounded-lg border p-3
                           {{ $state === 'active' ? 'border-sim-navy-800 bg-sim-active' : 'border-sim-border-light bg-sim-white' }}">
                    <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold
                                 {{ $state === 'done' ? 'bg-sim-success text-white' : ($state === 'active' ? 'bg-sim-navy-800 text-white' : 'bg-sim-surface-alt text-sim-muted') }}">
                        {{ $state === 'done' ? '✓' : $step }}
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-medium text-sim-ink">{{ $info['judul'] }}</span>
                        <span class="block text-xs leading-relaxed text-sim-muted">{{ $info['ket'] }}</span>
                    </span>
                </li>
            @endforeach
        </ol>

        {{-- LANGKAH 1: template + panduan --}}
        <x-form-section step="1" title="Unduh Template &amp; Pahami Kolomnya"
                        description="Template sudah berisi contoh baris yang benar. Timpa contoh dengan data EP Anda, lalu simpan sebagai .xlsx atau .csv.">
            <div class="flex flex-wrap items-center gap-3">
                <x-action action="download" :href="route('audit.standards.import.template')" icon="download"
                          label="Unduh Template Excel" />
                <span class="text-xs text-sim-muted">Format: .xlsx (disarankan) atau .csv</span>
            </div>

            <div class="overflow-x-auto rounded-lg border border-sim-border-light">
                <table class="sim-table text-sm">
                    <thead class="bg-sim-surface-alt">
                        <tr>
                            <th class="px-3 py-2">Nama Kolom</th>
                            <th class="px-3 py-2">Wajib</th>
                            <th class="px-3 py-2">Penjelasan</th>
                            <th class="px-3 py-2">Contoh Isi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ([
                            ['Kode Standar', true, 'Pengelompokan EP. Boleh dipakai berulang untuk banyak EP.', 'STANDAR 1.1'],
                            ['Kode EP', true, 'Kode unik EP di dalam satu kode standar.', 'EP 1'],
                            ['Elemen Penilaian', true, 'Redaksi resmi EP yang dinilai auditor.', 'Terdapat kebijakan tertulis…'],
                            ['Kelengkapan Bukti', false, 'Daftar bukti yang harus diperiksa. Pisahkan tiap bukti dengan baris baru (Alt+Enter di Excel).', 'Kebijakan yang disahkan'],
                            ['Panduan Penilaian', false, 'Cara menentukan nilai sesuai / sebagian / tidak sesuai.', 'Nilai sesuai bila…'],
                            ['Skor Maksimal', false, 'Angka. Kosongkan untuk memakai nilai bawaan 10.', '10'],
                        ] as [$kolom, $wajib, $keterangan, $contoh])
                            <tr class="border-t border-sim-border-light align-top">
                                <td class="px-3 py-2 font-medium text-sim-ink">{{ $kolom }}</td>
                                <td class="px-3 py-2">
                                    <span class="sim-badge sim-badge-status {{ $wajib ? 'border-sim-danger bg-sim-danger-bg text-sim-danger' : 'border-sim-neutral bg-sim-neutral-bg text-sim-neutral' }}">
                                        {{ $wajib ? 'Wajib' : 'Opsional' }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-sim-muted">{{ $keterangan }}</td>
                                <td class="px-3 py-2 font-mono text-xs text-sim-muted">{{ $contoh }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="sim-alert sim-alert-info">
                <span class="sim-alert-icon">i</span>
                <span>
                    Urutan kolom <strong>bebas</strong> — sistem mengenali kolom dari judulnya.
                    Judul kolom juga menerima padanan umum seperti <span class="sim-code">Standar</span>,
                    <span class="sim-code">EP</span>, <span class="sim-code">Bukti</span>, dan
                    <span class="sim-code">Skor</span>, sehingga berkas lama tetap bisa diimpor.
                </span>
            </div>
        </x-form-section>

        {{-- LANGKAH 2: upload --}}
        <x-form-section step="2" title="Upload Berkas yang Sudah Diisi"
                        description="Berkas tidak langsung disimpan — sistem menampilkan pratinjau dan daftar masalah lebih dulu.">
            <form method="POST" action="{{ route('audit.standards.import.preview', $standard) }}"
                  enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div class="rounded-lg border-2 border-dashed border-sim-border bg-sim-surface-alt p-6 text-center">
                    <label for="import-file" class="cursor-pointer">
                        <span class="block text-sm font-medium text-sim-ink">Pilih berkas impor</span>
                        <span class="mt-1 block text-xs text-sim-muted">Format .xlsx, .xls, .csv, atau .txt — maksimal 10 MB</span>
                        <input id="import-file" type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required
                               class="sim-form-input mt-3 mx-auto max-w-md text-sm">
                    </label>
                    @error('file')
                        <p class="sim-form-error mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end">
                    <x-action action="detail" icon="eye" label="Periksa Berkas" />
                </div>
            </form>
        </x-form-section>

        {{-- HASIL PEMERIKSAAN --}}
        @if (count($parseErrors) > 0)
            <div class="sim-alert sim-alert-error">
                <span class="sim-alert-icon">!</span>
                <div>
                    <p class="font-semibold">Berkas belum bisa diproses</p>
                    <ul class="mt-1 list-disc pl-5 text-sm">
                        @foreach ($parseErrors as $parseError)
                            <li>{{ $parseError }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @if (count($invalid) > 0)
            <div class="sim-card">
                <div class="sim-card-header">
                    <h3 class="sim-card-title text-sim-danger">Baris Bermasalah ({{ count($invalid) }})</h3>
                </div>
                <div class="sim-card-body overflow-x-auto">
                    <p class="mb-3 text-sm text-sim-muted">
                        Baris berikut tidak akan diimpor. Perbaiki lalu unggah ulang berkas.
                    </p>
                    <table class="sim-table text-sm">
                        <thead class="bg-sim-surface-alt">
                            <tr>
                                <th class="px-3 py-2 w-20">Baris</th>
                                <th class="px-3 py-2">Penyebab</th>
                                <th class="px-3 py-2">Isi Berkas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invalid as $row)
                                <tr class="border-t border-sim-border-light align-top">
                                    <td class="px-3 py-2 font-mono text-xs">{{ $row['baris'] }}</td>
                                    <td class="px-3 py-2 text-sim-danger">{{ $row['pesan'] }}</td>
                                    <td class="px-3 py-2 text-xs text-sim-muted">
                                        {{ \Illuminate\Support\Str::limit(implode(' | ', array_filter($row['data'] ?? [])), 160) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if (count($duplicates) > 0)
            <div class="sim-card">
                <div class="sim-card-header">
                    <h3 class="sim-card-title text-sim-warning">Duplikat terdeteksi ({{ count($duplicates) }})</h3>
                </div>
                <div class="sim-card-body">
                    <p class="mb-3 text-sm text-sim-muted">
                        Impor diblokir selama masih ada EP ganda agar instrumen tidak berisi data rangkap.
                        Hapus baris tersebut dari berkas atau gunakan kode EP lain.
                    </p>
                    <ul class="list-disc space-y-1 pl-5 text-sm text-sim-ink">
                        @foreach ($duplicates as $duplicate)
                            <li>{{ $duplicate }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        {{-- LANGKAH 3: pratinjau & simpan --}}
        @if ($hasPreview)
            @php
                $seksiCount = collect($rows)->pluck('kode_standar')->unique()->count();
                $buktiCount = collect($rows)->filter(fn ($r) => ($r['kelengkapan_bukti'] ?? '') !== '')->count();
            @endphp

            <x-form-section step="3" title="Pratinjau &amp; Konfirmasi"
                            description="Periksa hasil pembacaan. Jika sudah benar, simpan untuk menambahkan EP ke instrumen standar ini.">
                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="rounded-lg border border-sim-border-light bg-sim-white p-3">
                        <div class="text-xs uppercase tracking-wide text-sim-muted">EP akan ditambahkan</div>
                        <div class="mt-1 text-2xl font-semibold text-sim-ink">{{ count($rows) }}</div>
                    </div>
                    <div class="rounded-lg border border-sim-border-light bg-sim-white p-3">
                        <div class="text-xs uppercase tracking-wide text-sim-muted">Seksi Standar</div>
                        <div class="mt-1 text-2xl font-semibold text-sim-ink">{{ $seksiCount }}</div>
                    </div>
                    <div class="rounded-lg border border-sim-border-light bg-sim-white p-3">
                        <div class="text-xs uppercase tracking-wide text-sim-muted">EP dengan Bukti</div>
                        <div class="mt-1 text-2xl font-semibold text-sim-ink">{{ $buktiCount }}</div>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-lg border border-sim-border-light">
                    <table class="sim-table text-sm">
                        <thead class="bg-sim-surface-alt">
                            <tr>
                                <th class="px-3 py-2 w-12">#</th>
                                <th class="px-3 py-2">Kode Standar</th>
                                <th class="px-3 py-2">Kode EP</th>
                                <th class="px-3 py-2">Elemen Penilaian</th>
                                <th class="px-3 py-2">Kelengkapan Bukti</th>
                                <th class="px-3 py-2">Panduan Penilaian</th>
                                <th class="px-3 py-2 text-center">Skor Maks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $index => $row)
                                <tr class="border-t border-sim-border-light align-top">
                                    <td class="px-3 py-2 text-xs text-sim-muted">{{ $index + 1 }}</td>
                                    <td class="px-3 py-2 font-mono text-xs">{{ $row['kode_standar'] }}</td>
                                    <td class="px-3 py-2 font-mono text-xs">{{ $row['kode_ep'] }}</td>
                                    <td class="px-3 py-2 text-sim-ink">{{ $row['elemen_penilaian'] }}</td>
                                    <td class="px-3 py-2 text-xs text-sim-muted whitespace-pre-line">{{ $row['kelengkapan_bukti'] ?: '—' }}</td>
                                    <td class="px-3 py-2 text-xs text-sim-muted whitespace-pre-line">{{ $row['panduan_penilaian'] ?: '—' }}</td>
                                    <td class="px-3 py-2 text-center text-xs">{{ $row['skor_maks'] !== '' ? $row['skor_maks'] : 10 }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="sim-save-bar">
                    <span class="text-sm text-sim-muted">
                        Berkas: <span class="font-medium text-sim-ink">{{ $fileName }}</span>
                    </span>
                    <form method="POST" action="{{ route('audit.standards.import.store', $standard) }}">
                        @csrf
                        <x-action action="save" icon="check-circle" :label="'Simpan ' . count($rows) . ' EP'" />
                    </form>
                </div>
            </x-form-section>
        @endif
    </div>
@endsection

