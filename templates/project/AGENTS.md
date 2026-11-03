# AGENTS.md — {{SITE_NAME}}

> Always loaded. Read this, then `SITE_STATUS.md`, then the newest lines of `SESSION_LOG.md`, before any task.
> `CLAUDE.md` and `GEMINI.md` are symlinks to this file: one set of rules for every agent. Keep it under 150 lines.

## What this is
{{SITE_NAME}} ({{DOMAIN}}): a WordPress site built with wp-site-kit.
Owner: `<name, role>`. Approves design and customer copy: `<name>`. Current stage: see `SITE_STATUS.md`.

## Project map
- `wordpress/{{SLUG}}/`: the DDEV install (docroot `web/`)
  - `web/wp-content/mu-plugins/{{SLUG}}-core/`: **all logic**. Modules: `environment edge-security content media enquiry seo schema performance`
  - `web/wp-content/themes/{{SLUG}}-child/`: layout only (templates, `parts/`, `skins/<skin>/`, `languages/`)
- `static/`: design prototype (stage 2)
- `scripts/`: `backup.sh`, `deploy-safe.sh`, `apply-content-change.sh`, `check-emoji.sh`, `probe.sh`, `workstation-doctor.sh`, `make-state-bundle.sh`
- `tests/`: Playwright (`structure seo accessibility security forms visual`)
- `docs/`: `BRIEF.md`, `DESIGN_DIRECTION.md`, `data/`, `reference/` (security, hardening, redirects, recovery, credentials), `supervision/` (task files), `team/BRIDGE.md`

## Commands
- Dev: `cd wordpress/{{SLUG}} && ddev start`, then `ddev wp …`. `ddev wp eval 'echo wp_get_environment_type();'` must print `local`.
- Tests: `npm test`. One suite: `npm run test:seo` (see `package.json`). Visual baselines only in the pinned Docker image: `npm run test:visual:docker`.
- Lint: `composer install` once, then `npm run lint:php` (0 errors).
- Backup: `scripts/backup.sh`. Deploy (owner only): `scripts/deploy-safe.sh <commit> --target staging|live`.

## Architecture rules
- **Plugin-first:** logic, data, policy text, forms and schema live in `{{SLUG}}-core`. Theme = layout; skin = look. A template calls one core function per section.
- Prefix everything with `{{PREFIX}}_`: functions, options, post types, meta, hooks and nonces. Text domains are `{{SLUG}}-core` and `{{SLUG}}-child`.
- Cards and prices only through `WP_Query` and `{{PREFIX}}_render_offer_card()`. Never hard-code a price, slug or card.
- Enquiries are saved (`{{PREFIX}}_enquiry`) before any forward. Forms post to `admin-post.php` with a nonce. No API keys or third-party endpoints in the page.
- Redirects: moved for good → 301; hidden for now → 302 + no-cache (`docs/reference/REDIRECT_RULES.md`).
- Fonts self-hosted, no Google Fonts calls. WCAG 2.1 AA. One H1 per page.

## Content rules
- **The DB is content.** Change it through a reviewable JSON file in `docs/data/content-changes/` and `scripts/apply-content-change.sh` (dry run first).
- AI-written copy is a draft until the owner approves it. Facts come from the owner (`docs/BRIEF.md`). Unknown = `PENDING-<who>`.
- Real photos for named places, rooms, products and people. AI scenery only as `ai-*` files, logged in `docs/data/photos/AI_IMAGES.md`.
- Never show review counts, ratings or testimonials that can't be proven.

## Always do
- Branch → local test → diff → owner merges. Conventional Commits. Commit after each meaningful step.
- Run the relevant tests before each commit. Add a `SESSION_LOG.md` line at the end of each session.
- Check before you say "done": open the page, run the test, read the response.

## Ask first
- Installing plugins, themes, tools or paid services (page builders: never).
- Any price, currency, policy, brand or logo change. Publishing a language.
- Anything touching DNS, Cloudflare, hosting, staging or live. Destructive DB or file commands.
- Sending code or customer data to a third-party AI API.

## Never do
- Never merge, push, deploy or `ssh` to a server without the owner's OK for that exact action.
- Never commit secrets, `wp-config.php`, DB dumps, backups or `.env` files. Dumps contain customer data.
- Never weaken, skip, delete or re-snapshot a test to get green.
- Never ship AI-generated PHP to production without branch → local test → diff → review.

## Delegated builds
Specs live in `docs/supervision/TASK_*.md`, with one `### Step N` per commit (template: `TASK_TEMPLATE.md`).
The builder does only the current step, runs its checks, commits once and stops. If something is unclear, answer `BLOCKED` with the options. Never guess.

## When stuck
Read `docs/reference/` and the latest `docs/supervision/REPORT_*.md` before guessing.
