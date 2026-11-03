# SECURITY_PRELAUNCH — {{SITE_NAME}}

Tick each line with evidence (command output, date). Re-run the whole list after a host move.

## Checklist
- [ ] `npm run test:security` green on staging (headers, xmlrpc, `?author=`, REST users, generator)
- [ ] `DEPLOY_HARDENING.md` sections 1–5 applied on staging and live
- [ ] Uploads guard tested with a real file (`probe.php` → 403, `probe.txt` → 200)
- [ ] `wp-config.php`: environment type, cron off, file edit off, SSL admin, enquiry inbox, mail sender
- [ ] Real cron runs (`wp cron event list` shows due jobs clearing)
- [ ] Admin login ≠ public author name; 2FA on every admin; old users removed
- [ ] No API key, form-service key or endpoint in page source or JS (search the HTML of every template)
- [ ] `gitleaks detect` on the repo: 0 findings; `.env`, dumps and backups are git-ignored
- [ ] WPScan with API token against staging: no unpatched plugin or theme CVE
- [ ] Production SSH key: passphrase + confirm-on-use agent; no unknown keys in `~/.ssh/authorized_keys` on the server
- [ ] Backups: the latest one restores (see `DISASTER_RECOVERY.md`)
- [ ] Any key an AI agent has seen in a chat or a log is rotated

## Scan log
### Scan N — <tool and version> — <date>
- Target:
- Findings (file:line or URL, rule; never paste a secret value):
- Fixed in commit:
- Remaining (with owner decision):
