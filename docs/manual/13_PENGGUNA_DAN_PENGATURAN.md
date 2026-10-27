# Pengguna & Pengaturan

## 1. Kegunaan
- **Pengguna**: mengelola akun, role, dan klinik terkait.
- **Pengaturan**: konfigurasi organisasi (nama, kontak, opsi sistem).

## 2. Route
| Halaman | URL | Permission |
|---------|-----|------------|
| Daftar pengguna | `/users` | `users.view` |
| Tambah pengguna | `/users/create` | `users.manage` |
| Pengaturan | `/settings` | `settings.view` / `settings.manage` |

## 3. Siapa yang Menggunakan
| Fitur | Role yang boleh |
|-------|-----------------|
| Pengguna | **super-admin** saja |
| Pengaturan | super-admin, admin-mutu |

## 4. Cara Pengisian — Pengguna
1. Login sebagai `admin@simmak.ppkp.local`.
2. Buka **Pengguna**.
3. Klik **Tambah**, isi nama, email, username, password, role, klinik.
4. Simpan. Pengguna baru dapat login sesuai role.

## 5. Cara Pengisian — Pengaturan
1. Buka **Pengaturan**.
2. Perbarui data organisasi yang diizinkan form.
3. Simpan. Perubahan berlaku untuk label/konteks organisasi.

## 6. Seed Ulang Akun Demo
```bash
php artisan db:seed --class=RoleUsersSeeder --force
```
Password akun demo: dari environment `DEFAULT_USER_PASSWORD` (fallback
`DEMO_ACCOUNT_PASSWORD`) atau hasil acak seeder (dicetak sekali di console) —
tidak ada di codebase. Akun super admin dibuat terpisah:
`php artisan simmak:create-superadmin` (kredensial `SUPERADMIN_*` di `.env`).
```
