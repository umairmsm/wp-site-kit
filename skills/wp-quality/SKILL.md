---
name: wp-quality
description: Stage 6 of a wp-site-kit WordPress build, and the checks behind every other stage - Playwright suites (structure, SEO, accessibility, security, forms, visual in a pinned Docker image), PHPCS with WordPress Coding Standards, shellcheck, gitleaks, performance budgets and Core Web Vitals. Use when adding or fixing tests, when a test fails, before a merge, or when asked "is the site ready", "run the checks", "why is this test failing", "make the site faster".
---

# Stage 6: Quality

Goal: the suite proves the rules hold, and stays honest. The checks run all the time; stage 6
is where every last one turns green on staging.

## The suites (project `tests/`)
| Suite | Command | Proves |
|---|---|---|
| structure | `npm run test:structure` | modules loaded; no logic, prices or Google Fonts in the theme; pages 200 with no PHP errors; noindex outside production |
| seo | `npm run test:seo` | title, description, canonical, one H1, lang; robots per environment; sitemap; `/llms.txt` |
| accessibility | `npm run test:a11y` | 0 serious or critical axe violations (WCAG 2.1 AA) |
| security | `npm run test:security` | headers, no generator tag, `?author=`, REST users 401, XML-RPC |
| forms | `npm run test:forms` | form shape (nonce, honeypot, consent, no keys); a local submission reaches Mailpit |
| visual | `npm run test:visual:docker` | screenshots match the approved baseline, **only in the pinned Docker image** |

Details, patterns and how to add a test: `references/test-suites.md`.
Speed budgets and how to measure: `references/performance.md`.

## Rules
1. **Never weaken a test to get green.** No deleting or skipping a test, no loosening an assertion, no re-snapshotting. If a test is wrong, say why, and the owner decides. A renamed test counts as removed until the owner approves the rename.
2. **A failing test is information.** Read the error, reproduce it, fix the cause. Re-run once to tell a network blip from a real failure, and write down which it was. No automatic retries.
3. **Guard tests must fail when broken.** After writing one, break the thing on purpose (revert the fix or plant a bad value), watch the test fail, then restore it. Write "revert-checked" in the commit message.
4. **Baselines come from the pinned image.** Visual snapshots made on a laptop won't match a server or another OS. Make them only with `npm run test:visual:update`, after the owner approves the look.
5. **Tests that write data never run against production.** Form submissions run locally only (`IS_LOCAL`).
6. **Every new template gets its URL in `tests/helpers/site.ts` (`PAGES`)**, so every suite visits it.

## Other gates
- PHP: `composer install` once, then `npm run lint:php`: **0 errors**. Fix warnings, or explain them with a targeted `phpcs:ignore … -- reason`.
- Shell: `npm run lint:sh` (shellcheck) is clean.
- Secrets: `npm run scan:secrets` (gitleaks) finds nothing. Never print a secret value, even in a report.

## Gate (tick in SITE_STATUS.md)
- [ ] `npm test` fully green; nothing skipped without an owner-approved reason
- [ ] Accessibility: 0 serious or critical violations on every page in `PAGES`
- [ ] Performance budgets met on staging (`references/performance.md`)
- [ ] gitleaks and shellcheck clean; PHPCS 0 errors
