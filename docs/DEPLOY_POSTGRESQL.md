# Deploy PostgreSQL (Staging / Production)

## Staging lokal dengan Docker

```bash
docker compose -f docker-compose.dev.yml up -d postgres
```

Ubah `.env`:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=simmak_ppkp
DB_USERNAME=simmak
DB_PASSWORD=simmak_secret
```

Jalankan migrasi:
```bash
php artisan migrate:fresh --seed --force
```

## Production (VM)

1. Siapkan PostgreSQL 17 di VM sesuai `docs/ARCHITECTURE.md`
2. Deploy aplikasi via `docker-compose.yml` (Nginx + PHP-FPM + Redis + workers)
3. Set `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`, `SESSION_DRIVER=redis`
4. Jalankan `php artisan migrate --force` dan `php artisan db:seed --force`
5. Konfigurasi backup harian PostgreSQL

## Redis (Production)

Redis sudah tersedia di `docker-compose.yml`. Dashboard executive memakai cache TTL 300 detik (`DashboardService`).
