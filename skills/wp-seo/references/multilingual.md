# Adding languages: one pilot at a time

Default tool: **Polylang (free)**. The original language stays at `/`; the others live at `/xx/`.
The method matters more than the tool.

## Rule zero
**The original language doesn't change.** Every URL, title, description, canonical, schema item
and visible word stays identical. The only allowed changes on original pages are new hreflang links and
the language switcher. A parity check proves this after every step.

## Architecture
- **Language logic lives in the core plugin:** link helpers (`<prefix>_page_url()`, `<prefix>_is_page()`), the switcher markup, template mapping and the content import.
  - The theme calls these helpers.
  - No `pll_*` call in a theme file. Every `pll_*` call sits behind `function_exists`, and the site renders the same original language with Polylang switched off.
- **UI strings are gettext calls.** Translations live in `.po/.mo` files in the repo (the theme's and the core's `languages/` folders), never in the database or a plugin's strings table.
  - Keep a source file per language (for example JSON).
  - Build `.po/.mo` from it with a script.
  - Typography rules (French spaces before `?` `!` `:` `%`, quotation marks) are applied by the build script, not by hand.
- **Facts have one source.** Price, duration, photos and other hard facts are synced from the original item. Never type a price into translated content.
- **Policy text** (deposits, cancellation, fees) comes only from the core's policy function and its `.mo`.

## Content import
- Translated content is created by an idempotent, guarded script fed from reviewable JSON in `docs/data/content-changes/`.
- The script **never updates, deletes or re-saves an original-language row**. Prove it: the original rows' `post_modified` and meta are identical before and after a run, and a test checks this.
- Mark every imported item as a draft for native review (for example meta `<prefix>_copy_status = <lang>-draft-pending-review`).

## Copy rules for translators (people or models)
- Natural language, not word-for-word. Use the formal or informal form the brand uses (for example French "vous").
- **Add nothing, drop nothing.** No new places, meals, times, promises or grades. If the original is unclear, stay close to it and list the place in the report.
- **Keep a glossary** (`docs/data/i18n/<lang>-glossary.md`) and use it every time: product words, place names, booking words (deposit, balance, quote).
- **Never claim what isn't confirmed.** A guide speaking the target language is a claim the owner must confirm per item. Until then, leave the language out.
- Guest reviews stay in the guest's original words; only the labels around them are translated.
- **Native review on staging before publishing.** The owner, or a reviewer they name, approves each batch.

## SEO per language
- hreflang: every page links to all its translations, plus `x-default` (usually the original). The pairs must be complete both ways.
- Canonical: each translation's own URL. A translated page pointing its canonical at the original is a bug that removes it from search.
- Titles and descriptions are written for that language's searches, not copied.
- Schema follows the page language; leave out any item that isn't translated yet.
- Sitemaps include only published languages.

## Hidden (not yet published) languages
- `/xx/` answers **302** to the original language, plus `nocache_headers()`. Never a 301 (see `REDIRECT_RULES.md`).
- Left out of sitemaps, hreflang, the switcher, menus, internal links and schema.
- A test proves the gate lifts cleanly when the language is switched on.

## Tests per language
- Pages answer 200, and hreflang and canonical are correct.
- No original-language UI words left on translated pages (a list of common words to look for).
- Facts match the original (price, duration, photos).
- The switcher links each page to its own twin, including the home page.
- Strings: the `.mo` is newer than the `.po`, and the build script runs clean.

## Polylang gotchas we hit
- One plugin fatal-errored on activation under WP-CLI. Run plugin switches with `--skip-plugins=<name>` on staging and live, and check WP-CLI output for fatals.
- An imported page got the slug `contact-2` because `contact` was taken. Check slugs after every import.
- Menu links fell back to the original language: link helpers must ask for the post in the current language explicitly.
- The front page in a translation needs the language home URL (`pll_home_url()`), not `home_url()`.
