<?php
// Penentu warna tombol final.
//
// Berbeda dari skrip sebelumnya, warna ditentukan HANYA dari teks di dalam
// elemen (<a ...>TEKS</a> / <button ...>TEKS</button>), bukan dari teks
// setelah tag — sehingga label tombol sebelahnya tidak ikut terbaca.
//
// Skrip ini idempoten: aman dijalankan berulang.
//
// Pemakaian: php scripts/apply_button_palette.php [--dry-run]

$dryRun = in_array('--dry-run', $argv, true);

$colors = 'back|create|save|edit|delete|detail|approve|reject|neutral|upload|download|print';

// Urutan penting: frasa paling spesifik lebih dulu.
$rules = [
    'simpan perubahan' => 'save',
    'simpan pengaturan' => 'save',
    'simpan' => 'save',
    'kembali' => 'back',
    'batal' => 'neutral',
    'reset' => 'back',
    'filter' => 'neutral',
    'terapkan' => 'neutral',
    'tambah' => 'create',
    'catat' => 'create',
    'buat versi' => 'create',
    'buat' => 'create',
    'mulai' => 'create',
    'edit' => 'edit',
    'ubah' => 'edit',
    'isi tabel' => 'edit',
    'detail' => 'detail',
    'lihat' => 'detail',
    'hapus' => 'delete',
    'eskalasi' => 'delete',
    'setujui' => 'approve',
    'terbitkan' => 'approve',
    'ajukan' => 'approve',
    'aktifkan' => 'approve',
    'verifikasi' => 'approve',
    'tautkan' => 'approve',
    'nonaktifkan' => 'reject',
    'kembalikan' => 'reject',
    'tolak' => 'reject',
    'unduh' => 'download',
    'download' => 'download',
    'ekspor' => 'download',
    'export' => 'download',
    'template' => 'download',
    'impor' => 'upload',
    'unggah' => 'upload',
    'upload' => 'upload',
    'cetak' => 'print',
    'cari' => 'search',
];

$changed = 0;
$log = [];

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/../resources/views'));

foreach ($iterator as $file) {
    if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
        continue;
    }

    $path = $file->getPathname();
    $contents = file_get_contents($path);
    $original = $contents;

    $contents = preg_replace_callback(
        '/<(a|button)\b((?:[^>"\']|"[^"]*"|\'[^\']*\')*)>(.*?)<\/\1>/s',
        function (array $m) use ($rules, $colors) {
            [, $tag, $attrs, $inner] = $m;

            // Hanya tombol ber-class sim-btn (tombol mentah) atau yang akan diberi class.
            $hasBtnClass = preg_match('/class="[^"]*\bsim-btn\b[^"]*"/', $attrs);

            if (! $hasBtnClass && ! preg_match('/\b(href=|type="(submit|button)")/', $attrs)) {
                return $m[0];
            }

            // Teks di dalam elemen saja.
            $text = strtolower($inner);
            $text = preg_replace('/<svg\b.*?<\/svg>/s', ' ', $text);
            $text = preg_replace('/<[^>]*>/', ' ', $text);
            $text = preg_replace('/\{\{.*?\}\}/s', ' ', $text);
            $text = preg_replace('/@\w+/', ' ', $text);
            $text = preg_replace('/\s+/', ' ', $text);
            $text = trim($text);

            if ($text === '') {
                return $m[0];
            }

            $color = null;
            foreach ($rules as $needle => $candidate) {
                if (str_contains($text, $needle)) {
                    $color = $candidate;
                    break;
                }
            }

            if ($color === null) {
                return $m[0];
            }

            if ($hasBtnClass) {
                // Ganti hanya token warnanya; pertahankan kelas lain (ukuran, layout).
                $newAttrs = preg_replace(
                    '/class="([^"]*\bsim-btn\s+)sim-btn-('.$colors.')([^"]*)"/',
                    'class="$1sim-btn-'.$color.'$3"',
                    $attrs,
                    1,
                    $count
                );

                if ($count === 0) {
                    return $m[0];
                }

                return '<'.$tag.$newAttrs.'>'.$inner.'</'.$tag.'>';
            }

            // Tombol tanpa class sama sekali: sisipkan.
            return '<'.$tag.$attrs.' class="sim-btn sim-btn-'.$color.'">'.$inner.'</'.$tag.'>';
        },
        $contents
    );

    if ($contents !== $original) {
        if (! $dryRun) {
            file_put_contents($path, $contents);
        }
        $changed++;
        $log[] = basename($path);
    }
}

echo ($dryRun ? '[dry] ' : '').'berkas disesuaikan: '.$changed.PHP_EOL;
foreach ($log as $l) {
    echo '  '.$l.PHP_EOL;
}
