#!/usr/bin/env bash
# DailyNews - restore backup script
# Usage: ./restore.sh <backup-file.tar.gz>

set -euo pipefail

BACKUP_FILE="${1:?Usage: $0 <backup-file.tar.gz>}"
RESTORE_DIR="$(mktemp -d)"

echo "==> Extracting backup"
tar -xzf "${BACKUP_FILE}" -C "${RESTORE_DIR}"

echo "==> Restoring MySQL (web app db)"
mysql -u "${DB_USERNAME:-dailynews}" -p"${DB_PASSWORD}" "${DB_DATABASE:-dailynews}" < "${RESTORE_DIR}/mysql_dailynews.sql"

echo "==> Restoring PostgreSQL (main db)"
psql "postgresql://${PGSQL_USERNAME}:${PGSQL_PASSWORD}@${PGSQL_HOST:-localhost}/${PGSQL_DATABASE}" < "${RESTORE_DIR}/postgres_dailynews.sql"

echo "==> Restore complete"
rm -rf "${RESTORE_DIR}"
