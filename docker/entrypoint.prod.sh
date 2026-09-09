#!/bin/sh
set -e

cd /var/www/html

wait_for_mysql() {
  echo "Waiting for MySQL at ${DB_HOST:-mysql}:${DB_PORT:-3306}..."
  until php -r "
    try {
      new PDO(
        'mysql:host=' . getenv('DB_HOST') . ';port=' . (getenv('DB_PORT') ?: '3306'),
        getenv('DB_USERNAME'),
        getenv('DB_PASSWORD')
      );
      exit(0);
    } catch (Throwable \$e) {
      exit(1);
    }
  " 2>/dev/null; do
    sleep 2
  done
  echo "MySQL is ready."
}

# storage/ is a named volume (shared with the nginx sidecar so it can serve
# storage:link'd uploads directly) and starts out empty, so the subdirs Laravel
# expects to already exist have to be recreated before anything writes to them.
ensure_storage_dirs() {
  mkdir -p storage/framework/cache/data storage/framework/sessions \
    storage/framework/testing storage/framework/views storage/logs storage/app/public
  chown -R www-data:www-data storage bootstrap/cache
}

wait_for_mysql
ensure_storage_dirs

php artisan migrate --force
php artisan storage:link || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

# nginx runs in a separate container and can't see this container's filesystem,
# so hand it a copy of public/ (including the storage symlink) via the shared
# volume mounted at /shared-public.
mkdir -p /shared-public
cp -a public/. /shared-public/

exec "$@"
