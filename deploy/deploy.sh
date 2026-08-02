#!/usr/bin/env bash
# ============================================================
# DailyNews - Ubuntu deployment script (Phase 1)
# Target: https://ittipolint-sbu.veya.co.th/dailynews
# Prereq: PHP 8.2+, Composer, Nginx, PostgreSQL, MySQL, Redis
# ============================================================
set -euo pipefail

APP_ROOT="${APP_ROOT:-/var/www/dailynews}"
WEB_USER="${WEB_USER:-www-data}"

echo "==> DailyNews deployment starting"

# 1. Ensure directories
sudo mkdir -p "${APP_ROOT}"
sudo chown -R "${USER}" "${APP_ROOT}"

# 2. Sync source (assumes repo already cloned into ${APP_ROOT})
cd "${APP_ROOT}/webapp"

# 3. Install dependencies
echo "==> Installing composer dependencies"
composer install --no-dev --optimize-autoloader --no-interaction

# 4. Environment
if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate
    echo "==> .env created - PLEASE EDIT credentials before continuing"
    exit 1
fi

# 5. Run migrations and seeders
echo "==> Running migrations (MySQL framework db)"
php artisan migrate --force

echo "==> Running migrations (PostgreSQL main db)"
php artisan migrate --database=pgsql --force

echo "==> Seeding initial data"
php artisan db:seed --force

# 6. Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link

# 7. Set up scheduler
echo "==> Installing crontab entry"
CRON_LINE="* * * * * cd ${APP_ROOT}/webapp && php artisan schedule:run >> /dev/null 2>&1"
( crontab -l 2>/dev/null | grep -v "dailynews" ; echo "${CRON_LINE}" ) | crontab -

# 8. Queue worker (if using queues)
echo "==> Queue worker configured via systemd, start manually if needed:"
echo "    php artisan queue:work redis --daemon"

# 9. Nginx site config
echo "==> Copying nginx site config"
sudo cp "${APP_ROOT}/deploy/nginx-dailynews.conf" /etc/nginx/sites-available/dailynews.conf
sudo ln -sf /etc/nginx/sites-available/dailynews.conf /etc/nginx/sites-enabled/dailynews.conf
sudo nginx -t && sudo systemctl reload nginx

# 10. Permissions
sudo chown -R "${WEB_USER}:${WEB_USER}" "${APP_ROOT}/storage" "${APP_ROOT}/bootstrap/cache"

echo "==> Deployment complete"
