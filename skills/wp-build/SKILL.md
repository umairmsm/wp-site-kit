---
name: wp-build
description: Stage 3 of a wp-site-kit WordPress build - local DDEV site, the core must-use plugin (modules), the presentation-only child theme with skins, the content model (custom post types, meta, WP_Query, render functions), and the enquiry pipeline that saves leads before forwarding. Use when building or changing features, templates, content types, forms or plugin modules on a wp-site-kit site.
---

# Stage 3: Build

Goal: the site runs locally. Real items render through core functions. Enquiries save, then
forward, then purge. The structure tests are green.

Read first: the project's `AGENTS.md`, then `references/architecture.md`.

## Steps
1. **Local site.** Follow the project's `README.md`, section "First run". Then check:
   `ddev wp eval 'echo wp_get_environment_type();'` must print `local`.
   If it doesn't, see `references/local-env.md`. Until it prints `local`, the site thinks it is production: it can be indexed and can email real customers.
2. **Rename the example content type.** Do this before content exists. `<prefix>_offer` becomes the business's word (tour, service, course): the post type, the rewrite slug and the labels in `modules/content.php`, plus the function names (`_get_offers`, `_render_offer_card`), the `parts/offer-card.php` file and the tests. Post type names have a 20-character limit.
3. **Model the content.** Find every field the brief needs. Register each as post meta with a sanitize
   callback and an auth callback. Prices are numbers in meta. 0 means "on request", never "free".
   If editors need richer field screens, add ACF (free) on top, but read values only through core functions.
4. **Data files → generator** (when the owner supplies many items):
   - keep one Markdown or JSON file per item in `docs/data/<type>/`;
   - write an idempotent WP-CLI script that creates or updates posts from those files;
   - give it a `--dry-run`;
   - run it with `ddev wp eval-file - < scripts/<name>.php`.
   The files are the reviewable truth; the DB is the copy.
5. **Templates call one core function per section.**
   - Layout lives in the theme: templates, `parts/`, skin overrides.
   - Data, queries, prices, policy sentences and forms come from the core.
   - Guard each call with `function_exists()`, so a template never fatals if the plugin is missing.
6. **Enquiries.** Keep the pipeline in `modules/enquiry.php` (`references/forms-and-leads.md`).
   - Add fields in three places together: the form, the handler and the details box.
   - Set the inbox and the sender in `wp-config.php` constants, never in code.
7. **Policy text** (terms, deposits, cancellation): put it in the core as one function (for example `<prefix>_policy( 'cancellation' )`). Templates print that function, and a test fails if a template hard-codes a policy sentence.
8. **New module?** Add one file in `modules/`, one `require_once` line in the bootstrap, and update `tests/structure/core.spec.ts` in the same commit.
9. **Check and commit.**
   - Run `npm run test:structure` and `npm run lint:php` (0 errors).
   - Ask the **wp-rules-reviewer** agent to review the diff.
   - Commit (Conventional Commits).

## Gate (tick in SITE_STATUS.md)
- [ ] `wp_get_environment_type()` = local
- [ ] Core plugin and child theme active; skin loads
- [ ] Real items render through core functions; no hard-coded cards, prices or slugs
- [ ] Enquiry: saved → emailed → retry → purge; tested end to end in Mailpit
- [ ] Structure tests green; PHPCS 0 errors

## Never
- Business logic, `WP_Query`, prices, `pll_*` calls or policy text in the theme.
- Raw `$_POST` or `$_GET` without `wp_unslash` + a sanitize function; output without `esc_*`.
- Third-party API keys or endpoints in HTML or JS. Forward from the server.
- Toggling plugin modules with raw `wp option update`. Use the plugin's own UI or CLI, then re-check every page it affects.
- Unprefixed functions, options, hooks, meta keys or nonces.
