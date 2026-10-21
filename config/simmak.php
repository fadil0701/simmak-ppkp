<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Akun Super Admin / Administrator Sistem
    |--------------------------------------------------------------------------
    |
    | Kredensial akun super-admin (administrator sistem). Nilai ini TIDAK
    | boleh ditulis di codebase — isi melalui environment di berkas .env.
    | Jika password kosong, seeder membuat password acak dan mencetaknya
    | sekali ke console saat seeding. Jangan pernah mengisi nilai tetap di sini.
    |
    */

    'superadmin_email' => env('SUPERADMIN_EMAIL', 'admin@simmak.ppkp.local'),
    'superadmin_name' => env('SUPERADMIN_NAME', 'Administrator SIMMAK'),
    'superadmin_username' => env('SUPERADMIN_USERNAME', 'admin'),
    'superadmin_password' => env('SUPERADMIN_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | Password Default Akun User (selain super admin)
    |--------------------------------------------------------------------------
    |
    | Dipakai oleh RoleUsersSeeder untuk seluruh akun demo non-superadmin
    | (admin-mutu, kepala-klinik, pj-mutu, tim-akreditasi, auditor, staff).
    | Jika kosong, jatuh ke DEMO_ACCOUNT_PASSWORD; bila keduanya kosong,
    | seeder membuat password acak dan mencetaknya sekali ke console.
    |
    */

    'default_user_password' => env('DEFAULT_USER_PASSWORD', env('DEMO_ACCOUNT_PASSWORD')),

    /*
    |--------------------------------------------------------------------------
    | Password Akun Demo (lama / kompatibilitas)
    |--------------------------------------------------------------------------
    |
    | Dipertahankan sebagai fallback tunggal untuk semua akun demo bila
    | DEFAULT_USER_PASSWORD / SUPERADMIN_PASSWORD tidak diisi.
    |
    */

    'demo_account_password' => env('DEMO_ACCOUNT_PASSWORD'),

];
