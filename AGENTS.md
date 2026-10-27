# AGENTS.md — SIMMAK PPKP

Quality management, internal audit, and accreditation system for PPKP DKI Jakarta.
Laravel 12 + PHP 8.2+ backend, **Blade + Tailwind v4 + Vite + Livewire** frontend.

## Commands

```bash
# First-time dev setup (default = PostgreSQL Docker; MySQL Laragon opsional)
cp .env.example .env && php artisan key:generate
php artisan migrate:fresh --seed
npm install && npm run build
php artisan serve                 # or: composer app
```

- Opsi MySQL lokal (Laragon): jalankan `php scripts/create_mysql_db.php`, lalu ubah `DB_CONNECTION=mysql` + `DB_HOST/DB_PORT/DB_USERNAME/DB_PASSWORD` di `.env`.

- `composer dev` runs server + queue listener + Pail logs + Vite together (concurrently). Use this while developing.
- After editing CSS/Tailwind: `npm run build` (or run `npm run dev` for hot reload).
- Tests: `php artisan test` or `vendor/bin/phpunit --filter=<Name>`. There is **no** `composer test` script.
- Laravel Pint (style) is installed but has no script: run `vendor/bin/pint`.
- RBAC cache after changing roles/permissions: `php artisan permission:cache-reset`.
- After changing Scope Sistem bundles (`app/Support/SimmakScopes.php`) or backfilling scopes: `php artisan simmak:sync-user-scopes` (re-applies direct permissions per scope for every account).
- Demo accounts (password `password`): `admin@…`, `mutu@…`, `kepala@…`, `pjmutu@…`, `akreditasi@…`, `auditor@…`, `staff@…` `@simmak.ppkp.local`.

## Environment / database gotchas

- **Satu berkas `.env` untuk semua lingkungan** (lihat `.env.example`). Docker Compose memakai `.env` yang sama; nilai khusus container (`DB_HOST=postgres`, `REDIS_HOST=redis`, `CLAMAV_HOST=clamav`, `APP_URL`) di-override lewat blok `environment:` di `docker-compose.yml` — jadi jangan bikin `.env.docker` lagi.
- The app must stay compatible with **both MySQL (dev) and PostgreSQL 17 (prod)**. `FIELD()`, `MAX(uuid)`, and double-quoted string literals have already caused PG breakage — see `docs/DEPLOY_DOCKER.md` "Perbaikan PostgreSQL" before writing raw SQL.
- `phpunit.xml` **forces** `DB_DATABASE=simmak_ppkp_test` (`force="true"`) so tests never touch the app database; `tests/TestCase.php::beforeRefreshingDatabase()` aborts with a `RuntimeException` if the resolved database is not a `*_test` / `testing` / `phpunit` / `:memory:` name. Create the test database once (`CREATE DATABASE simmak_ppkp_test;`) before running `php artisan test`.
- Docker stack: `docker compose build app && docker compose up -d`, build assets with `docker compose --profile tools run --rm assets`, run artisan as `docker compose exec app gosu www-data php artisan …`.

## Architecture

- UUID primary keys + soft deletes: business models extend `App\Models\Concerns\BaseModel`; `User` reuses the traits directly. Use `BelongsToOrganization` (`organization()` + `forOrganization()` scope) for org-scoped data.
- Multi-clinic context: the `clinic.context` middleware alias (`SetClinicContext`) resolves `active_clinic_id` from session/user. Authenticated web routes are wrapped in `Route::middleware(['auth', 'clinic.context'])` (`routes/web.php`).
- RBAC uses Spatie Permission with guard `web`. Permission names are `module.action` (`dashboard.executive`, `dms.view`, `dms.manage`, `audit.manage`, …). Routes are gated with `permission:` middleware. Role/permission matrix is seeded in `database/seeders/DatabaseSeeder.php`; the sidebar menu is `app/Support/SimmakMenu.php`. Role catalog lives in `app/Support/SimmakRoles.php` (`legacyMap()` keeps old role aliases alive).
- **Access model (implemented 26 Oct 2026).** Employee holds multiple clinics via `employee_clinic_assignments` (`Employee::syncClinicAssignments()`, exactly one `is_primary`; legacy `employees.clinic_id`/`unit_id` mirror the primary). Users hold multiple roles (`role_ids[]` → `syncRoles()`) plus multi-select system scopes in `user_scope_assignments` (`App\Support\SimmakScopes`: `audit_internal`, `mutu`, `akreditasi`, `monitoring`, `auditor`, `auditi`). Clinic access is derived from the employee master by `App\Services\UserClinicScopeSynchronizer`; `user_clinic_scopes` remains a synced mirror so `SetClinicContext`, `ClinicScope`, and `AuditWorkScope` are untouched. Effective permission = role ∪ scope (`App\Services\UserScopeSynchronizer`); scopes never bypass clinic limits or segregation of duties. Role catalog: 8 assignable roles in `SimmakRoles::catalog()`; `auditor`/`auditi`/`ketua-tim-*` stay in the DB as non-assignable legacy roles. After changing scope bundles run `php artisan simmak:sync-user-scopes`. Spec: `docs/PERBAIKAN_MASTER_PEGAWAI_PENGGUNA_PENUGASAN.md`; ADR-0014…ADR-0018 still **Proposed**.
- Business logic lives in `app/Services/`; controllers (`app/Http/Controllers`) are thin. API is `/api/v1/*` via Sanctum (`routes/api.php`).
- Frontend is **server-rendered Blade**, not a React SPA — despite the `VITE_API_URL` / "Material Dashboard" mentions. Views follow the mockups (`docs/FRONTEND_INTEGRATION.md`, `docs/UIUX.md`, `mockups/`).
- Scheduled job `dms:check-expiry` runs daily at 06:00 (`routes/console.php`).

## Docs

- Authoritative: `docs/PRD.md`, `docs/DDD.md` (schema), `docs/PROGRESS_TIMELINE.md` (status), `docs/FRONTEND_INTEGRATION.md`, `docs/ENVIRONMENT.md`, `docs/DEPLOY_DOCKER.md`.
- `docs/ARCHITECTURE.md` is long generated boilerplate; prefer the files above.
- Manual PDFs: `python scripts/generate_manual_pdfs.py` → `pdf/manual/`.
