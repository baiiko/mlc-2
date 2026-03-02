#!/bin/sh
set -e

# Fix volume directories permissions (for existing volumes)
mkdir -p /var/www/app/public/uploads/maps/thumbnails
mkdir -p /var/matchsettings
chown -R app:app /var/www/app/public/uploads
chown -R app:app /var/matchsettings

if [ -n "$DATABASE_URL" ]; then
    echo "Running database migrations..."
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || true
fi
php bin/console cache:clear --no-warmup
php bin/console cache:warmup
exec "$@"
