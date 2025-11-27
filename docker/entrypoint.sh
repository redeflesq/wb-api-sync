#!/bin/bash
set -e

echo "Starting WB API Sync Application..."

echo "Waiting for remote database at $DB_HOST:$DB_PORT ..."
while ! nc -z "$DB_HOST" "$DB_PORT"; do
  echo "Database is unavailable - sleeping 3s..."
  sleep 3
done

echo "Database reachable"

if grep -q "APP_KEY=$" .env; then
    echo "🔑 Generating application key..."
    php artisan key:generate --force
fi

echo "Clearing cache..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "Optimizing..."
php artisan config:cache
php artisan route:cache

if [ "$RUN_MIGRATIONS" = "true" ]; then
  echo "Running migrations..."
  php artisan migrate --force
fi

echo "Setting permissions..."
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

if [ "$AUTO_SYNC" = "true" ]; then
    echo "Running initial sync..."
    php artisan wb:sync all --force --date-from=$(date -d '30 days ago' +%Y-%m-%d) --date-to=$(date +%Y-%m-%d) &
fi

echo "Application is ready!"
echo "Database Host: $DB_HOST:$DB_PORT $DB_DATABASE:$DB_USERNAME"

# Запуск supervisor (который управляет nginx, php-fpm и cron)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf