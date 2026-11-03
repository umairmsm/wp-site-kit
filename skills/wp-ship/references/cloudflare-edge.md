# Cloudflare edge settings

All changes here are the owner's. An agent proposes the exact change (record, value, reason) and
waits for a yes. Use an API token scoped to the one zone with only the permissions needed, and
list it by name in `CREDENTIALS_INVENTORY.md`.

## DNS
- Move DNS to Cloudflare early (stage 1). Copy every record from the old DNS host first: MX, SPF/DKIM/DMARC TXT, verification records.
- Web records (`@`, `www`): **DNS only** until the new origin is tested. Then turn the proxy on.
- One canonical host (apex or www). The other redirects with a 301.

## SSL/TLS
- Mode **Full (strict)**, with a Cloudflare Origin CA certificate on the server.
- Minimum TLS 1.2. Turn on "Always Use HTTPS" and "Automatic HTTPS Rewrites".
- **HSTS in steps.** Start with max-age 1 day and no subdomains. Raise it to 6 months once everything works. Add `includeSubDomains` only when every subdomain is HTTPS for good. Add `preload` last, because it is very hard to undo.

## Speed
- Brotli and HTTP/3 on. Early Hints on if the plan has it.
- Cache rules:
  - **bypass** for `/wp-admin/*`, `/wp-login.php`, `/wp-json/*`, logged-in cookies (`wordpress_logged_in_*`), and URLs with `?enquiry=` or `?skin=`;
  - cache static assets for a long time.
- Assets must be versioned (the theme adds the file time to every CSS and JS URL). A cached old script next to new data broke a whole page once. Versioning fixes that; purging the cache doesn't.

## Security
- WAF custom rules: block `/xmlrpc.php`; rate-limit `/wp-login.php` (for example 5 requests per minute per IP); block `/.env`, `/.git`, `*.sql` and `/wp-config.php` probes.
- Bot Fight Mode: test that forms and payment callbacks still work after you turn it on.
- **Access** in front of staging (email PIN for people, a service token for tests).

## AI crawlers and robots
- Cloudflare can serve a **managed robots.txt** that blocks AI crawlers (GPTBot, ClaudeBot, CCBot) at the edge. You won't see it in your source. Decide on purpose whether the business wants to be in AI answers. If it does, turn that rule off and keep `/llms.txt`.
- Check `https://<domain>/robots.txt` in a browser after any change: what visitors get is what counts.

## Analytics
- Web Analytics (RUM) sets no cookies, so it usually needs no consent banner. Still name it in the privacy notice, and check your own regulator's guidance (rules differ by country). **Turn lite mode off**, or EU visitors are not counted.
- The free plan allows one rule. Point it at the live host, and filter reports by hostname.
