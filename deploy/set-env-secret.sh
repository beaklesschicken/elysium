#!/usr/bin/env bash
# Stores a secret in the server's .env from a hidden prompt, so it never appears on screen, in shell
# history, in chat, or in the process list (other accounts on this server can see command lines).
# Run from your own machine (-t gives the prompt a terminal):
#
#   ssh -t elysium@ericthedead.com 'bash /var/www/elysium/deploy/set-env-secret.sh POSTMARK_TOKEN'
set -euo pipefail

KEY="${1:-}"
case "$KEY" in
    POSTMARK_TOKEN|MAIL_PASSWORD|MAIL_CONTACT_TO) ;;
    *) echo "Usage: $0 POSTMARK_TOKEN|MAIL_PASSWORD|MAIL_CONTACT_TO" >&2; exit 1 ;;
esac
cd /var/www/elysium

read -r -s -p "Value for $KEY (hidden): " VALUE
echo
[[ -n "$VALUE" ]] || { echo "Nothing entered; nothing changed." >&2; exit 1; }
[[ "$VALUE" != *$'\n'* ]] || { echo "The value can't contain a line break." >&2; exit 1; }

# Double-quoted for .env, escaping \ " and $ so Laravel never expands ${...}.
QUOTED=${VALUE//\\/\\\\}; QUOTED=${QUOTED//\"/\\\"}; QUOTED=${QUOTED//\$/\\\$}
LINE="$KEY=\"$QUOTED\""

# Rewritten with shell builtins only (no sed/printf child processes holding the value).
umask 077
TMP=$(mktemp .env.XXXXXX)
trap 'rm -f "$TMP"' EXIT
found=0
while IFS= read -r line || [[ -n "$line" ]]; do
    if [[ "$line" == "$KEY="* ]]; then
        echo "$LINE" >> "$TMP"; found=1
    else
        echo "$line" >> "$TMP"
    fi
done < .env
[[ $found == 1 ]] || echo "$LINE" >> "$TMP"
chmod 600 "$TMP"
mv "$TMP" .env
trap - EXIT
unset VALUE QUOTED LINE

php artisan config:clear >/dev/null
php artisan optimize >/dev/null
echo "Saved $KEY."
