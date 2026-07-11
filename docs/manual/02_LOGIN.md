# Login & Autentikasi

## 1. Kegunaan
Modul login mengamankan akses ke SIMMAK PPKP. Hanya pengguna aktif dengan kredensial valid yang dapat masuk. Saat ini memakai **login lokal** (LDAP AD ditunda).

## 2. Siapa yang Menggunakan
Semua role.

## 3. Cara Penggunaan

### 3.1 Masuk ke Sistem
1. Buka `http://127.0.0.1:8000/login` (atau URL server Anda).
2. Isi **Email** dan **Password**.
3. Klik **Masuk**.
4. Sistem memverifikasi kredensial, mencatat audit trail login, lalu mengarahkan ke halaman sesuai role.

### 3.2 Keluar dari Sistem
1. Klik ikon keluar di header kanan atas.
2. Sistem mencatat logout dan mengembalikan ke halaman login.

### 3.3 Tips
- Pada environment lokal, tabel akun demo tampil di halaman login.
- Password default akun demo: `password`.
- Jika gagal login, pastikan email benar dan user `is_active = true`.

## 4. Akses per Role
Semua role dapat login. Perbedaan hanya pada **halaman tujuan** dan **menu** setelah masuk (lihat dokumen Role & Akses).

## 5. API (opsional)
- `POST /api/v1/auth/login` — dapat token Sanctum
- `GET /api/v1/auth/me` — profil pengguna
- `POST /api/v1/auth/logout` — revoke token
