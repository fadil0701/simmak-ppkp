# SIMMAK PPKP — Environment Configuration Guide

## Development (MySQL — Laragon)

Gunakan konfigurasi ini pada workstation developer:

```env
APP_ENV=local
APP_DEBUG=true

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
php artisan migrate:fresh --seed
```

---

## Staging / Production (PostgreSQL 17 — VM Docker)

```env
APP_ENV=production
APP_DEBUG=false

DB_CONNECTION=pgsql
DB_HOST=<host-postgres-vm>
DB_PORT=5432
DB_DATABASE=simmak_ppkp
DB_USERNAME=simmak
DB_PASSWORD=<secret>
```

**Catatan PostgreSQL:**
- Aktifkan extension `pgcrypto` untuk `gen_random_uuid()`
- Kolom `json` di migration dapat di-upgrade ke `jsonb` di production jika diperlukan
- Backup harian sesuai `docs/ARCHITECTURE.md`

---

## Frontend (React Material Dashboard)

```env
# API backend untuk React SPA
VITE_API_URL=http://localhost:8000/api/v1
```

Template UI: `react-dashboard-material-1.0.0/`
