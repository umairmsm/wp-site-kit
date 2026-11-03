# SEO checklist and writing rules

## Per page
- **Title** (10–65 characters): what the page is, then the brand. One title per page across the whole site; no two the same.
- **Description** (50–160): what the visitor gets and why it matters to them. A plain sentence, not a keyword list.
- **H1**: exactly one, close to the title, in plain words.
- **Canonical**: the page's own absolute URL. On translated pages, it is the translated URL.
- **Images**: alt text says what is in the photo; file names are words (`harbour-sunrise.webp`), not camera numbers.
- **Internal links**: every important page is reachable within three clicks, with descriptive link text ("3-day walking tour", not "click here").
- **Content**: answer the questions people actually ask (use Search Console queries and real customer emails). Facts must come from the owner.

## Site-wide
- One canonical host (apex or www); everything else 301s to it. HTTPS only.
- XML sitemap from the SEO plugin. Utility pages (search, thank-you, form states) are noindex (the core handles them) and out of the sitemap.
- `robots.txt`: allow everything public, and list the sitemap. Never block CSS or JS.
- `/llms.txt` (served by the core): the site name, one-line description, and links to pages and items with a short summary each.
- 404 page: helpful, links to the main sections and search, returns a real 404 status. WordPress must not "guess" a redirect for a 404 (the core turns that guessing off).
- Speed and Core Web Vitals: see `wp-quality` > `performance.md`.

## Structured data
- One graph per page, owned by the SEO plugin; the core adds to it (`rank_math/json_ld`).
- Organization or LocalBusiness: name, URL, logo, `sameAs` (only profiles the business really owns and runs).
- Item pages: the most specific true type. Add `offers` only with a real price and currency.
- Reviews and ratings in schema: only real, first-party reviews shown on the page. Never import aggregate counts from elsewhere.
- FAQ: only questions answered on that page, in that page's language.
- Validate after every schema change (Rich Results Test, Schema.org validator), and keep one test per type.

## Local and trust signals
- A Google Business Profile with the same name, address and phone as the site.
- Links from real profiles (TripAdvisor, Facebook, and so on) only if they exist and are active. Photos on review sites belong to their authors; don't copy them.

## Crawling other sites (research, migrations)
`robots.txt` is a convention; the **Terms of Use** are the contract. Before any scraper or
importer runs against a site you don't own, check its robots.txt on the exact host, its
Terms of Use, and whether an API or licence exists. One adapter that robots.txt allowed was
switched off hours later because the Terms forbade scraping. For your own old site, crawl
politely (2 seconds or more between requests); one host rate-limited and blocked real visitors during a fast crawl.

## Parity baseline (before redesigns and migrations)
1. Capture for every URL: status, title, description, canonical, H1, hreflang and JSON-LD types.
2. After each step, compare. Every difference is either a bug or an intended change.
3. Re-capture only the routes with intended changes, and list them and the reason in the commit body.
