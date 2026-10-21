# SIMMAK PPKP — Environment Configuration Guide

SIMMAK PPKP memakai **satu berkas `.env`** untuk semua lingkungan. Template-nya
ada di `.env.example`. Docker Compose membaca `.env` yang sama dan hanya menimpa
nilai khusus container (`DB_HOST`, `REDIS_HOST`, `CLAMAV_HOST`, `APP_URL`) lewat
blok `environment:` di `docker-compose.yml`.

```bash
cp .env.example .env
php artisan key:generate
```

---

## Default: PostgreSQL 17 (Docker Compose)

Nilai default `.env.example` sudah menunjuk ke PostgreSQL Docker yang
dipublikasikan ke host pada port `5432`:

```env
APP_ENV=local
APP_DEBUG=true

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=simmak_ppkp
DB_USERNAME=simmak
DB_PASSWORD=simmak_secret
```

```bash
docker compose up -d          # jalankan postgres + redis (+ app bila sudah dibuild)
php artisan migrate:fresh --seed
```

---

## Opsi: MySQL (Laragon)

Jika lebih suka MySQL lokal, ubah lima baris di `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=simmak_ppkp
DB_USERNAME=root
DB_PASSWORD=
```

**Setup database MySQL:**
```sql
CREATE DATABASE simmak_ppkp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
php scripts/create_mysql_db.php   # opsional, membuat DB via CLI
php artisan migrate:fresh --seed
```

---

## Staging / Production (PostgreSQL 17 — VM/Docker)

```env
APP_ENV=production
APP_DEBUG=false

DB_CONNECTION=pgsql
DB_HOST=<host-postgres-vm>
DB_PORT=5432
DB_DATABASE=simmak_ppkp
DB_USERNAME=simmak
DB_PASSWORD=<secret>

SESSION_SECURE_COOKIE=true   # wajib untuk HTTPS
```

**Catatan PostgreSQL:**
- Aktifkan extension `pgcrypto` untuk `gen_random_uuid()`
- Kolom `json` di migration dapat di-upgrade ke `jsonb` di production jika diperlukan
- Backup harian sesuai `docs/ARCHITECTURE.md`

---

## Akun Demo

`RoleUsersSeeder` membaca password dari `DEMO_ACCOUNT_PASSWORD` (tidak ditulis di
codebase). Jika kosong, seeder membuat password acak dan mencetaknya sekali ke
console. Di `APP_ENV=production` tanpa `DEMO_ACCOUNT_PASSWORD`, seeder dilewati.

---

## Frontend

UI disajikan server-side dengan Blade + Tailwind dari `resources/views/`.
API backend: `/api/v1/*`.
