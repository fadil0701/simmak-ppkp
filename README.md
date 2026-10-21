# SIMMAK PPKP — Panduan Implementasi

Sistem Informasi Manajemen Mutu, Audit Internal dan Akreditasi untuk PPKP Provinsi DKI Jakarta.

## Stack

| Layer | Development | Production |
|-------|-------------|------------|
| Backend | Laravel 12 + PHP 8.3+ | Laravel 12 + PHP 8.4 |
| Database | **MySQL 8** (Laragon) | **PostgreSQL 17** (VM/Docker) |
| Frontend | Blade + Tailwind CSS (mockup SIMMAK) | Same |
| Auth | Spatie Permission + Sanctum | + LDAP AD DKI |
| Cache/Queue | Database | Redis |

## Dokumentasi Progres

📋 **[PROGRESS_TIMELINE.md](docs/PROGRESS_TIMELINE.md)** — Timeline, status fase, metrik progres  
📋 **[ENVIRONMENT.md](docs/ENVIRONMENT.md)** — Konfigurasi MySQL dev / PostgreSQL prod  
📘 **[Panduan Pengguna](docs/manual/00_INDEX.md)** — Fitur, cara pakai, role & akses (PDF di `pdf/manual/`)

### Generate PDF Panduan Pengguna

```bash
python scripts/generate_manual_pdfs.py
# Output: pdf/manual/*.pdf
```

## Fase Implementasi

| Fase | Modul | Status |
|------|-------|--------|
| 0 | Blueprint enterprise | ✅ Selesai |
| 1 | Core Platform, Auth, RBAC, 17 klinik, akun demo | ✅ Selesai |
| 2 | DMS + Workflow + QR verifikasi | ✅ Selesai |
| 3 | Audit Internal + CAPA + RCA | ✅ Selesai |
| 4 | Risk + Quality Management | ✅ Selesai |
| 5 | Accreditation (FKTP/FKTL) + dashboard mockup | ✅ Selesai |
| 6 | Dashboard Enterprise + Export | ✅ Selesai |
| 7 | Optimasi, Redis prod, DR drill | ⬜ Belum dimulai |

## Akun Demo

Akun demo dibuat oleh seeder. Password **tidak tertulis di codebase** — diambil dari
environment `DEMO_ACCOUNT_PASSWORD`. Jika tidak di-set, seeder membuat password acak
dan mencetaknya sekali ke console saat `php artisan db:seed` dijalankan.

| Email | Role | Halaman utama |
|-------|------|---------------|
| `admin@simmak.ppkp.local` | super-admin | `/dashboard` |
| `mutu@simmak.ppkp.local` | admin-mutu | `/dashboard` |
| `kepala@simmak.ppkp.local` | kepala-klinik | `/clinic/dashboard` |
| `pjmutu@simmak.ppkp.local` | pj-mutu | `/clinic/dashboard` |
| `akreditasi@simmak.ppkp.local` | tim-akreditasi | `/accreditation` |
| `auditor@simmak.ppkp.local` | auditor | `/audit` |
| `staff@simmak.ppkp.local` | staff | `/dms` |

```bash
php artisan db:seed --class=RoleUsersSeeder --force
```

## Setup Development

**Satu berkas `.env` untuk semua lingkungan** — Docker Compose memakai `.env` yang
sama dan hanya menimpa nilai khusus container lewat `docker-compose.yml`.

```bash
# 1. Environment
cp .env.example .env
php artisan key:generate

# 2. Database
#    Default .env = PostgreSQL 17 Docker (127.0.0.1:5432).
#    Untuk MySQL Laragon: jalankan `php scripts/create_mysql_db.php`
#    lalu ubah DB_CONNECTION=mysql, DB_HOST=127.0.0.1, DB_PORT=3306,
#    DB_USERNAME=root, DB_PASSWORD= di .env.

# 3. Migration & seed
php artisan migrate:fresh --seed

# 4. Jalankan (satu server)
npm install && npm run build
php artisan serve
# Buka http://127.0.0.1:8000/login
```

**Login default:** `admin@simmak.ppkp.local` / password dari `DEMO_ACCOUNT_PASSWORD` (lihat output seeder)

### API Endpoints

| Method | Endpoint | Keterangan |
|--------|----------|------------|
| POST | `/api/v1/auth/login` | Login, dapat token |
| GET | `/api/v1/auth/me` | Profil user (Bearer token) |
| POST | `/api/v1/auth/logout` | Revoke token |
| GET | `/api/v1/dashboard/executive` | Data dashboard eksekutif |
| GET | `/api/v1/dms/documents` | Daftar dokumen |
| POST | `/api/v1/dms/documents` | Buat dokumen |

## Frontend

UI Blade + Tailwind mengacu mockup di `mockups/`. Lihat `docs/FRONTEND_INTEGRATION.md`.

## Docker Lokal (PostgreSQL 17)

Stack lengkap: Nginx + PHP-FPM 8.4 + PostgreSQL 17 + Redis + queue worker + scheduler.
Memakai berkas `.env` yang sama; nilai khusus container (`DB_HOST=postgres`,
`REDIS_HOST=redis`, `CLAMAV_HOST=clamav`, `APP_URL`) ditimpa otomatis oleh
`docker-compose.yml`.

```bash
docker compose build app
docker compose up -d
docker compose --profile tools run --rm assets        # build asset Vite
docker compose exec app gosu www-data php artisan db:seed --force
# Buka http://localhost:8080
```

Rincian service, akun demo, dan troubleshooting: **[DEPLOY_DOCKER.md](docs/DEPLOY_DOCKER.md)**

## Production (VM Linux + PostgreSQL)

Lihat `docs/ENVIRONMENT.md` dan `docs/ARCHITECTURE.md`.

## Struktur Proyek

```
SIMMAK-PPKP/
├── app/                    # Laravel backend
├── public/                 # Assets Vite build
├── resources/views/        # UI Blade (mockup SIMMAK)
├── diagrams/               # ERD, DFD, BPMN
├── mockups/                # Referensi desain dashboard
├── docker/                 # Docker production
└── pdf/                    # Export dokumen blueprint
```

## Dokumen Acuan

- `docs/BRD.md` — Business requirements & roadmap
- `docs/PRD.md` — Product requirements per modul
- `docs/DDD.md` — Database design (104 tabel)
- `docs/ARCHITECTURE.md` — Arsitektur teknis & deployment
- `docs/UIUX.md` — Design system
- `docs/PROGRESS_TIMELINE.md` — **Progres & timeline pembangunan**
- `docs/manual/` — **Panduan pengguna per fitur (Markdown)**
- `pdf/manual/` — **PDF panduan pengguna per fitur + matriks role**

## Perintah Berguna

```bash
php artisan migrate:fresh --seed   # Reset database
php artisan permission:cache-reset # Reset cache RBAC
php artisan route:list             # Daftar route
composer app                       # php artisan serve
```
