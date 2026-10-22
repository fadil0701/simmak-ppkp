<?php

/**
 * Skrip bantu: mengganti tautan "Kembali" dan navigasi polos pada view
 * modul Audit & master data menjadi komponen <x-action> (ikon + teks ringkas).
 *
 * Jalankan: php scripts/normalize_action_links.php
 */
$base = dirname(__DIR__).'/resources/views';

$replacements = [
    'audit/assignments/create.blade.php' => [
        '<a href="{{ route(\'audit.assignments.index\') }}" class="text-sm font-medium text-teal-700 hover:underline">← Kembali ke Daftar Penugasan</a>' => '<x-action href="{{ route(\'audit.assignments.index\') }}" icon="chevron-left" label="Kembali" variant="ghost" size="sm" />',
    ],
    'audit/assignments/edit.blade.php' => [
        '<a href="{{ route(\'audit.assignments.show\', $assignment) }}" class="text-sm font-medium text-teal-700 hover:underline">← Kembali ke Detail Penugasan</a>' => '<x-action href="{{ route(\'audit.assignments.show\', $assignment) }}" icon="chevron-left" label="Kembali" variant="ghost" size="sm" />',
    ],
    'audit/evidence/create.blade.php' => [
        '<a href="{{ route(\'audit.evidence.index\') }}" class="text-sm font-medium text-teal-700 hover:underline">← Kembali ke Daftar Bukti</a>' => '<x-action href="{{ route(\'audit.evidence.index\') }}" icon="chevron-left" label="Kembali" variant="ghost" size="sm" />',
    ],
    'audit/findings/create.blade.php' => [
        '<a href="{{ route(\'findings.index\') }}" class="text-sm font-medium text-teal-700 hover:underline">← Kembali ke Daftar Temuan</a>' => '<x-action href="{{ route(\'findings.index\') }}" icon="chevron-left" label="Kembali" variant="ghost" size="sm" />',
    ],
    'audit/findings/edit.blade.php' => [
        '<a href="{{ route(\'findings.show\', $finding) }}" class="text-sm font-medium text-teal-700 hover:underline">← Kembali ke Detail Temuan</a>' => '<x-action href="{{ route(\'findings.show\', $finding) }}" icon="chevron-left" label="Kembali" variant="ghost" size="sm" />',
    ],
    'audit/dashboard/index.blade.php' => [
        '<a href="{{ route(\'audit.programs.index\') }}" class="text-sm font-medium text-sim-primary hover:underline">Semua Program →</a>' => '<x-action href="{{ route(\'audit.programs.index\') }}" icon="clipboard-list" label="Semua Program" variant="ghost" size="sm" class="mt-3" />',
        '<a href="{{ route(\'findings.index\') }}" class="text-sm font-medium text-sim-primary hover:underline">Lihat Semua →</a>' => '<x-action href="{{ route(\'findings.index\') }}" icon="chevron-right" label="Lihat Semua" variant="ghost" size="sm" />',
        '<a href="{{ route(\'findings.index\') }}?severity=critical" class="mt-3 inline-block text-sm font-medium text-sim-primary hover:underline">Lihat semua temuan →</a>' => '<x-action href="{{ route(\'findings.index\', [\'severity\' => \'critical\']) }}" icon="chevron-right" label="Lihat semua temuan" variant="ghost" size="sm" class="mt-3" />',
    ],
];

foreach ($replacements as $file => $pairs) {
    $path = $base.'/'.$file;
    if (! file_exists($path)) {
        echo "LEWATI (tidak ada): {$file}\n";

        continue;
    }

    $content = file_get_contents($path);
    $original = $content;

    foreach ($pairs as $search => $replace) {
        $content = str_replace($search, $replace, $content);
    }

    if ($content !== $original) {
        file_put_contents($path, $content);
        echo "DIPERBARUI: {$file}\n";
    } else {
        echo "TIDAK BERUBAH: {$file}\n";
    }
}
