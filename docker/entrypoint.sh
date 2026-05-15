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

ensure_app_key() {
  key_file=/var/www/html/storage/.docker_app_key
  case "${APP_KEY:-}" in
    ""|"GENERATE_ME")
      if [ -f "$key_file" ]; then
        export APP_KEY="$(cat "$key_file")"
      else
        export APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
        echo "$APP_KEY" > "$key_file"
      fi
      ;;
  esac
}

wait_for_mysql
ensure_app_key

if [ ! -f vendor/autoload.php ]; then
  echo "Installing Composer dependencies..."
  composer install --no-interaction --prefer-dist
fi

php artisan config:clear
php artisan migrate --force

exec "$@"
