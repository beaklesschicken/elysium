#!/usr/bin/env bash
# Connects nightly backups to Backblaze B2 (the shared mybrewfest bucket, in a elysium/
# folder). Run on the server as the elysium user:
#
#   ssh -t elysium@ericthedead.com 'bash /var/www/elysium/deploy/set-backup-remote.sh'
#
# Use a NEW B2 application key for this app, not another app's: in Backblaze, create a key limited
# to the bucket with the file name prefix "elysium/", so this site can't read or change
# other apps' backups. The key is read from a hidden prompt and stored only in this user's private
# rclone config (mode 600); it never appears on screen, in shell history or in the process list.
set -euo pipefail

PREFIX=elysium
RCLONE="$HOME/bin/rclone"

# rclone isn't installed system-wide, so this user keeps its own verified copy (no sudo).
if [[ ! -x "$RCLONE" ]] && ! RCLONE=$(command -v rclone); then
    RCLONE="$HOME/bin/rclone"
    echo "Installing rclone into ~/bin..."
    case "$(uname -m)" in x86_64) arch=amd64 ;; aarch64) arch=arm64 ;; *) echo "Unsupported CPU: $(uname -m)" >&2; exit 1 ;; esac
    version=$(curl -fsSL https://downloads.rclone.org/version.txt | awk '{print $2}')
    [[ "$version" =~ ^v[0-9]+\.[0-9]+\.[0-9]+$ ]] || { echo "Couldn't read the rclone version." >&2; exit 1; }
    tmp=$(mktemp -d); trap 'rm -rf "$tmp"' EXIT
    zip="rclone-$version-linux-$arch.zip"
    curl -fsSL -o "$tmp/$zip" "https://downloads.rclone.org/$version/$zip"
    curl -fsSL -o "$tmp/SHA256SUMS" "https://downloads.rclone.org/$version/SHA256SUMS"
    (cd "$tmp" && grep " $zip\$" SHA256SUMS | sha256sum -c -)
    python3 -m zipfile -e "$tmp/$zip" "$tmp/x"
    mkdir -p "$HOME/bin"
    install -m 755 "$tmp/x/rclone-$version-linux-$arch/rclone" "$RCLONE"
    echo "Installed $("$RCLONE" version | head -1)."
fi

read -r -p "B2 bucket name [mybrewfest]: " bucket
bucket=${bucket:-mybrewfest}
read -r -p "B2 keyID (for the elysium key): " key_id
read -r -s -p "B2 applicationKey (hidden): " app_key
echo
[[ -n "$bucket" && -n "$key_id" && -n "$app_key" ]] || { echo "All three values are needed." >&2; exit 1; }

umask 077
# Written with shell builtins so the key never appears in the process list on this shared server.
# hard_delete=false: rclone only ever hides files, so B2 lifecycle rules and Object Lock decide deletion.
mkdir -p "$HOME/.config/rclone"
CONF="$HOME/.config/rclone/rclone.conf"
printf '[b2]\ntype = b2\naccount = %s\nkey = %s\nhard_delete = false\n' "$key_id" "$app_key" > "$CONF"
chmod 600 "$CONF"

REMOTE="b2:$bucket/$PREFIX"
echo "Checking access to $bucket/$PREFIX/..."
probe="$(mktemp)"
echo "Elysium Hospitality backup connection test $(date -u +%FT%TZ)" > "$probe"
"$RCLONE" copyto "$probe" "$REMOTE/connection-test.txt"
rm -f "$probe"
echo "$REMOTE" > "$HOME/.backup-remote"
echo "Connected. Nightly backups will now also be copied to $REMOTE."
echo "Run one now with: bash /var/www/elysium/deploy/backup.sh"
