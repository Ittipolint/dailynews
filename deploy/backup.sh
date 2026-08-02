#!/usr/bin/env bash
# DailyNews - automated daily backup (add to crontab)
# Cron: 0 2 * * * /var/www/dailynews/deploy/backup.sh

set -euo pipefail

BACKUP_DIR="${BACKUP_DIR:-/var/backups/dailynews}"
DATE="$(date +%Y%m%d_%H%M%S)"
mkdir -p "${BACKUP_DIR}"

# MySQL web app db
mysqldump -u "${DB_USERNAME:-dailynews}" -p"${DB_PASSWORD}" "${DB_DATABASE:-dailynews}" > "${BACKUP_DIR}/mysql_dailynews.sql"

# PostgreSQL main db
pg_dump "postgresql://${PGSQL_USERNAME}:${PGSQL_PASSWORD}@${PGSQL_HOST:-localhost}/${PGSQL_DATABASE}" > "${BACKUP_DIR}/postgres_dailynews.sql"

# Archive
tar -czf "${BACKUP_DIR}/dailynews_${DATE}.tar.gz" -C "${BACKUP_DIR}" mysql_dailynews.sql postgres_dailynews.sql
rm "${BACKUP_DIR}/mysql_dailynews.sql" "${BACKUP_DIR}/postgres_dailynews.sql"

# Retention: keep 30 days
find "${BACKUP_DIR}" -name "dailynews_*.tar.gz" -mtime +30 -delete

echo "==> Backup complete: ${BACKUP_DIR}/dailynews_${DATE}.tar.gz"
