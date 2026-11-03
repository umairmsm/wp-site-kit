# Test suites: patterns that held up on a live site

## Layout
```
tests/
  helpers/site.ts        # BASE_URL, TARGET_ENV, PAGES, paths, PHP_ERROR, filesUnder()
  structure/core.spec.ts # repo rules + every page renders
  seo/meta.spec.ts       # on-page SEO + robots/sitemap/llms.txt
  accessibility/axe.spec.ts
  security/hardening.spec.ts
  forms/enquiry.spec.ts
  visual/home.visual.spec.ts
```
On the source site, a growing suite (about 670 tests across 3 viewports) took about 15 minutes. Run the targeted suite while you work, and the full suite before a merge.

## Environments
- Local: `npm test` uses `https://<slug>.ddev.site`.
- Staging: `BASE_URL=https://staging.<domain> TARGET_ENV=staging npm test`, with the Access service token in the environment.
- Live, read-only: `BASE_URL=https://<domain> TARGET_ENV=production npx playwright test tests/seo tests/security tests/structure`.
  Production tests only read. They never submit forms or log in.

## Kinds of test, and when to use each
| Kind | Example | Notes |
|---|---|---|
| Repo rule (no browser) | the theme has no `WP_Query`; no prices in templates | Cheap and fast, and turns a hard rule into a guard |
| Request (no browser) | headers, status codes, robots.txt | Use the `request` fixture; run on one project only |
| Page | one H1, canonical, a form's shape | `page.goto` + locators |
| Behaviour | submit the form, check Mailpit | Local only; one project |
| Visual | screenshot vs baseline | Pinned Docker image only |
| Parity | old vs new title, description and canonical for every URL | Before a redesign or migration: capture a baseline, then compare. A planned change means re-capturing the baseline, with the reason in the commit |

## Guard tests worth adding as the site grows
- **Policy text single source**: templates must not contain policy sentences; they call the core's policy function.
- **Content parity across languages**: every translated item has the same price, duration and photos as the original.
- **No untranslated strings on translated pages**: no English UI words on `/fr/` pages.
- **Photo registry**: every key in `photo-library.php` has both files; every `ai-*` key is listed in `AI_IMAGES.md`.
- **Function inventory**: adding or removing a core function updates an allow-list in the same commit, so dead code and surprise functions show up in review.
- **Legacy redirects**: a table of old URL → new URL, each answering 301 with the right `Location`.

## Flaky or failing
1. Re-run the single test once: `npx playwright test <file> -g "<title>" --project=desktop`.
2. Passes on re-run → a blip. Note it in the session log. If the same test blips twice, it is a bug: fix it (wait for the right state, not a timeout).
3. Fails again → real. Find the cause in the code or content. Changing the test needs the owner's OK and a reason in the commit.
4. Never add `retries`, `test.skip` or a bigger timeout just to get green.

## Common causes we hit
- **Site not up** when a gate started (HTTP 000 on every probe). Start DDEV and wait for `/` to answer 200 before running.
- **Visual baselines from another machine.** They differ in fonts and anti-aliasing. Use the pinned image.
- **Environment flag lost**: the site reported `production` locally, so dev-only features were hidden and tests failed in confusing ways. Check `wp_get_environment_type()`.
- **Renamed tests** read as "removed" by a gate. Get the owner's approval and list the renames in the commit.
