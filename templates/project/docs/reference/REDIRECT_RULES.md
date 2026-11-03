# Redirect rules (any website we build)

> Why this exists: a hidden-language gate once sent every `/es/…` request a **permanent 301** to
> the home page. Browsers and Google remember a 301, so `/es/` would have kept going home even
> after that language launched. It became a temporary 302. These rules stop the same mistake.

## 1. Pick the status code by what is true
| Situation | Code | Example |
|---|---|---|
| The page **moved for good** and the old address will never come back | **301** (or 308 for non-GET) | `/about-us/` → `/about/`; old item URL → new item URL |
| The page is **hidden for now** and will open later (launch gate, unpublished language, maintenance, seasonal page, login wall, A/B test, geo routing) | **302** (or 307) | `/es/` while Spanish is unpublished |
| The page **never existed** | **404** | a mistyped or guessed URL |
| The page **existed and is gone for good**, with no replacement | **410** | a deleted item with no equivalent |

Ask: *"Will this old address ever serve its own page again?"* If yes, or if you're not sure, it is **not** a 301.

## 2. Where to send people
- To the **closest equivalent page** (old item → same item, old category → its hub). **Never send everything to the home page.** Google treats a mass redirect to the home page as a soft 404 and passes on no value.
- Only to a page that **exists and returns 200**. Test the destination, not just the redirect.
- **One hop only.** No chains (A → B → C), no loops.
- Permanent legacy maps run **only on a 404**, so a real page at the old path always wins.

## 3. How to write it (WordPress)
- `wp_safe_redirect()` with an **allow-listed map** (a fixed array in the core plugin). Never redirect to a URL taken from the request: that is an open redirect.
- Temporary gates also call `nocache_headers()`, so no browser or CDN keeps the redirect.
- Put the reason in a code comment: *gate* (302) or *moved* (301).
- A hidden section must also be **left out everywhere else**: sitemaps, hreflang, the language switcher, menus, internal links and structured data. A redirect alone does not hide it.

## 4. Before and after you change one
- Every redirect has a test that asserts the **exact status code and `Location`**.
- A gate has a test proving it **lifts cleanly** when the switch is turned on (no leftover redirect).
- **Changed a 301 by mistake?** Browsers cache it with no expiry. Fix it at once, purge the CDN, and expect Google to take weeks to re-crawl.
- After launch, watch Search Console for 404s and redirect errors. Add real old URLs (from backlinks or analytics) to the permanent map.

## 5. Quick checklist
- [ ] Moved for good → 301; hidden for now → 302; never existed → 404; gone for good → 410
- [ ] Destination is the closest equivalent and returns 200 (not the home page by default)
- [ ] One hop, allow-listed map, `wp_safe_redirect()`
- [ ] Temporary gate: `nocache_headers()`, and hidden from sitemap, hreflang, menus and links
- [ ] A test asserts the code and `Location`; a gate test proves it lifts cleanly
