#!/usr/bin/env bash
# Nightly backup of Elysium Hospitality's database and uploaded files. Runs as `elysium` (no sudo)
# from its crontab. Keeps KEEP_DAYS of backups in ~/backups and, when a remote is set in
# ~/.backup-remote (by deploy/set-backup-remote.sh), copies each one to Backblaze B2.
#
#   bash deploy/backup.sh            # take a backup now
set -euo pipefail

APP_DIR=/var/www/elysium
BACKUP_DIR="$HOME/backups"
KEEP_DAYS=14
STAMP=$(date -u +%Y%m%d-%H%M%S)

umask 077
mkdir -p "$BACKUP_DIR/db" "$BACKUP_DIR/files"


TMP="$BACKUP_DIR/db/.tmp-$STAMP.sqlite"
trap 'rm -f "$TMP"' EXIT
# A consistent copy while the site keeps running (SQLite's VACUUM INTO), then a full integrity check.
php -r '
    $db = new PDO("sqlite:" . $argv[1], null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $db->exec("VACUUM INTO " . $db->quote($argv[2]));
    $copy = new PDO("sqlite:" . $argv[2], null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $check = $copy->query("PRAGMA integrity_check")->fetchColumn();
    if ($check !== "ok") { fwrite(STDERR, "Backup failed integrity check: $check\n"); exit(1); }
' "$APP_DIR/database/database.sqlite" "$TMP"
DB_FILE="$BACKUP_DIR/db/elysium-$STAMP.sqlite.gz"
gzip -9 -c "$TMP" > "$DB_FILE"
gzip -t "$DB_FILE"

# Uploads and other stored files (Livewire's temporary uploads are skipped).
FILES_FILE="$BACKUP_DIR/files/storage-$STAMP.tar.gz"
tar -czf "$FILES_FILE" --exclude='app/livewire-tmp' -C "$APP_DIR/storage" app

# Prune old nightly backups (one-off folders like pre-setup-* are kept).
find "$BACKUP_DIR/db" "$BACKUP_DIR/files" -type f -mtime +"$KEEP_DAYS" -delete

echo "$(date -u '+%F %T') backup ok: $(basename "$DB_FILE") ($(du -h "$DB_FILE" | cut -f1)), $(basename "$FILES_FILE") ($(du -h "$FILES_FILE" | cut -f1))"

if [[ -f "$HOME/.backup-remote" ]]; then
    REMOTE=$(tr -d '[:space:]' < "$HOME/.backup-remote")
    RCLONE=$(command -v rclone || echo "$HOME/bin/rclone")
    "$RCLONE" copy "$DB_FILE" "$REMOTE/db/"
    "$RCLONE" copy "$FILES_FILE" "$REMOTE/files/"
    echo "$(date -u '+%F %T') copied to $REMOTE"
else
    echo "$(date -u '+%F %T') no off-server copy yet: run deploy/set-backup-remote.sh" >&2
fi
