<?php

namespace App\Support;

class RoleLabel
{
    public static function for(?string $role): string
    {
        return match ($role) {
            'super-admin' => 'Direktur Utama',
            'admin-mutu' => 'Admin Mutu PPKP',
            'kepala-klinik' => 'Kepala Klinik',
            'pj-mutu' => 'Penanggung Jawab Mutu',
            'tim-akreditasi' => 'Tim Akreditasi',
            'auditor' => 'Auditor Internal',
            'staff' => 'Staff Klinik',
            default => 'Pengguna',
        };
    }
}
