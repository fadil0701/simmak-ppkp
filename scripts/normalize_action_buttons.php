<?php
// Selaraskan seluruh pemakaian <x-action> ke palet aksi baku.
//
// Untuk setiap tag <x-action ...>:
//   1. baca icon + label (statis maupun dinamis),
//   2. tebak jenis aksi via App\Support\UiAction::guess(),
//   3. hapus atribut variant, tambahkan action="<jenis>".
//
// Pemakaian: php scripts/normalize_action_buttons.php [--dry-run]

$dryRun = in_array('--dry-run', $argv, true);

// Autoload agar App\Support\UiAction dapat dipakai tanpa boot Laravel.
require __DIR__.'/../vendor/autoload.php';

$changed = 0;
$skipped = [];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(__DIR__.'/../resources/views')
);

foreach ($iterator as $file) {
    if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
        continue;
    }

    $path = $file->getPathname();
    $contents = file_get_contents($path);
    $original = $contents;

    $contents = preg_replace_callback(
        '/<x-action\b((?:[^>"\']|"[^"]*"|\'[^\']*\')*)>/s',
        function (array $m) use (&$skipped, $file) {
            $attrs = $m[1];

            // Sudah memakai action eksplisit: biarkan.
            if (preg_match('/\s(?:action)=/', $attrs)) {
                return $m[0];
            }

            $icon = null;
            if (preg_match('/\sicon="([^"]*)"/', $attrs, $i)) {
                $icon = $i[1];
            }

            $label = null;
            if (preg_match('/\slabel="([^"]*)"/', $attrs, $l)) {
                $label = $l[1];
            } elseif (preg_match('/\s:label="([^"]*)"/', $attrs, $l)) {
                $label = $l[1];
            }

            $action = \App\Support\UiAction::guess($icon, $label);

            if ($action === null) {
                $skipped[] = basename($file->getFilename()).' :: '.trim($m[0]);

                return $m[0];
            }

            // Buang variant manual (warna kini ditentukan aksi).
            $clean = preg_replace('/\s+variant="[^"]*"/', '', $attrs);

            return '<x-action action="'.$action.'"'.$clean.'>';
        },
        $contents
    );

    if ($contents !== $original) {
        if (! $dryRun) {
            file_put_contents($path, $contents);
        }
        $changed++;
        echo ($dryRun ? '[dry] ' : '').basename($path).PHP_EOL;
    }
}

echo PHP_EOL.'berkas diubah: '.$changed.PHP_EOL;

if ($skipped !== []) {
    echo PHP_EOL.'TIDAK DIKENALI (perlu review manual): '.count($skipped).PHP_EOL;
    foreach (array_slice($skipped, 0, 20) as $s) {
        echo '  '.$s.PHP_EOL;
    }
}
