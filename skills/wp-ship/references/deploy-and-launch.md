# Staging, deploys, cutover and the first week

## Staging
- **Where:** a subdomain on the same host as live (`staging.<domain>`), with its own folder and its own database. Never share live's database.
- **Private:** a Cloudflare Access app on `staging.<domain>`. People log in with an email PIN. Tests and scripts use a **service token** (`CF_ACCESS_CLIENT_ID` and `CF_ACCESS_CLIENT_SECRET`), kept in a git-ignored env file and listed by name in `CREDENTIALS_INVENTORY.md`.
- **Never indexed:** `WP_ENVIRONMENT_TYPE=staging` in `wp-config.php`. The core then sends noindex headers and a blocking robots.txt. Also untick "Search engine visibility" in WordPress.
- **Never emails customers:** the core's mail guard sends everything to the admin. Set `<PREFIX>_ENQUIRY_TO` to a tester's inbox.
- **TLS:** a Cloudflare Origin CA certificate on the server, and SSL mode "Full (strict)". A new subdomain can show 526 or the host's default page for a minute or two after setup.
- **Data:** copy live's DB to staging only through a script that removes enquiries and customer data. Rewrite URLs with `wp search-replace` (dry run first).

## A deploy, end to end (owner)
```bash
git switch main && git pull                       # the commit must be on origin/main
scripts/deploy-safe.sh <sha> --target staging --dry-run
scripts/deploy-safe.sh <sha> --target staging     # type the 7-character id
npx playwright test --grep-invert @local-only     # BASE_URL=https://staging.<domain> + Access token
scripts/deploy-safe.sh <sha> --target live --dry-run
scripts/deploy-safe.sh <sha> --target live
```
What the script does: it builds from `git archive` and lints PHP. It checks for drift (did someone change the server by hand?) and shows the change list. A human types the commit id. It backs up code and DB, uploads to a staging folder, verifies checksums, swaps everything in one command, flushes the cache and smoke-tests. If the smoke test fails, it rolls back by itself.

**Drift found?** Someone edited files on the server. Find out who and why before you overwrite anything. Their fix might be needed.

## Content changes (DB)
- One JSON file per change: `docs/data/content-changes/<date>-<topic>.json`. It maps item slug → field → `{ "before": …, "after": … }`.
- `scripts/apply-content-change.sh <file> --target staging` is a dry run that reports, per field: will change, already applied, or SKIP (the server has a third value, so someone edited it; it is never overwritten).
- `--apply` writes. For live, a human types `live`.
- Backup first: the script takes `wp db export` before writing.

## Cutover (replacing a live site)
Write `docs/CUTOVER.md` with these headings, and have the owner approve it:
1. **What must keep working:** bookings and payments, forms and their inbox, the top pages by search traffic, company email (MX), and any integrations.
2. **Redirect map:** every old URL with traffic goes to its closest new page with a 301 (`docs/reference/REDIRECT_RULES.md`). Test the map against staging.
3. **DNS:**
   - lower the TTL to 300 a day before;
   - copy every existing record first (MX, TXT/SPF, DKIM, verification records);
   - change only the web records.
4. **Proxy:** if the old host rate-limits by IP, start with DNS only (grey cloud). Turn the proxy on after the new origin is live and tested.
5. **The moment:** deploy code to live, apply content, flush caches, run launch-auditor, send a real enquiry, make a real test booking if there is a payment flow, then refund it.
6. **Rollback:** the exact steps and who runs them: DNS back, or `deploy-safe` from the previous commit, or restore the backup. Keep the old site's files and DB for at least 14 days.
7. **People:** tell the business owner the time window. Somebody watches the inbox that day.

## Origin lock (optional)
To stop visitors from bypassing Cloudflare and hitting the origin directly:
- **Don't** allow-list Cloudflare IPs if the host already rewrites `REMOTE_ADDR` to the visitor's IP. That rule blocked every real visitor on one host.
- Add a secret header with a Cloudflare Transform Rule, and check for it at the origin.

## Week 1 after launch
- **Daily:**
  - server error log;
  - 404 log (add 301s for real old URLs);
  - the forms inbox (did leads arrive?);
  - analytics (real visitors, by country).
- **Search Console:** submit the sitemap, then watch Coverage and Page indexing. Search Console keeps only 16 months of data, so turn on its bulk export early if history matters.
- **Analytics:** Cloudflare Web Analytics with "lite" mode off. Lite mode left EU visitors out. Filter by hostname, because staging gets the beacon too. A plain `curl` never shows the beacon, since Cloudflare injects it at the edge.
- **Speed:** measure page weight with the browser's Resource Timing (`transferSize`, `decodedBodySize`), not `content-length`. Cloudflare compresses responses and drops that header.
- **Day 7:** write a short report in `SESSION_LOG.md`, then tick the gate.
