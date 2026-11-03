# Deploy hardening — {{SITE_NAME}}

Apply on every new server (staging and live), then tick it in `SITE_STATUS.md`.
The core plugin already sends security headers, disables XML-RPC, hides the REST user list
and blocks `?author=` enumeration. This file covers what the application can't do itself.

## 1. Apache `.htaccess`, above `# BEGIN WordPress`
```apache
# --- {{SLUG}} hardening ---
<Files xmlrpc.php>
  Require all denied
</Files>
# A real system cron calls cron (section 4), so the URL is closed.
<Files wp-cron.php>
  Require all denied
</Files>
# Version-leaking core files
<FilesMatch "^(readme\.html|license\.txt|wp-config-sample\.php)$">
  Require all denied
</FilesMatch>
# Dumps, backups, logs and editor leftovers
<FilesMatch "\.(ini|log|sql|bak|old|orig|save|swp|zip|gz|tar)$">
  Require all denied
</FilesMatch>
Options -Indexes
```

## 1b. Uploads: no script runs from `wp-content/uploads/`
The tracked file `wordpress/{{SLUG}}/web/wp-content/uploads/.htaccess` ships with every deploy.
**Test it with a real file.** A missing `uploads/x.php` returns 404 even when the rule is broken,
because WordPress answers first. So put a plain-text file named `uploads/probe.php` on the server,
`curl` it and expect **403**; `uploads/probe.txt` must return 200. Delete both.

## 2. nginx (if the host runs nginx)
```nginx
location = /xmlrpc.php  { deny all; }
location = /wp-cron.php { deny all; }
location ~* /(readme\.html|license\.txt|wp-config-sample\.php)$ { deny all; }
location ~* \.(ini|log|sql|bak|old|orig|save|swp)$ { deny all; }
location ~* /wp-content/uploads/.*\.(php[0-9]?|phtml|phar)$ { deny all; }
autoindex off;
```

## 3. `wp-config.php`, above "That's all, stop editing"
```php
define( 'WP_ENVIRONMENT_TYPE', 'production' );   // 'staging' on staging
define( 'DISABLE_WP_CRON', true );               // real cron below
define( 'DISALLOW_FILE_EDIT', true );
define( 'FORCE_SSL_ADMIN', true );
define( '{{PREFIX_UC}}_ENQUIRY_TO', 'inbox@{{DOMAIN}}' );   // where leads go
define( '{{PREFIX_UC}}_MAIL_FROM', 'noreply@{{DOMAIN}}' );  // sender with SPF/DKIM
// Staging only: define( '{{PREFIX_UC}}_ENQUIRY_TO', '<tester inbox>' ); never the business inbox.
```
Check the database charset too: `DB_CHARSET` must be `utf8mb4` (`scripts/check-emoji.sh`).

## 4. Real cron instead of page-load cron
Every 5 minutes (cPanel > Cron Jobs, or crontab):
```
*/5 * * * * cd <docroot> && wp cron event run --due-now >/dev/null 2>&1
```
The enquiry retry and purge jobs depend on it. Check: `wp cron event list`.

## 5. Accounts
- The admin login is not the public author name. Set a different display name and nicename (they live in the DB, not in git).
- 2FA for every admin (a 2FA plugin or the host's SSO). Remove users who no longer need access.
- Production SSH key: protected by a passphrase, held in its own agent with confirm-on-use (`ssh-add -c`). Every connection asks a human.
  A lock that is only an environment variable is no lock: an agent can set the variable itself.

## 6. Cloudflare (edge)
- WAF rule: block `/xmlrpc.php`. Rate-limit `/wp-login.php` (for example 5 requests per minute per IP).
- More in the `wp-ship` skill, file `references/cloudflare-edge.md`.

## 7. Vulnerability scan before launch
Free WPScan API token (wpscan.com), then against **staging**:
```bash
docker run --rm wpscanteam/wpscan --url https://staging.{{DOMAIN}} \
  --enumerate vp,vt --api-token "$WPSCAN_API_TOKEN" --random-user-agent
```
Record the result in `SECURITY_PRELAUNCH.md`.
