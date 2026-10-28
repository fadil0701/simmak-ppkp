<?php

namespace App\Support;

/**
 * Palet aksi baku SIMMAK.
 *
 * Warna dan ikon tombol ditentukan oleh JENIS AKSI, bukan pilihan bebas tiap view.
 * Semua tombol aksi sebaiknya memakai komponen <x-action action="..."> sehingga
 * satu aksi selalu tampil dengan warna yang sama di seluruh aplikasi.
 *
 * Pemakaian di Blade:
 *   <x-action action="create" :href="route('x.create')" label="Tambah Data" />
 *   <x-action action="delete" :href="route('x.destroy', $item)" method="DELETE" label="Hapus" />
 *   <x-action action="back" :href="route('x.index')" label="Kembali" />
 *
 * Menambah aksi baru: tambahkan entri di ACTIONS dan kelas .sim-btn-<kunci>
 * pada resources/css/app.css.
 */
class UiAction
{
    /**
     * Katalog aksi: kunci => [ikon, label bawaan, warna].
     *
     * Warna mengacu pada kelas CSS .sim-btn-<warna> yang didefinisikan di app.css.
     */
    public const ACTIONS = [
        'back' => ['icon' => 'chevron-left', 'label' => 'Kembali', 'color' => 'back'],
        'create' => ['icon' => 'plus', 'label' => 'Tambah', 'color' => 'create'],
        'save' => ['icon' => 'check-circle', 'label' => 'Simpan', 'color' => 'save'],
        'edit' => ['icon' => 'pencil', 'label' => 'Edit', 'color' => 'edit'],
        'delete' => ['icon' => 'trash', 'label' => 'Hapus', 'color' => 'delete'],
        'detail' => ['icon' => 'file-text', 'label' => 'Detail', 'color' => 'detail'],
        'approve' => ['icon' => 'check-circle', 'label' => 'Setujui', 'color' => 'approve'],
        'reject' => ['icon' => 'x-mark', 'label' => 'Tolak', 'color' => 'reject'],
        'cancel' => ['icon' => 'x-mark', 'label' => 'Batal', 'color' => 'neutral'],
        'upload' => ['icon' => 'upload', 'label' => 'Upload', 'color' => 'upload'],
        'download' => ['icon' => 'download', 'label' => 'Unduh', 'color' => 'download'],
        'import' => ['icon' => 'upload', 'label' => 'Impor', 'color' => 'upload'],
        'export' => ['icon' => 'download', 'label' => 'Ekspor', 'color' => 'download'],
        'print' => ['icon' => 'file-text', 'label' => 'Cetak', 'color' => 'print'],
        'search' => ['icon' => 'search', 'label' => 'Cari', 'color' => 'neutral'],
        'start' => ['icon' => 'play-circle', 'label' => 'Mulai', 'color' => 'create'],
        'escalate' => ['icon' => 'alert-triangle', 'label' => 'Eskalasi', 'color' => 'delete'],
        'navigate' => ['icon' => null, 'label' => null, 'color' => 'back'],
        'menu' => ['icon' => 'grid', 'label' => null, 'color' => 'back'],
    ];

    public static function has(string $action): bool
    {
        return isset(self::ACTIONS[$action]);
    }

    /**
     * Kelas CSS untuk sebuah aksi, termasuk kelas dasar dan ukuran.
     */
    public static function classes(string $action, string $size = 'md'): string
    {
        $color = self::ACTIONS[$action]['color'] ?? 'back';

        $classes = 'sim-btn sim-btn-'.$color;

        if ($size === 'sm') {
            $classes .= ' sim-btn-sm';
        } elseif ($size === 'icon') {
            $classes .= ' sim-btn-icon';
        }

        return $classes;
    }

    public static function icon(string $action): ?string
    {
        return self::ACTIONS[$action]['icon'] ?? null;
    }

    public static function label(string $action): ?string
    {
        return self::ACTIONS[$action]['label'] ?? null;
    }

    /**
     * Tebak aksi dari ikon + teks tombol.
     *
     * Dipakai untuk menyelaraskan tombol lama yang belum memakai prop action.
     * Pencocokan teks didahulukan karena satu ikon bisa dipakai beberapa aksi
     * (mis. check-circle untuk "Simpan" dan "Setujui").
     */
    public static function guess(?string $icon, ?string $label): ?string
    {
        $text = strtolower(trim((string) $label));

        $byLabel = [
            'kembali' => 'back',
            'batal' => 'cancel',
            'nonaktifkan' => 'cancel',
            'kembalikan' => 'reject',
            'tolak' => 'reject',
            'hapus' => 'delete',
            'edit' => 'edit',
            'ubah' => 'edit',
            'detail' => 'detail',
            'lihat' => 'detail',
            'simpan' => 'save',
            'tambah' => 'create',
            'buat' => 'create',
            'setujui' => 'approve',
            'aktifkan' => 'approve',
            'verifikasi' => 'approve',
            'konfirmasi' => 'approve',
            'unggah' => 'upload',
            'upload' => 'upload',
            'impor' => 'import',
            'unduh' => 'download',
            'download' => 'download',
            'ekspor' => 'export',
            'cetak' => 'print',
            'cari' => 'search',
        ];

        foreach ($byLabel as $needle => $action) {
            if ($text !== '' && str_contains($text, $needle)) {
                return $action;
            }
        }

        $byIcon = [
            'chevron-left' => 'back',
            'chevron-right' => null,
            'plus' => 'create',
            'check-circle' => 'save',
            'pencil' => 'edit',
            'trash' => 'delete',
            'file-text' => 'detail',
            'eye' => 'detail',
            'x-mark' => 'cancel',
            'upload' => 'upload',
            'download' => 'download',
            'search' => 'search',
        ];

        return $byIcon[$icon] ?? null;
    }
}
