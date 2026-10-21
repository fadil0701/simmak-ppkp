# Dokumentasi Instalasi & Konfigurasi — SIMMAK Audit Internal Klinik

**Versi:** 1.0  
**Tanggal:** 19 Oktober 2026  
**Lingkungan:** Development (Laragon + MySQL) / Production (Docker + PostgreSQL 17)

---

## 1. Prasyarat

| Komponen | Versi Minimum | Catatan |
|----------|--------------|---------|
| PHP | 8.2+ | Teruji pada PHP 8.3.24 |
| Composer | 2.4+ | |
| Node.js / npm | 18+ | Untuk build asset frontend |
| MySQL | 8.0+ | Development (Laragon) |
| PostgreSQL | 17 | Production (Docker) |
| Redis | 7+ | Cache, queue, session |
| ClamAV | Opsional | Scan malware bukti audit |

---

## 2. Instalasi Development (Laragon + MySQL)

### 2.1 Clone dan Setup

```bash
# Masuk ke direktori proyek
cd E:\laragon\www\simmak-ppkp

# Install dependensi PHP
composer install --no-interaction --prefer-dist

# Install dependensi frontend
npm install

# Salin konfigurasi environment
cp .env.example .env
php artisan key:generate

# Buat database MySQL (via Laragon atau phpMyAdmin)
# Nama database: simmak_ppkp

# Jalankan migrasi
php artisan migrate

# Jalankan seeder (termasuk izin audit internal)
php artisan db:seed --force

# Build asset frontend
npm run build

# Jalankan server
php artisan serve
```

### 2.2 Konfigurasi Environment (.env)

```env
APP_NAME="SIMMAK PPKP"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

APP_LOCALE=id
APP_FALLBACK_LOCALE=id
APP_FAKER_LOCALE=id_ID

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=simmak_ppkp
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false

QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=log
```

### 2.3 Akun Demo (Seeder)

Password tidak tertulis di codebase. Diambil dari environment `DEMO_ACCOUNT_PASSWORD`;
jika kosong, seeder membuat password acak dan mencetaknya sekali ke console saat seeding.

| Username | Email | Role |
|----------|-------|------|
| admin | admin@simmak.ppkp.local | super-admin |
| mutu | mutu@simmak.ppkp.local | admin-mutu |
| kepala | kepala@simmak.ppkp.local | kepala-klinik |
| pjmutu | pjmutu@simmak.ppkp.local | pj-mutu |
| auditor | auditor@simmak.ppkp.local | auditor |
| staff | staff@simmak.ppkp.local | staff |

---

## 3. Instalasi Production (Docker + PostgreSQL 17)

### 3.1 Setup Docker

```bash
# Siapkan satu berkas .env (dipakai juga oleh Docker Compose)
cp .env.example .env
# Edit .env: ganti DB_PASSWORD, jalankan `php artisan key:generate`, dll.

# Build dan jalankan stack
docker compose build app
docker compose up -d

# Build asset frontend
docker compose --profile tools run --rm assets

# Jalankan migrasi dan seeder
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
```

### 3.2 Konfigurasi .env (production)

Cukup satu berkas `.env`. Nilai khusus container (`DB_HOST=postgres`,
`REDIS_HOST=redis`, `CLAMAV_HOST=clamav`, `APP_URL`) sudah ditimpa otomatis oleh
`docker-compose.yml`.

```env
APP_NAME="SIMMAK PPKP"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost:8080

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=simmak_ppkp
DB_USERNAME=simmak
DB_PASSWORD=GANTI_DENGAN_PASSWORD_AMAN

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

CLAMAV_HOST=clamav
CLAMAV_PORT=3310

MAIL_MAILER=smtp
```

> Untuk produksi HTTPS, set `SESSION_SECURE_COOKIE=true` dan `APP_DEBUG=false`.

### 3.3 ClamAV (Opsional)

ClamAV dijalankan sebagai service terpisah di Docker Compose. Untuk produksi dengan firewall eksternal (mis. Fortinet), ClamAV dapat diabaikan — upload bukti akan tetap diterima dengan catatan `clamav-unavailable` di metadata.

---

## 4. Konfigurasi RBAC dan Permission

Permission diautomatisasi melalui seeder `AuditPermissionSeeder`. Role yang tersedia:

| Role | Permission Kunci |
|------|-----------------|
| `super-admin` | Semua permission |
| `admin-mutu` | Semua kecuali manajemen user/settings |
| `pj-mutu` | Program, assignment, execution, finding, action, evidence, report (manage scope) |
| `auditor` | Execution, finding, action, evidence (own scope) |
| `kepala-klinik` | View scope untuk klinik yang ditugaskan |
| `staff` | View dasar |

Untuk menambah permission baru, edit `database/seeders/AuditPermissionSeeder.php` lalu jalankan:
```bash
php artisan db:seed --class=AuditPermissionSeeder --force
```

Untuk reset cache permission:
```bash
php artisan permission:cache-reset
```

---

## 5. Konfigurasi Audit Trail

Audit trail dicatat secara otomatis pada:
- Pembuatan, pengajuan, persetujuan, dan penolakan program audit
- Aktivasi dan pengajuan penugasan auditor
- Mulai, serah terima, dan finalisasi eksekusi audit
- Simpan respons checklist
- Pembuatan, review, penutupan, dan pembukaan kembali temuan
- Pembuatan CAPA dari temuan
- Simpan analisis akar masalah dan verifikasi CAPA
- Generate dan hapus laporan audit
- Karantina bukti audit

Model `AuditTrail` dilindungi dari update dan delete melalui antarmuka pengguna (append-only).

---

## 6. Konfigurasi Storage Bukti Audit

Berkas bukti disimpan di:
- `storage/app/private/audit_evidence/{organization_id}/{clinic_id}/` — bukti audit
- `storage/app/private/quarantine/{organization_id}/{date}/` — bukti yang dikarantinakan (malware/extension palsu)
- `storage/app/private/reports/{organization_id}/` — file laporan PDF dan Excel

Unduhan bukti hanya melalui route terotorisasi (`audit.evidence.download`).

---

## 7. Troubleshooting

| Masalah | Solusi |
|---------|--------|
| Migration gagal (PostgreSQL) | Pastikan service postgres healthy: `docker compose ps` |
| Test gagal (MySQL) | Pastikan MySQL (Laragon) running dan database `simmak_ppkp` ada |
| ClamAV tidak tersedia | Upload tetap diterima dengan catatan `clamav-unavailable` di metadata |
| Permission denied pada route | Jalankan `php artisan permission:cache-reset` |
| Asset tidak muncul | Jalankan `npm run build` atau `docker compose --profile tools run --rm assets` |

---

## 8. Backup & Restore

### Database (PostgreSQL)
```bash
# Backup
docker compose exec postgres pg_dump -U simmak simmak_ppkp > backup_$(date +%F).sql

# Restore
docker compose exec -T postgres psql -U simmak simmak_ppkp < backup_2026-10-19.sql
```

### File (Evidence & Reports)
```bash
# Backup
tar czf storage_backup_$(date +%F).tar.gz storage/app/private/

# Restore
tar xzf storage_backup_2026-10-19.tar.gz -C /var/www/html/
```
