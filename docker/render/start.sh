#!/usr/bin/env bash
set -e

php artisan config:clear
php artisan migrate --force

if [ "${SEED_DATABASE:-true}" = "true" ]; then
    php artisan db:seed --force
fi

php artisan storage:link || true
apache2-foreground
