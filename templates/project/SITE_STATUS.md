# SITE_STATUS — {{SITE_NAME}}

The current stage is the first stage with an unticked gate. Tick a gate only with evidence:
a link, commit, screenshot, test output or date. Write the evidence after the dash.
Started: {{DATE}} with wp-site-kit.

## 1 Kickoff (skill: wp-kickoff)
- [ ] `docs/BRIEF.md` approved by the owner —
- [ ] Locked decisions and business hard rules in `AGENTS.md` —
- [ ] Private remote exists; first push by the owner —
- [ ] `docs/reference/CREDENTIALS_INVENTORY.md` started (names only) —
- [ ] Replacing a live site: URL and feature inventory in `docs/data/legacy/` (or "n/a") —

## 2 Design (wp-design)
- [ ] `docs/DESIGN_DIRECTION.md` written —
- [ ] Static prototype in `static/` signed off by the owner (screenshots + date) —
- [ ] Tokens defined; fonts self-hosted (0 requests to Google Fonts) —

## 3 Build (wp-build)
- [ ] `ddev start` works and `wp_get_environment_type()` prints `local` —
- [ ] Core plugin and child theme active; skin loads —
- [ ] Content types hold real items; templates render them through core functions —
- [ ] Enquiry saved → forwarded → purged after retention; tested end to end —
- [ ] `npm run test:structure` green; `npm run lint:php` 0 errors —

## 4 Content (wp-content)
- [ ] Every public page has owner-approved copy (approval date per page or batch) —
- [ ] Real photos imported with alt text; AI images logged —
- [ ] Privacy notice, terms and any booking or refund policy published —
- [ ] No `PENDING-` facts and no placeholder text left on public pages —

## 5 SEO & languages (wp-seo)
- [ ] Titles, descriptions, canonical, one H1, schema: `npm run test:seo` green —
- [ ] Sitemap, `robots.txt` and `llms.txt` correct for live —
- [ ] Old-URL redirect map in place (or "n/a") —
- [ ] Each extra language: pilot passed, native review done, hreflang pairs complete —

## 6 Quality (wp-quality)
- [ ] `npm test` fully green; nothing skipped without an owner-approved reason —
- [ ] Accessibility: 0 axe violations on key pages —
- [ ] Performance budgets met on staging (LCP, CLS, INP, page weight) —
- [ ] `gitleaks` clean; `shellcheck` clean —

## 7 Ship (wp-ship)
- [ ] Staging behind Access, noindex, mail guarded —
- [ ] Hardening applied (`docs/reference/DEPLOY_HARDENING.md`) —
- [ ] First safe deploy to staging from a commit; smoke test green —
- [ ] launch-auditor PASS on staging —
- [ ] Cutover plan approved: date, rollback, mail (MX) checked —
- [ ] Live deploy; launch-auditor PASS on live —
- [ ] 7-day watch done: errors, 404s, forms, analytics —

## 8 Operate (wp-operate)
- [ ] Backups scheduled, with an off-machine copy —
- [ ] Restore drill done (date, minutes taken) —
- [ ] Update and alert routines written in `docs/reference/OPERATIONS.md` —
