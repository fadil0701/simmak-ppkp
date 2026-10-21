#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

log() { echo "[entrypoint] $*"; }

mkdir -p storage/framework/{cache/data,sessions,testing,views} \
         storage/logs \
         storage/app/public \
         bootstrap/cache

# Dependency PHP di-install sebagai root, lalu dimiliki www-data
if [ ! -f vendor/autoload.php ]; then
    log "vendor belum ada, menjalankan composer install..."
    composer install --no-interaction --prefer-dist --no-progress
fi

chown -R www-data:www-data storage bootstrap/cache vendor

# Perintah artisan dijalankan sebagai www-data
artisan() { gosu www-data php artisan "$@"; }

if ! grep -qE '^APP_KEY=base64:.+' .env; then
    log "APP_KEY kosong, menjalankan key:generate..."
    artisan key:generate --force --no-interaction
fi

if [ "${WAIT_FOR_DB:-true}" = "true" ]; then
    log "Menunggu database..."
    ready=0
    for _ in $(seq 1 40); do
        if gosu www-data php -r '
            $dsn = sprintf("pgsql:host=%s;port=%s;dbname=%s", getenv("DB_HOST"), getenv("DB_PORT"), getenv("DB_DATABASE"));
            try { new PDO($dsn, getenv("DB_USERNAME"), getenv("DB_PASSWORD"), [PDO::ATTR_TIMEOUT => 3]); exit(0); }
            catch (Throwable $e) { exit(1); }
        ' 2>/dev/null; then
            ready=1
            break
        fi
        sleep 2
    done
    if [ "$ready" != "1" ]; then
        log "Database belum siap setelah 80 detik." >&2
        exit 1
    fi
    log "Database siap."
fi

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    log "Menjalankan migrasi..."
    artisan migrate --force --no-interaction
fi

artisan storage:link --force --no-interaction >/dev/null 2>&1 || true

log "Menjalankan: $*"
exec "$@"