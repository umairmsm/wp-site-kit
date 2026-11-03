---
name: wp-kickoff
description: Stage 1 of a wp-site-kit WordPress build. Use when starting a new WordPress website or taking over or rebuilding an existing one - discovery brief, stack decision, creating the project with bin/wp-site-new, locked decisions, private repo and credentials inventory. Triggers - "new WordPress site", "start a client website", "rebuild this site", "take over this WordPress site".
---

# Stage 1: Kickoff

Goal: one page of truth about the business, and an empty project with every file the method
expects. Nothing is designed or built yet.

## Steps
1. **Is there a live site already?** If yes, treat it as a running business, not a parked
   domain. First take inventory (`references/discovery-brief.md`, "Existing site"):
   - every URL (from its sitemaps), form, payment step, blog post and contact detail;
   - its Search Console data, if the owner can share it;
   - what earns money today. The rebuild must keep all of it working.
   Crawl politely: read the Terms of Use first (robots.txt is not permission) and leave 2 seconds or more between requests.
2. **Discovery brief.** Ask the questions in `references/discovery-brief.md` and write the
   answers to `docs/BRIEF.md`. Mark each fact with its source (owner, document, crawl).
   If you don't know a fact, write `PENDING-<who>`. Never guess.
3. **Stack.** Start from the default in `references/stack.md`. Change a layer only for a
   reason you can state, and record it in `DECISIONS.md`.
4. **Create the project** with the kit's scaffolder (two folders up from this skill):
   ```bash
   bash <kit>/bin/wp-site-new --name "Acme Tours" --slug acme-tours --prefix acme \
        --domain example.com --dir ~/sites/acme-tours --git
   ```
   - `slug`: lowercase and hyphens. It names the DDEV project, the folders and the text domains.
   - `prefix`: 2 to 12 lowercase letters or digits. Every function, option, post type, meta key, hook and nonce starts with `<prefix>_`.
5. **Fill `AGENTS.md`:**
   - what the site is;
   - who approves design and customer copy;
   - the locked decisions;
   - hard rules specific to this business, such as regulated claims, licences to show, or words the owner never wants.
6. **Private remote, set up on day 1.** The owner creates a private repo and pushes. Once the local site
   exists (stage 3), run `scripts/backup.sh` and keep one copy on another machine.
7. **Credentials inventory** (`docs/reference/CREDENTIALS_INVENTORY.md`):
   - list every account (registrar, DNS, host, SMTP, analytics, Search Console, payments, CDN);
   - write where each secret lives;
   - names only, never values.
8. Tick the Kickoff gate in `SITE_STATUS.md` with evidence: the brief's approval date, the first commit SHA, the remote name.

## Gate
- [ ] `docs/BRIEF.md` approved by the owner (date)
- [ ] Locked decisions and business hard rules in `AGENTS.md`
- [ ] Project created and committed; private remote exists
- [ ] Credentials inventory started (names only)
- [ ] Replacing a live site: URL and feature inventory saved in `docs/data/legacy/` (or "n/a")

## Pitfalls we hit
- Treating "cutover" as a DNS change while the old site was taking paid bookings that day.
- An old site's review counts ("1,118 reviews") turned out to be made up. Don't carry them over.
- Proxying the domain through Cloudflare while the old host rate-limited by IP. The host saw
  only Cloudflare's IPs, so real visitors got 429 errors. Check the origin's limits before
  you turn the proxy on.
- Trusting another agent's notes that said "resolved". The fix was unpushed, untested and
  never deployed. Check the real thing.
- Someone pasted an API key into an agent chat. Rotate any key an agent has seen.
