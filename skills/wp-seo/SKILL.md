---
name: wp-seo
description: Stage 5 of a wp-site-kit WordPress build - titles and descriptions, canonical URLs, one schema owner and JSON-LD, sitemaps, robots.txt and llms.txt, AI crawler access, redirects (301 vs 302), legacy URL maps, Search Console, and adding languages one pilot at a time with Polylang (hreflang, .po/.mo strings, native review). Use for "SEO", "schema", "redirect", "old URLs", "translate the site", "add French/Spanish", "hreflang", "canonical".
---

# Stage 5: SEO and languages

Goal: search engines and AI assistants understand every page. Old URLs land in the right place.
Each extra language is as correct as the original.

## SEO steps
1. **One owner per job.**
   - The SEO plugin (Rank Math by default) owns titles, meta, sitemaps and the main schema graph.
   - The core's `schema.php` adds the site's own types to that graph.
   - Silence any domain plugin that prints its own schema, so there is one graph, not two that disagree.
2. **Every page in `tests/helpers/site.ts` passes `npm run test:seo`:**
   - title 10–65 characters;
   - description 50–160;
   - absolute canonical;
   - one H1;
   - `lang` attribute;
   - zero Google Fonts requests.
   Checklist and writing rules: `references/seo-checklist.md`.
3. **Structured data:**
   - Organization (or LocalBusiness) once.
   - The item type per page (Service, Product, TouristTrip, Course, …).
   - Breadcrumbs.
   - FAQ only for questions really answered on the page.
   - Never an `Offer` without a price.
   - Validate with Google's Rich Results Test and the Schema.org validator.
4. **Robots, sitemap, llms.txt:**
   - live allows crawling and lists the sitemap; staging blocks everything (the core does this);
   - `/llms.txt` is served by the core;
   - decide on purpose whether AI crawlers may read the site, then check the edge isn't blocking them by default (`wp-ship` > `cloudflare-edge.md`).
5. **Redirects:** follow the project's `docs/reference/REDIRECT_RULES.md`. Map every old URL that has traffic or backlinks to its closest new page (301, one hop), and test each one.
6. **Parity before big changes.** Before a redesign, migration or language launch, capture title, description, canonical and H1 for every URL. After each step, compare. Re-capture only for intended changes, with the reason in the commit.
7. **Search Console and Bing:**
   - verify both;
   - submit the sitemap;
   - watch page indexing weekly for the first month;
   - Search Console keeps only 16 months of data, so export or turn on the bulk export early.

## Language steps (each language is a pilot first)
Follow `references/multilingual.md`. In short:
1. **Rule zero:** the original language doesn't change. Its URLs, titles, canonical tags and text stay identical (the parity check proves it).
2. **Pilot:** the language foundation (Polylang, the switcher, hreflang, strings in `.po/.mo`), then the key pages and the top items. Then a native-speaker review on staging, then publish.
3. **The rest** comes in batches, each through the same review.
4. A language that isn't published yet answers **302** to the original language, and appears nowhere: not in sitemaps, hreflang, menus or the switcher.

## Gate (tick in SITE_STATUS.md)
- [ ] `npm run test:seo` green on staging; Rich Results test clean on one page of each type
- [ ] Sitemap, robots.txt and llms.txt correct for live
- [ ] Old-URL redirect map in place and tested (or "n/a")
- [ ] Each extra language: pilot passed, native review done, hreflang pairs complete, canonical per language

## Bugs we shipped once, so check for them
- The translated home page's canonical pointed at the original home page, so Google would drop `/fr/` from French results. The canonical of a translated page is the translated URL.
- English JSON-LD on French pages: the schema must follow the page's language, or be left out until it is translated.
- A gate that 301'd an unpublished language to the home page. Gates are 302s.
