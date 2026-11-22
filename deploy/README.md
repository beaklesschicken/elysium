# Deploying Elysium Hospitality

Runs on the shared VPS (`ericthedead.com`, SSH port 4666) at `/var/www/elysium`, as its own
`elysium` user with its own PHP-FPM pool, following the same pattern as MyBrewFest.
Generated from Eric's `vps-web-app` template; adjust freely.

| File | Runs as | What it does |
| --- | --- | --- |
| `server-setup.sh` | admin with `sudo`, once | User, folder, database, PHP pool, nginx, backup schedule. For an existing site: maintenance mode first, backup, log review, permission lockdown |
| `server-finish.sh` | admin with `sudo`, once | HTTPS (certbot) and queue worker |
| `deploy.sh` | `elysium` | Pull `production`, build, back up, migrate, cache; `--stay-down`, `--go-live`, `--status` |
| `backup.sh` | `elysium`, nightly at 08:00 UTC | Database + `storage/app` into `~/backups` (14 days), copied to B2 |
| `set-env-secret.sh` | `elysium` | Puts a secret (Postmark token, mail password) into `.env` from a hidden prompt |
| `set-backup-remote.sh` | `elysium`, once | Installs rclone for the user and connects `b2:<bucket>/elysium` |

## First-time setup

0. Commit and push `deploy/`. Point DNS for elysiumhospitality.net at the server (new domains).
1. Copy the setup files and your public key up (run from the repository folder):

   ```bash
   ssh ericthedead.com 'mkdir -p ~/elysium-setup' && scp -r deploy ~/.ssh/id_ed25519.pub ericthedead.com:elysium-setup/
   ```

2. Setup (asks for your sudo password):

   ```bash
   ssh -t ericthedead.com 'sudo bash ~/elysium-setup/deploy/server-setup.sh ~/elysium-setup/id_ed25519.pub'
   ```

3. First deploy (stays in maintenance mode; prints a preview link):

   ```bash
   ssh elysium@ericthedead.com 'bash -s' < deploy/deploy.sh
   ```

4. HTTPS and queue worker:

   ```bash
   ssh -t ericthedead.com 'sudo bash ~/elysium-setup/deploy/server-finish.sh you@example.com'
   ```

5. B2 backups: in Backblaze create a key for bucket `mybrewfest`, file name prefix `elysium/`,
   read and write. Then:

   ```bash
   ssh -t elysium@ericthedead.com 'bash /var/www/elysium/deploy/set-backup-remote.sh'
   ```

6. Check the site with the preview link, then go live and clean up:

   ```bash
   ssh elysium@ericthedead.com 'bash -s -- --go-live' < deploy/deploy.sh
   ```

   ```bash
   ssh ericthedead.com 'rm -rf ~/elysium-setup'
   ```

## Later deploys

```bash
ssh elysium@ericthedead.com 'bash -s' < deploy/deploy.sh
```
