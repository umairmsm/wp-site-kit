---
name: wp-ship
description: Stage 7 of a wp-site-kit WordPress build - staging behind Cloudflare Access, server hardening, safe deploys from a commit (scripts/deploy-safe.sh), database content changes, the launch audit, DNS cutover without breaking a running business, and the first week after launch. Use for "deploy", "set up staging", "go live", "launch", "cutover", "move DNS", "harden the server".
---

# Stage 7: Ship

Goal: staging mirrors live, every deploy is boring, launch day has a rollback, and the first
week is watched.

**The owner runs deploys.** Agents prepare, check and explain. They don't ssh to servers, run
`deploy-safe.sh`, change DNS or touch Cloudflare unless the owner asks for that exact
action. `deploy-safe.sh` needs a terminal and a typed commit id, so an agent can't finish one alone.

## Steps
1. **Staging** (`references/deploy-and-launch.md`, "Staging"): its own folder and database on
   the live host, `WP_ENVIRONMENT_TYPE=staging`, behind Cloudflare Access, a test inbox for enquiries.
2. **Harden** every server with the project's `docs/reference/DEPLOY_HARDENING.md`. Record the
   results in `docs/reference/SECURITY_PRELAUNCH.md`.
3. **Edge**: Cloudflare settings from `references/cloudflare-edge.md`. Before you turn the proxy on
   in front of an old host, test it: some hosts rate-limit by IP and will block Cloudflare.
4. **Deploy code**, owner only:
   - fill `deploy/targets.conf` from the example;
   - run `scripts/deploy-safe.sh <commit> --target staging --dry-run`, read the change list, then run it again without `--dry-run`;
   - the first run on a server that already has code needs `--assume-live <commit>`.
5. **Deploy content separately.**
   - Code never carries prices, pages or legal text.
   - Record each change as `docs/data/content-changes/<date>-<topic>.json` (before and after values per field).
   - Apply it with `scripts/apply-content-change.sh <file> --target staging` (dry run), then `--apply`.
   - After any DB import or move, run `scripts/check-emoji.sh --target <env>`.
6. **Launch audit**: run the **launch-auditor** agent against staging, fix what fails, and run it again until it passes.
7. **Cutover plan.** Write it from `references/deploy-and-launch.md`, "Cutover". The owner approves the date and time, and so does the business partner if the site already takes bookings.
8. **Go live**: deploy to live, run launch-auditor against live, submit the sitemap to Search Console, send a real test enquiry to the real inbox.
9. **Watch for 7 days**: errors, 404s (add redirects), the forms inbox, analytics, Search Console coverage.

## Gate (tick in SITE_STATUS.md)
- [ ] Staging behind Access, noindex, mail guarded
- [ ] Hardening applied and recorded
- [ ] Safe deploy to staging; smoke test green
- [ ] launch-auditor PASS on staging, then on live
- [ ] Cutover plan approved (date, rollback, mail/MX checked)
- [ ] 7-day watch done

## Incidents that shaped these rules
- An agent changed live prices, published AI-written terms and deployed a feature with no review,
  and its report left the deploy out. Since then: deploys come only from merged commits, through the script, typed by a human, and production SSH keys ask for confirmation on every use.
- The contact form on staging posted to a third-party form service with a **test** key, so enquiries
  went to a test inbox. Before launch, send one real enquiry on live and see it arrive.
- Web analytics in "lite" mode didn't count EU visitors for weeks, which hid the core markets. Check the settings on day 1.
- A long deploy (39 commits) stopped on a SIGPIPE from `git log | head` under `pipefail`. The script now captures the output first.
- A server DB import turned emoji into `????`. Run `check-emoji.sh` after every import.
