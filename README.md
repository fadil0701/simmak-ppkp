# SIMMAK PPKP — Panduan Implementasi

Sistem Informasi Manajemen Mutu, Audit Internal dan Akreditasi untuk PPKP Provinsi DKI Jakarta.

## Stack

| Layer | Development | Production |
|-------|-------------|------------|
| Backend | Laravel 12 + PHP 8.3+ | Laravel 12 + PHP 8.4 |
| Database | **MySQL 8** (Laragon) | **PostgreSQL 17** (VM/Docker) |
| Frontend | **React Material Dashboard** (`react-dashboard-material-1.0.0/`) | Same |
| Auth | Spatie Permission + Sanctum | + LDAP AD DKI |
| Cache/Queue | Database | Redis |

## Dokumentasi Progres

📋 **[PROGRESS_TIMELINE.md](docs/PROGRESS_TIMELINE.md)** — Timeline, status fase, metrik progres  
📋 **[ENVIRONMENT.md](docs/ENVIRONMENT.md)** — Konfigurasi MySQL dev / PostgreSQL prod

## Fase Implementasi

| Fase | Modul | Status |
|------|-------|--------|
| 0 | Blueprint enterprise | ✅ Selesai |
| 1 | Core Platform, Auth, RBAC, 17 klinik | 🟡 ~85% |
| 1b | React Material Dashboard + API Sanctum | 🟡 ~70% |
| 2 | DMS + Workflow Engine | ⬜ |
| 3 | Audit Internal + CAPA | ⬜ |
| 4 | Risk + Quality Management | ⬜ |
| 5 | Accreditation (FKTP/FKTL) | ⬜ |
| 6 | Dashboard Enterprise + Go-Live | ⬜ |

## Setup Development (MySQL Laragon)

```bash
# 1. Buat database MySQL
php scripts/create_mysql_db.php

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Migration & seed
php artisan migrate:fresh --seed

# 4. Backend API
php artisan serve

# 5. React Dashboard (terminal terpisah)
cd react-dashboard-material-1.0.0
npm install
npm start
# Buka http://localhost:3000/login
```

**Login default:** `admin@simmak.ppkp.local` / `password`

### API Endpoints (Fase 1b)

| Method | Endpoint | Keterangan |
|--------|----------|------------|
| POST | `/api/v1/auth/login` | Login, dapat token |
| GET | `/api/v1/auth/me` | Profil user (Bearer token) |
| POST | `/api/v1/auth/logout` | Revoke token |
| GET | `/api/v1/dashboard/executive` | Data dashboard eksekutif |

## Frontend — React Material Dashboard

Template: `react-dashboard-material-1.0.0/` (Creative Tim Material Dashboard React)

| Halaman | Sumber |
|---------|--------|
| Dashboard admin | `src/views/Dashboard/Dashboard.js` |
| Layout sidebar | `src/layouts/Admin.js` |
| Login / Landing | Custom (Material-UI components) — Fase 1b |

Backend API akan disediakan Laravel Sanctum di `/api/v1/*`.

## Production (VM Linux + PostgreSQL)

Lihat `docs/ENVIRONMENT.md` dan `docs/ARCHITECTURE.md`.

```bash
# Set DB_CONNECTION=pgsql di .env
docker compose up -d --build
```

## Struktur Proyek

```
SIMMAK-PPKP/
├── app/                          # Laravel backend
├── database/migrations/          # Schema (MySQL & PG compatible)
├── docs/                         # Blueprint + PROGRESS_TIMELINE.md
├── react-dashboard-material-1.0.0/  # React UI template
├── diagrams/                     # ERD, DFD, BPMN
├── mockups/                      # Referensi desain dashboard
├── docker/                       # Docker production
└── pdf/                          # Export dokumen blueprint
```

## Dokumen Acuan

- `docs/BRD.md` — Business requirements & roadmap
- `docs/PRD.md` — Product requirements per modul
- `docs/DDD.md` — Database design (104 tabel)
- `docs/ARCHITECTURE.md` — Arsitektur teknis & deployment
- `docs/UIUX.md` — Design system
- `docs/PROGRESS_TIMELINE.md` — **Progres & timeline pembangunan**

## Perintah Berguna

```bash
php artisan migrate:fresh --seed   # Reset database
php artisan permission:cache-reset # Reset cache RBAC
php artisan route:list             # Daftar route
npm run build                      # Build assets Blade (sementara)
```
