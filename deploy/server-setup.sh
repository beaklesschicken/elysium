#!/usr/bin/env bash
# One-time server setup for Elysium Hospitality. Run as an admin with sudo, from a copy of this deploy/
# folder on the server (see deploy/README.md):
#
#   sudo bash server-setup.sh /path/to/your_ssh_key.pub
#
# New app (no site in /var/www/elysium yet): creates the `elysium` user, the app folder, the
# database, a dedicated PHP-FPM pool, the nginx site and the nightly backup. Then run the first
# deploy and server-finish.sh.
#
# Existing app (/var/www/elysium already has a Laravel site): puts it in maintenance mode FIRST (it stays
# down until you go live), backs everything up privately, reviews the web logs, then hands the
# folder to `elysium` and locks down permissions before switching PHP and nginx over.
#
# Safe to re-run: existing users, keys, databases and backups are kept; configs are re-installed.
set -euo pipefail

APP_USER=elysium
APP_DIR=/var/www/elysium
SLUG=elysium
APP_HOME="/home/$APP_USER"
NGINX_SITE=/etc/nginx/sites-available/$SLUG.conf
POOL=/etc/php/8.3/fpm/pool.d/$SLUG.conf
HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
KEY_FILE="${1:-}"
STAMP=$(date -u +%Y%m%d-%H%M%S)

[[ $EUID -eq 0 ]] || { echo "Run with sudo." >&2; exit 1; }
[[ -n "$KEY_FILE" && -f "$KEY_FILE" ]] || { echo "Usage: sudo bash $0 /path/to/your_ssh_key.pub" >&2; exit 1; }
[[ -f "$HERE/php-fpm/$SLUG.conf" && -f "$HERE/nginx/$SLUG.conf" ]] || { echo "Run this from the deploy/ folder (php-fpm/ and nginx/ are missing)." >&2; exit 1; }

step() { printf '\n==> %s\n' "$*"; }
EXISTING=0
[[ -f "$APP_DIR/artisan" ]] && EXISTING=1

step "System user $APP_USER"
if ! id "$APP_USER" >/dev/null 2>&1; then
    adduser --disabled-password --gecos "Elysium Hospitality app" "$APP_USER"
fi
install -d -m 750 -o "$APP_USER" -g "$APP_USER" "$APP_HOME" "$APP_HOME/backups"
chown "$APP_USER:$APP_USER" "$APP_HOME/backups"; chmod 700 "$APP_HOME/backups"

SECRET_FILE="$APP_HOME/.maintenance-secret"
if [[ ! -s "$SECRET_FILE" ]]; then
    install -m 600 -o "$APP_USER" -g "$APP_USER" /dev/null "$SECRET_FILE"
    openssl rand -hex 16 > "$SECRET_FILE"
fi

if [[ $EXISTING == 1 ]]; then
    step "Existing site: maintenance mode (stays on until you go live)"
    OWNER=$(stat -c %U "$APP_DIR/artisan")
    (cd "$APP_DIR" && sudo -u "$OWNER" php artisan down --secret="$(cat "$SECRET_FILE")" --retry=300)

    step "Backup before any changes"
    BACKUP="$APP_HOME/backups/pre-setup-$STAMP"
    install -d -m 700 -o "$APP_USER" -g "$APP_USER" "$BACKUP"
    for f in .env .env.save .env.backup database/database.sqlite database/database.sqlite-wal database/database.sqlite-shm; do
        if [[ -f "$APP_DIR/$f" ]]; then cp -a "$APP_DIR/$f" "$BACKUP/$(basename "$f")"; fi
    done
    tar -czf "$BACKUP/storage-app.tar.gz" -C "$APP_DIR/storage" app
    if [[ -f "$NGINX_SITE" ]]; then cp -a "$NGINX_SITE" "$BACKUP/nginx-$SLUG.conf"; fi
    for f in /etc/nginx/sites-enabled/*; do
        if grep -qs "$APP_DIR" "$f"; then cp -aL "$f" "$BACKUP/nginx-enabled-$(basename "$f")"; fi
    done
    chown -R "$APP_USER:$APP_USER" "$BACKUP"; chmod -R u=rwX,go= "$BACKUP"
    echo "Saved in $BACKUP"

    step "Web log review (protected pages that returned 200, by IP)"
    REPORT="$BACKUP/access-review.txt"
    # The site's current access logs, wherever its existing nginx config writes them.
    LOGS=$(grep -lsR "$APP_DIR" /etc/nginx/sites-enabled/ | xargs -r awk '$1 == "access_log" && $2 ~ /^\// { sub(/;$/, "", $2); print $2 }' | sort -u)
    LOGS=${LOGS:-/var/log/nginx/$SLUG-access.log}
    {
        echo "Requests that returned 200 for non-public paths. Logged-in staff appear too; look for unknown IPs."
        echo "Edit the path list for this app if needed."
        { for log in $LOGS; do zcat -f "$log"* 2>/dev/null || true; done; } \
            | grep -E '"(GET|POST|PATCH|PUT|DELETE) /(admin|dashboard|users?|api)[/? ]' \
            | awk '$9 == 200 { print $1 }' | sort | uniq -c | sort -rn
    } > "$REPORT"
    chown "$APP_USER:$APP_USER" "$REPORT"; chmod 600 "$REPORT"
    echo "Report: $REPORT"

    # Leftover copies of settings hold secrets; they're in the backup above.
    rm -f "$APP_DIR/.env.save" "$APP_DIR/.env.backup"
else
    step "App folder $APP_DIR"
    install -d -m 750 -o "$APP_USER" -g "$APP_USER" "$APP_DIR"
fi

step "SSH access for $APP_USER (key login only, no tunnels or forwarding)"
install -d -m 700 -o "$APP_USER" -g "$APP_USER" "$APP_HOME/.ssh"
touch "$APP_HOME/.ssh/authorized_keys"
grep -qxF "$(cat "$KEY_FILE")" "$APP_HOME/.ssh/authorized_keys" || cat "$KEY_FILE" >> "$APP_HOME/.ssh/authorized_keys"
chown "$APP_USER:$APP_USER" "$APP_HOME/.ssh/authorized_keys"; chmod 600 "$APP_HOME/.ssh/authorized_keys"
cat > "/etc/ssh/sshd_config.d/60-$APP_USER.conf" <<CFG
# Installed by $SLUG deploy/server-setup.sh
Match User $APP_USER
    PasswordAuthentication no
    AllowTcpForwarding no
    AllowAgentForwarding no
    AllowStreamLocalForwarding no
    PermitTunnel no
    X11Forwarding no
CFG
sshd -t
# Ubuntu 24.04 may start sshd on demand; if it isn't running, the next login reads the new config.
if systemctl is-active --quiet ssh; then systemctl reload ssh; fi


step "Ownership and permissions"
chown -R "$APP_USER:$APP_USER" "$APP_DIR"
# Code: owner read/write, group (nginx) read, others nothing. X keeps existing executables runnable.
chmod -R u+rwX,g=rX,o= "$APP_DIR"
# Secrets and data: owner only, so nginx and the shared www-data PHP pool can't read them.
for p in .env database storage bootstrap/cache; do
    if [[ -e "$APP_DIR/$p" ]]; then chmod -R u+rwX,go= "$APP_DIR/$p"; fi
done
# nginx serves public/ and needs to pass through the folders above it.
usermod -aG "$APP_USER" www-data

step "PHP-FPM pool"
install -m 644 "$HERE/php-fpm/$SLUG.conf" "$POOL"
php-fpm8.3 -t
systemctl reload php8.3-fpm
for _ in 1 2 3 4 5; do [[ -S /run/php/php8.3-fpm-$APP_USER.sock ]] && break; sleep 1; done
[[ -S /run/php/php8.3-fpm-$APP_USER.sock ]] || { echo "The new PHP pool didn't start; check: journalctl -u php8.3-fpm" >&2; exit 1; }

step "nginx site"
PREV=""
if [[ -f "$NGINX_SITE" ]]; then PREV=$(mktemp); cp -a "$NGINX_SITE" "$PREV"; fi
install -m 644 "$HERE/nginx/$SLUG.conf" "$NGINX_SITE"
ln -sf "$NGINX_SITE" "/etc/nginx/sites-enabled/$SLUG.conf"
if ! nginx -t; then
    echo "The new nginx config failed its test; putting the old one back." >&2
    if [[ -n "$PREV" ]]; then cp -a "$PREV" "$NGINX_SITE"; else rm -f "$NGINX_SITE" "/etc/nginx/sites-enabled/$SLUG.conf"; fi
    nginx -t && systemctl reload nginx
    exit 1
fi
systemctl reload nginx
rm -f "$PREV"


step "Nightly backup at 08:00 UTC (before the 09:00 UTC reboot)"
CRON_LINE="0 8 * * * [ -f $APP_DIR/deploy/backup.sh ] && bash $APP_DIR/deploy/backup.sh >> \$HOME/backups/backup.log 2>&1"
{ crontab -u "$APP_USER" -l 2>/dev/null | grep -v "$APP_DIR/deploy/backup.sh" || true; echo "$CRON_LINE"; } | crontab -u "$APP_USER" -

step "Done"
echo "Preview while in maintenance mode: https://elysiumhospitality.net/$(cat "$SECRET_FILE")"
echo "Next: deploy as $APP_USER (see deploy/README.md)."
if [[ -f "$APP_DIR/.env" ]] && grep -qE '^MAIL_MAILER=(log|array)' "$APP_DIR/.env"; then
    echo "Note: .env has MAIL_MAILER set to log/array, so the app sends no email."
fi
