#!/usr/bin/env bash
# Database backup for UniHup. Supports both supported DB_CONNECTION values:
# sqlite (the default — a plain file copy) and mysql/mariadb (mysqldump).
#
# Usage:
#   deploy/backup.sh                # writes to /var/backups/unihup
#   BACKUP_DIR=/mnt/nfs deploy/backup.sh
#
# Restore (on a target where this is safe — see the warning below):
#   sqlite:  gunzip -c /var/backups/unihup/database-2026-08-25T120000Z.sqlite.gz > database/database.sqlite
#   mysql:   gunzip -c /var/backups/unihup/unihup-2026-08-25T120000Z.sql.gz \
#              | mysql -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE"
#
# Actually DRILLING this (restoring into a scratch database, not the
# production one, and verifying the app boots against it) is a process
# step for the team to schedule, not something this script can do safely
# on its own — running the restore command above against a live database
# overwrites it.

set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/unihup}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/unihup}"
RETAIN_DAYS="${RETAIN_DAYS:-30}"

cd "$APP_DIR"

if [[ ! -f .env ]]; then
    echo "Error: $APP_DIR is not a configured Laravel application (.env is required)." >&2
    exit 1
fi

# shellcheck disable=SC1091
set -a; source .env; set +a

mkdir -p "$BACKUP_DIR"
TIMESTAMP="$(date -u +%Y-%m-%dT%H%M%SZ)"

case "${DB_CONNECTION:-sqlite}" in
    sqlite)
        SQLITE_PATH="${DB_DATABASE:-$APP_DIR/database/database.sqlite}"
        if [[ ! -f "$SQLITE_PATH" ]]; then
            echo "Error: sqlite database not found at $SQLITE_PATH." >&2
            exit 1
        fi
        DEST="$BACKUP_DIR/database-${TIMESTAMP}.sqlite.gz"
        echo "Backing up $SQLITE_PATH to $DEST..."
        gzip -c "$SQLITE_PATH" > "$DEST"
        ;;
    mysql | mariadb)
        DEST="$BACKUP_DIR/${DB_DATABASE}-${TIMESTAMP}.sql.gz"
        echo "Backing up ${DB_DATABASE} to ${DEST}..."
        # MYSQL_PWD rather than --password=... — the latter is visible to any
        # other user on the box via `ps aux` for as long as the process runs.
        MYSQL_PWD="${DB_PASSWORD:-}" mysqldump \
            --single-transaction \
            --routines \
            --triggers \
            -h "${DB_HOST:-127.0.0.1}" \
            -P "${DB_PORT:-3306}" \
            -u "${DB_USERNAME:-root}" \
            "${DB_DATABASE}" | gzip > "$DEST"
        ;;
    *)
        echo "Error: unsupported DB_CONNECTION=${DB_CONNECTION:-unset} for backup." >&2
        exit 1
        ;;
esac

echo "Backup complete: $(du -h "$DEST" | cut -f1)"

# Prune backups older than RETAIN_DAYS.
find "$BACKUP_DIR" -name "*.gz" -mtime "+${RETAIN_DAYS}" -delete
