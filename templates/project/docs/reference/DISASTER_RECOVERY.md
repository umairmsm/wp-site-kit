# Disaster recovery and machine moves — {{SITE_NAME}}

## 0. What lives where
| Asset | Source of truth | In git? | In `scripts/backup.sh` zip? |
|---|---|---|---|
| Core plugin, child theme, scripts, tests, docs, `.ddev/config.yaml` | git (private remote) | yes | yes (+ `repo.bundle` with every branch and stash) |
| WordPress core, Astra, plugins | reinstallable | no | yes (working tree) |
| **Database** (content, settings, users, enquiries) | **the live DB** | no | yes (`_dr/db.sql.gz`, local copy) |
| **Uploads** (Media Library) | disk | no | yes |
| Photo originals | owner's folder: `<path>` | no | no: back it up separately |
| Secrets (keys, passwords, tokens) | password manager / `_private/` | **never** | **never**: carry them by hand, encrypted |
| Machine-only config (`deploy/targets.conf`, `config.local.yaml`) | each machine | no | no: `scripts/make-state-bundle.sh` (encrypted) |
| Claude memory | `~/.claude/projects/<path>/memory/` | no | yes (snapshot) |
| Live server code | the last `deploy-safe.sh` commit (`~/deploy-state/` on the server) | yes | — |
| Live server DB | the server; `~/backups/` before each deploy or content change | no | — |

## 1. Back up
- Local: `scripts/backup.sh` (add `--encrypt` before copying it anywhere). Weekly, and before risky work.
- Live: the host's daily backups, plus `wp db export` before every deploy and content change (the scripts do this).
- One copy off the machine: another drive, or encrypted cloud storage. Write where: `<location>`.

## 2. Restore on a new machine (from a backup zip)
```bash
unzip <name>-<stamp>.zip && cd <name>
git clone _dr/repo.bundle /tmp/check && rm -rf /tmp/check     # the bundle is readable
git remote -v                                                   # the remote is still set
cd wordpress/{{SLUG}} && ddev start
ddev import-db --file=../../_dr/db.sql.gz
ddev wp eval 'echo wp_get_environment_type();'                 # must print: local
cd ../.. && npm install && composer install
scripts/workstation-doctor.sh
npm run test:structure
```
Then restore the machine-only files (`gpg -d state-<date>.tar.gpg | tar -x`) and the secrets
(password manager), and copy the Claude memory folder to `~/.claude/projects/<new path>/memory/`.
The folder name is the project's absolute path with `/` replaced by `-`.

## 3. Restore from git only (no backup zip)
Code comes back; **content does not**. Start DDEV, install WordPress, and then:
- import the newest DB dump you can find (the live server's `~/backups/`, or the host's backups);
- re-sync uploads from the server: `rsync -a <host>:<docroot>/wp-content/uploads/ wordpress/{{SLUG}}/web/wp-content/uploads/`;
- remove customer data from the copy if it isn't the owner's own machine.

## 4. Restore live
- Bad code deploy: `scripts/deploy-safe.sh <previous good commit> --target live`, or swap back the `prev` folder that the script printed.
- Bad content change: the DB export the script made in `~/backups/` (`wp db import`), or a reverse content-change file (after becomes before).
- Server lost: new host → hardening (`DEPLOY_HARDENING.md`) → code via `deploy-safe.sh --assume-live <commit>` → DB from the newest backup → DNS.

## 5. Drill log
Restore once per quarter onto a spare machine or a fresh DDEV project, and time it.
| Date | Who | From | Minutes | Problems found |
|---|---|---|---|---|
