# Panduan Instalasi dan Konfigurasi — SIMMAK PPKP

## 1. Prasyarat

| Komponen | Versi |
|----------|-------|
| PHP | 8.2+ |
| Composer | 2.0+ |
| Node.js | 18+ |
| NPM | 8+ |
| Database | MySQL 8 (dev) atau PostgreSQL 17 (prod) |

## 2. Instalasi Pengembangan (Laragon + MySQL)

```bash
# 1. Clone repository
cd E:\laragon\www
git clone <repository-url> simmak-ppkp
cd simmak-ppkp

# 2. Install dependensi PHP
composer install

# 3. Install dependensi JavaScript
npm install

# 4. Buat database MySQL
php scripts/create_mysql_db.php

# 5. Salin dan konfigurasikan environment
cp .env.example .env
php artisan key:generate

# Edit .env dan sesuaikan kredensial database:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=simmak_ppkp
# DB_USERNAME=root
# DB_PASSWORD=

# 6. Jalankan migrasi dan seeder
php artisan migrate:fresh --seed

# 7. Build aset frontend
npm run build

# 8. Jalankan server
php artisan serve
```

Akses aplikasi di `http://localhost:8000`.

## 3. Instalasi Produksi (Docker + PostgreSQL)

```bash
# 1. Build image aplikasi
docker compose build app

# 2. Jalankan stack
docker compose up -d

# 3. Build aset frontend (via profile tools)
docker compose --profile tools run --rm assets

# 4. Jalankan migrasi dan seeder
docker compose exec app gosu www-data php artisan migrate --force
docker compose exec app gosu www-data php artisan db:seed --class=DatabaseSeeder

# 5. Reset cache permission
docker compose exec app gosu www-data php artisan permission:cache-reset
```

Docker Compose memakai berkas `.env` yang sama dengan pengembangan lokal. Nilai khusus container (`DB_HOST=postgres`, `REDIS_HOST=redis`, `CLAMAV_HOST=clamav`, `APP_URL`) ditimpa otomatis lewat blok `environment:` di `docker-compose.yml`, jadi tidak perlu berkas env terpisah untuk Docker.

## 4. Konfigurasi Environment

Variabel kunci di `.env`:

| Variabel | Deskripsi | Contoh |
|----------|-----------|--------|
| `APP_NAME` | Nama aplikasi | `SIMMAK PPKP` |
| `APP_ENV` | Lingkungan | `local` / `production` |
| `APP_DEBUG` | Debug mode | `true` / `false` |
| `APP_URL` | URL aplikasi | `https://simmak.pkp.jakarta.go.id` |
| `DB_CONNECTION` | Driver database | `mysql` atau `pgsql` |
| `DB_HOST` | Host database | `127.0.0.1` |
| `DB_PORT` | Port database | `3306` atau `5432` |
| `DB_DATABASE` | Nama database | `simmak_ppkp` |
| `DB_USERNAME` | Username database | `root` |
| `DB_PASSWORD` | Password database | `` |
| `FILESYSTEM_DISK` | Disk penyimpanan | `local` atau `s3` |
| `AWS_ACCESS_KEY_ID` | Kunci akses object storage | (kosong untuk lokal) |
| `AWS_SECRET_ACCESS_KEY` | Rahasia object storage | (kosong untuk lokal) |
| `AWS_BUCKET` | Nama bucket | `simmak-ppkp` |
| `AWS_ENDPOINT` | Endpoint S3-kompatibel | (kosong untuk AWS standar) |
| `MAIL_MAILER` | Driver email | `log` (dev), `smtp` (prod) |
| `SESSION_DRIVER` | Driver session | `database` |
| `LDAP_ENABLED` | Aktifkan LDAP | `false` (dev) |

Jangan menyimpan `.env` yang berisi rahasia dalam version control.

## 5. Perintah Berguna

```bash
# Jalankan server + queue + logs + Vite (development)
composer dev

# Jalankan hanya server
php artisan serve

# Jalankan migration dari database bersih
php artisan migrate:fresh

# Jalankan seeder demonstrasi
php artisan db:seed --class=DatabaseSeeder

# Reset cache permission setelah mengubah role/permission
php artisan permission:cache-reset

# Jalankan tes
php artisan test
vendor/bin/phpunit --filter=<NamaTest>

# Jalankan Pint (code style)
vendor/bin/pint

# Build aset produksi
npm run build

# Hot reload aset (development)
npm run dev

# Generate manual PDF
python scripts/generate_manual_pdfs.py
```

## 6. Akun Demonstrasi

Setelah menjalankan seeder, akun berikut tersedia. Password tidak tertulis di
codebase — diambil dari `DEFAULT_USER_PASSWORD` (fallback lama `DEMO_ACCOUNT_PASSWORD`); jika kosong,
seeder membuat password acak dan mencetaknya sekali ke console saat seeding:

| Email | Username | Role | Keterangan |
|-------|----------|------|------------|
| `$SUPERADMIN_EMAIL` (default `admin@simmak.ppkp.local`) | `admin` | super-admin | Akses penuh; dibuat `php artisan simmak:create-superadmin` |
| sistem@simmak.ppkp.local | adminsistem | admin-sistem | Administrasi teknis sistem |
| kepalapusat@simmak.ppkp.local | kepalapusat | kepala-pusat | Monitoring lintas klinik |
| tu@simmak.ppkp.local | kasubbagtu | kasubbag-tu | Administrasi umum & kepegawaian |
| mutu@simmak.ppkp.local | ketuamutu | ketua-tim-mutu (legacy) | Manajemen mutu dan audit |
| ketuaaudit@simmak.ppkp.local | ketuaaudit | ketua-tim-audit (legacy) | Ketua tim audit internal |
| akreditasi@simmak.ppkp.local | akreditasi | ketua-tim-akreditasi (legacy) | Akreditasi |
| auditor@simmak.ppkp.local | auditor | auditor (legacy) | Auditor internal |
| kepala@simmak.ppkp.local | kepala | kepala-klinik | Akses klinik sendiri (KP06) |
| staff@simmak.ppkp.local | auditi | auditi (legacy) | Auditi klinik (KP06) |

Peran legacy tetap ada di database tetapi tidak dapat dipilih untuk akun baru
(`SimmakRoles::legacyRoleLabels()`); fungsi audit kini ditandai lewat Scope Sistem
(`auditor` / `auditi`).

Akun super admin **tidak** dibuat oleh seeder. Jalankan sekali setelah seeding:

```bash
php artisan simmak:create-superadmin
```

Perintah membaca `SUPERADMIN_NAME`, `SUPERADMIN_USERNAME`, `SUPERADMIN_EMAIL`, dan
`SUPERADMIN_PASSWORD` dari `.env`; bila password kosong, perintah membuat password
acak dan mencetaknya sekali. Akun ini tidak terikat klinik (lintas klinik) dan
memperoleh seluruh permission yang ada.

**Peringatan**: Akun demonstrasi tidak boleh dibiarkan aktif tanpa pengamanan di produksi. Ganti password dan nonaktifkan akun yang tidak diperlukan.

## 7. Pemeliharaan

- Backup database secara teratur (mysqldump untuk MySQL, pg_dump untuk PostgreSQL)
- Monitor log aplikasi via `php artisan pail` atau file log di `storage/logs`
- Rotate log secara berkala
- Perbarui dependensi Composer dan npm secara teratur
- Periksa status verifikasi regulasi sebelum produksi (lihat docs/REGULATION_VERIFICATION.md)