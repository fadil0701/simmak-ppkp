# Deploy Docker Lokal — SIMMAK PPKP

Stack ringkas: Nginx + PHP-FPM 8.4 + PostgreSQL 17 + Redis 7 + queue worker + scheduler.
Semua konfigurasi memakai **satu berkas `.env`** (lihat `.env.example`); nilai khusus
container (`DB_HOST=postgres`, `REDIS_HOST=redis`, `CLAMAV_HOST=clamav`, `APP_URL`)
ditimpa otomatis lewat blok `environment:` di `docker-compose.yml`.

## Prasyarat

- Docker Desktop berjalan (context `desktop-linux` atau `default`)
- Port bebas: `8080` (web), `5432` (PostgreSQL), `6379` (Redis)

## Menjalankan

```bash
# Build image PHP + jalankan seluruh stack
docker compose build app
docker compose up -d

# Build asset frontend (Vite/Tailwind) -> public/build
docker compose --profile tools run --rm assets

# Isi data contoh (opsional)
docker compose exec app gosu www-data php artisan db:seed --force
```

Akses aplikasi di <http://localhost:8080>.

Akun demo dibuat oleh seeder. Password diambil dari `DEFAULT_USER_PASSWORD`
(fallback lama `DEMO_ACCOUNT_PASSWORD`) — tidak ada di codebase; jika kosong,
seeder membuat password acak dan mencetaknya sekali ke console saat seeding.
Set `DEFAULT_USER_PASSWORD` di `.env` untuk password yang deterministik di
lingkungan Docker.

Super admin dibuat terpisah (bukan seeder), dijalankan di dalam container:

```bash
docker compose exec app gosu www-data php artisan simmak:create-superadmin
```

Akun demo dibuat `RoleUsersSeeder`. Kolom role di bawah adalah role **setelah** `migrateLegacyRoles()`
menjalankan `SimmakRoles::legacyMap()`, jadi beberapa akun tetap memakai peran legacy
(`ketua-tim-*`, `auditor`, `auditi`) yang memang tidak assignable untuk akun baru.

| Username | Email | Role |
| --- | --- | --- |
| `$SUPERADMIN_USERNAME` (default `admin`) | `$SUPERADMIN_EMAIL` | super-admin |
| `adminsistem` | `sistem@simmak.ppkp.local` | admin-sistem |
| `kepalapusat` | `kepalapusat@simmak.ppkp.local` | kepala-pusat |
| `kasubbagtu` | `tu@simmak.ppkp.local` | kasubbag-tu |
| `ketuamutu` | `mutu@simmak.ppkp.local` | ketua-tim-mutu (legacy) |
| `ketuaaudit` | `ketuaaudit@simmak.ppkp.local` | ketua-tim-audit (legacy) |
| `akreditasi` | `akreditasi@simmak.ppkp.local` | ketua-tim-akreditasi (legacy) |
| `auditor` | `auditor@simmak.ppkp.local` | auditor (legacy) |
| `kepala` | `kepala@simmak.ppkp.local` | kepala-klinik |
| `auditi` | `staff@simmak.ppkp.local` | auditi (legacy) |

Akun super admin tidak ada di daftar `$accounts` seeder; ia dibuat `php artisan simmak:create-superadmin`
dari `SUPERADMIN_EMAIL` / `SUPERADMIN_USERNAME` / `SUPERADMIN_PASSWORD` di `.env`.

## Perintah harian

```bash
docker compose ps                      # status container
docker compose logs -f app             # log PHP-FPM + entrypoint
docker compose logs -f queue           # log worker
docker compose restart app             # restart setelah ubah kode
docker compose exec app gosu www-data php artisan <command>
docker compose down                    # matikan, data tetap tersimpan
docker compose down -v                 # hapus juga volume (data hilang)
```

## Service

| Service | Peran | Port host |
| --- | --- | --- |
| `nginx` | web server, proxy ke PHP-FPM | 8080 |
| `app` | PHP-FPM 8.4 + entrypoint init | — |
| `postgres` | PostgreSQL 17 | 5432 |
| `redis` | Redis 7 (cache/queue) | 6379 |
| `queue` | `queue:work` | — |
| `scheduler` | `schedule:work` | — |
| `assets` | build Vite (profil `tools`, one-shot) | — |

Port host bisa dioverride lewat `.env`: `APP_PORT`, `POSTGRES_PORT`, `REDIS_PORT`.

## Yang dilakukan entrypoint (`docker/php/entrypoint.sh`)

1. `composer install` bila `vendor/autoload.php` belum ada (volume `vendor` terpisah)
2. `key:generate` bila `APP_KEY` kosong di `.env`
3. Menunggu PostgreSQL siap (loop PDO, maks 80 detik)
4. `php artisan migrate --force`
5. `storage:link`

Queue dan scheduler memakai `RUN_MIGRATIONS=false` agar tidak berlomba dengan `app`.

## Catatan arsitektur

- `vendor/` dan `node_modules/` memakai named volume, bukan bind mount, supaya tidak
  dipakai artefak hasil `composer install`/`npm install` di Windows.
- Kode aplikasi tetap bind mount, jadi edit di host langsung terbaca.
- `.env` di-mount sebagai bagian dari bind mount `.:/var/www/html`, lalu nilai khusus container ditimpa oleh blok `environment:` pada `docker-compose.yml`.
- `opcache.validate_timestamps=1` supaya perubahan kode terbaca tanpa rebuild.

## Perbaikan PostgreSQL yang diperlukan

Aplikasi ini awalnya ditulis dengan asumsi MySQL. Empat tempat tidak kompatibel dengan
PostgreSQL dan sudah diperbaiki:

| Lokasi | Masalah | Perbaikan |
| --- | --- | --- |
| `database/migrations/2026_07_08_000001_...` | FK `departments.parent_id` menunjuk tabel yang sedang dibuat | Kolom dibuat polos, FK ditambahkan via `Schema::table` setelah tabel ada |
| `database/migrations/2026_07_09_100000_...` | Idem untuk `document_categories.parent_id` | Idem |
| `app/Models/AccreditationProgram.php` | `ofMany()` melakukan `MAX(id)` pada kolom UUID; PostgreSQL tidak punya `max(uuid)` | Relasi `latestOf()` memakai `orderByDesc` |
| `app/Http/Controllers/AccreditationController.php` | `FIELD()` hanya ada di MySQL | `CASE severity WHEN ... THEN` |
| `app/Services/DashboardService.php` | `SUM(CASE WHEN status = "closed" ...)` — tanda kutip ganda dibaca sebagai identifier di PostgreSQL | Kutip tunggal |

## Troubleshooting

**Halaman 500 dan `storage/logs/laravel-*.log` grew**

```bash
docker compose exec app tail -f storage/logs/laravel-$(date +%F).log
```

**Container `app` keluar dengan "Database belum siap"**

Pastikan `postgres` sehat: `docker compose ps`. Compose sudah menunggu lewat
healthcheck, jadi biasanya hanya perlu `docker compose up -d app`.

**Mulai dari database kosong**

```bash
docker compose exec postgres psql -U simmak -d simmak_ppkp \
  -c "DROP SCHEMA public CASCADE; CREATE SCHEMA public; GRANT ALL ON SCHEMA public TO simmak;"
docker compose up -d --force-recreate app
docker compose exec app gosu www-data php artisan db:seed --force
```

**Port 8080 sudah dipakai**

Set `APP_PORT=8081` di `.env`, lalu `docker compose up -d nginx`.