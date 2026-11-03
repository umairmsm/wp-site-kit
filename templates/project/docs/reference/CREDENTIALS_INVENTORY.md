# Credentials inventory — {{SITE_NAME}}

**Names and places only. Never a value.** Values live in a password manager, or in git-ignored files on
the owner's machines (`_private/`, `.env`). When moving machines, carry them encrypted, out of band.
Rotate any secret that an AI agent has seen in a chat, a log or a prompt.

Last reviewed: {{DATE}}

| # | Secret | Used by | Where the value lives | Who has it | Rotated |
|---|---|---|---|---|---|
| 1 | Domain registrar login | owner | password manager | | |
| 2 | Cloudflare account (+ API token scoped to this zone) | owner, deploy checks | password manager | | |
| 3 | Hosting panel / SSH key for live (passphrase, confirm-on-use) | `deploy-safe.sh` | owner's machine, ssh agent | | |
| 4 | WordPress admin (live, staging) | owner | password manager | | |
| 5 | Database password (live, staging) | `wp-config.php` on the server | server only | | |
| 6 | SMTP / mail provider | `wp-config.php` | password manager | | |
| 7 | Cloudflare Access service token (staging) | tests, smoke checks | `_private/staging-access.env` | | |
| 8 | Search Console / Bing Webmaster | owner | Google / Microsoft account | | |
| 9 | Analytics | owner | Cloudflare account | | |
| 10 | Payment provider (if any) | owner | password manager | | |

## Rules
- A new secret gets a row here before it is used.
- `wp-config.php`, `.env`, `_private/`, `deploy/targets.conf`, dumps and backups are git-ignored. `gitleaks` checks the repo.
- Agents never print a secret value, even in a report: name the file and the line instead.
