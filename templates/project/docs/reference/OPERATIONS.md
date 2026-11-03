# Operations — {{SITE_NAME}}

## Weekly
- Enquiries inbox: leads arrive. In wp-admin > Enquiries, any with "Email sent = 0"?
- 404 log / Search Console: real old URLs → add 301s (`REDIRECT_RULES.md`).
- Updates waiting: WordPress, plugins, theme (apply on staging first, see below).
- `scripts/backup.sh` (local), and check that the host's backups ran.

## Monthly
- Updates applied: staging first → `npm test` against staging → live. Note the versions in `SESSION_LOG.md`.
- Users: remove anyone who no longer needs access; 2FA still on for every admin.
- Speed: Lighthouse on the home page and one item page; compare with the budgets.
- Restore check: open the newest backup zip and check it holds `db.sql.gz` and `repo.bundle`.

## Quarterly / yearly
- Restore drill (`DISASTER_RECOVERY.md` §5).
- Rotate keys and tokens; review `CREDENTIALS_INVENTORY.md`.
- Re-check the Cloudflare IP list in the core's `edge-security.php` against cloudflare.com/ips.
- Domain renewal date, TLS certificates, and privacy notice still true (retention period, tools used).

## Update policy
- Security releases: within 48 hours, staging first.
- Minor releases: monthly batch. Major WordPress or plugin versions: on staging, with the full suite, and a backup first.
- Never update plugins directly on live through wp-admin.
- Abandoned plugin (no update for a year, or closed on wordpress.org): plan a replacement.

## Alerts
- Uptime: an external check on `/` and `/contact/` every 5 minutes (for example a free uptime service or a cron + push notification).
- Errors: watch the PHP error log on the server weekly. Any "Fatal error" means a fix that week.
- Form failures: the enquiry retry job emails what it can. A rising count of "Email sent = 0" is an alert.
