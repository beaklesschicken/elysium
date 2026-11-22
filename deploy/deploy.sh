#!/usr/bin/env bash
# Deploys production for Elysium Hospitality. Runs on the server as `elysium`, no sudo needed.
# From a checkout of this repository:
#
#   ssh elysium@ericthedead.com 'bash -s' < deploy/deploy.sh                  # deploy; a live site stays live,
#                                                                             # a site that was down stays down
#   ssh elysium@ericthedead.com 'bash -s -- --stay-down' < deploy/deploy.sh    # deploy, then show coming soon
#   ssh elysium@ericthedead.com 'bash -s -- --go-live' < deploy/deploy.sh      # only take maintenance off
#   ssh elysium@ericthedead.com 'bash -s -- --status' < deploy/deploy.sh
#
# First run clones the repository and creates .env. While in maintenance mode the site can be
# previewed at https://elysiumhospitality.net/<secret> (the secret is in ~/.maintenance-secret).
set -euo pipefail

APP_DIR=/var/www/elysium
REPO=https://github.com/beaklesschicken/elysium.git
BRANCH=production
NODE_VERSION=22.20.0
NODE_DIR="$HOME/.local/node-v$NODE_VERSION"
SECRET_FILE="$HOME/.maintenance-secret"
STAY_DOWN=0

log() { printf '\n==> %s\n' "$*"; }

case "${1:-}" in
    "") ;;
    --stay-down) STAY_DOWN=1 ;;
    --go-live)
        cd "$APP_DIR" && php artisan up && echo "Live: https://elysiumhospitality.net"
        exit 0 ;;
    --status)
        cd "$APP_DIR"
        echo "Deployed: $(git log -1 --format='%h %s (%cd)' --date=short)"
        if [[ -f storage/framework/down ]]; then
            echo "Maintenance mode: ON. Preview: https://elysiumhospitality.net/$(cat "$SECRET_FILE" 2>/dev/null)"
        else
            echo "Maintenance mode: off (live)."
        fi
        exit 0 ;;
    *) echo "Unknown option: $1 (use --stay-down, --go-live or --status)" >&2; exit 1 ;;
esac

[[ "$(whoami)" == elysium ]] || { echo "Run this as the elysium user." >&2; exit 1; }
exec 9>"$HOME/.deploy.lock"
flock -n 9 || { echo "Another deploy is running." >&2; exit 1; }

# New files: owner read/write, group (nginx) read, others nothing.
umask 027

# The server's system Node is too old for current Vite, so this app keeps its own copy (no sudo).
if [[ ! -x "$NODE_DIR/bin/node" ]]; then
    log "Installing Node $NODE_VERSION for this user"
    tmp="$(mktemp -d)"
    curl -fsSL -o "$tmp/node.tar.xz" "https://nodejs.org/dist/v$NODE_VERSION/node-v$NODE_VERSION-linux-x64.tar.xz"
    curl -fsSL -o "$tmp/SHASUMS256.txt" "https://nodejs.org/dist/v$NODE_VERSION/SHASUMS256.txt"
    (cd "$tmp" && grep " node-v$NODE_VERSION-linux-x64.tar.xz\$" SHASUMS256.txt | sed 's/node-v.*/node.tar.xz/' | sha256sum -c -)
    mkdir -p "$NODE_DIR" && tar -xJf "$tmp/node.tar.xz" -C "$NODE_DIR" --strip-components=1
    rm -rf "$tmp"
fi
export PATH="$NODE_DIR/bin:$PATH"

# "First" lasts until one deploy has finished, so a failed first run never goes live on its retry.
FIRST_DONE_MARKER="$HOME/.first-deploy-done"
FIRST=0
[[ -f "$FIRST_DONE_MARKER" ]] || FIRST=1
if [[ ! -d "$APP_DIR/.git" ]]; then
    log "Cloning $REPO"
    git clone --branch "$BRANCH" "$REPO" "$APP_DIR"
fi
cd "$APP_DIR"

[[ -s "$SECRET_FILE" ]] || { (umask 077; openssl rand -hex 16 > "$SECRET_FILE"); }

# go_down coming-soon|plain: maintenance mode, showing the coming-soon page when asked and it exists.
# Falls back to the plain page so a render problem can never leave the site up.
go_down() {
    local args=(--secret="$(cat "$SECRET_FILE")" --retry=60)
    if [[ $1 == coming-soon && -f resources/views/coming-soon.blade.php ]]; then
        php artisan down "${args[@]}" --render=coming-soon && return 0
    fi
    php artisan down "${args[@]}"
}

# A site that is already down (e.g. showing coming soon) stays down until --go-live.
WAS_DOWN=0
[[ -f storage/framework/down ]] && WAS_DOWN=1 && STAY_DOWN=1
if [[ -f vendor/autoload.php ]]; then
    log "Maintenance mode"
    if [[ $WAS_DOWN == 1 || $FIRST == 1 ]]; then go_down coming-soon || true; else go_down plain || true; fi
fi
trap 'echo "Deploy failed; the site stays in maintenance mode. Fix and re-run." >&2' ERR

log "Fetching $BRANCH"
git fetch --prune origin "$BRANCH"
# Discards changes made directly on the server; .env, the database, storage/ and vendor/ are
# ignored by git and kept.
git reset --hard "origin/$BRANCH"
echo "Now at $(git log -1 --format='%h %s')"

if [[ ! -f .env ]]; then
    log "Creating production .env"
    cp .env.example .env
    sed -i \
        -e "s|^APP_NAME=.*|APP_NAME=\"Elysium Hospitality\"|" \
        -e "s|^APP_ENV=.*|APP_ENV=production|" \
        -e "s|^APP_DEBUG=.*|APP_DEBUG=false|" \
        -e "s|^APP_URL=.*|APP_URL=https://elysiumhospitality.net|" \
        -e "s|^LOG_LEVEL=.*|LOG_LEVEL=warning|" \
        -e "s|^DB_CONNECTION=.*|DB_CONNECTION=sqlite|" \
        -e "s|^APP_PORTAL_ENABLED=.*|APP_PORTAL_ENABLED=false|" \
        -e "s|^MAIL_MAILER=.*|MAIL_MAILER=postmark|" \
        .env
    grep -q '^SESSION_SECURE_COOKIE=' .env || printf '\nSESSION_SECURE_COOKIE=true\n' >> .env
    chmod 600 .env
    touch database/database.sqlite
    NEW_ENV=1
fi

log "Installing PHP dependencies"
composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# After composer: resources/css/app.css imports Flux's stylesheet from vendor/.
if [[ -f package.json ]]; then
    log "Building frontend"
    npm ci --no-audit --no-fund
    npm run build
fi

if [[ "${NEW_ENV:-0}" == 1 ]] || ! grep -qE '^APP_KEY=.+' .env; then
    php artisan key:generate --force
fi

if [[ $FIRST == 0 && -f deploy/backup.sh ]]; then
    log "Backing up before migrating"
    bash deploy/backup.sh
fi

log "Migrating database"
php artisan migrate --force

log "Caching configuration"
php artisan optimize:clear
php artisan optimize


log "Scheduler (credential reminder emails)"
SCHEDULE_LINE="* * * * * cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1"
if ! crontab -l 2>/dev/null | grep -qF "artisan schedule:run"; then
    { crontab -l 2>/dev/null || true; echo "$SCHEDULE_LINE"; } | crontab -
    echo "Added the Laravel scheduler to this user's crontab."
fi

log "Permissions"
chmod 600 .env
chmod -R u+rwX,go= database storage bootstrap/cache
chmod -R u+rwX,g=rX,o= app config public resources routes vendor

for key in POSTMARK_TOKEN MAIL_CONTACT_TO; do
    grep -qE "^$key=.+" .env || echo "WARNING: $key is empty, so the contact form can't send mail. Set it with deploy/set-env-secret.sh $key"
done
grep -qE '^APP_PORTAL_ENABLED=true' .env && echo "WARNING: APP_PORTAL_ENABLED=true; the unfinished portal (login, teams) is public."

trap - ERR
touch "$FIRST_DONE_MARKER"
if [[ $STAY_DOWN == 1 || $FIRST == 1 ]]; then
    go_down coming-soon || true
    log "Deployed $(git log -1 --format='%h'). Still in maintenance mode, showing the coming-soon page."
    echo "Preview: https://elysiumhospitality.net/$(cat "$SECRET_FILE")"
    echo "Go live: ssh elysium@ericthedead.com 'bash -s -- --go-live' < deploy/deploy.sh"
else
    php artisan up
    log "Deployed $(git log -1 --format='%h') on $(date '+%a %b %-d %Y at %-I:%M %p %Z'). Live."
fi
