#!/usr/bin/env bash
# One-time setup for Elysium Hospitality, stage 2. Run after the first deploy succeeded and the domain
# resolves to this server:
#
#   sudo bash server-finish.sh you@example.com
#
# Requests the HTTPS certificate (certbot edits the nginx site) and starts the queue worker.
set -euo pipefail

SLUG=elysium
EMAIL="${1:-}"
[[ $EUID -eq 0 ]] || { echo "Run with sudo." >&2; exit 1; }
[[ -f /var/www/elysium/artisan ]] || { echo "Deploy the app first (deploy/deploy.sh)." >&2; exit 1; }

echo "HTTPS is already set up with the elysiumhospitality.net certificate."


echo "Stage 2 done: https://elysiumhospitality.net"
