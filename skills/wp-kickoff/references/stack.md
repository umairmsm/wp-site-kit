# Default stack, and when to change it

Start here. Change a layer only for a reason you can say in one sentence, and record the
change in `DECISIONS.md`.

| Layer | Default | Why | Change it when |
|---|---|---|---|
| Local dev | DDEV (Docker), PHP 8.3, MariaDB 10.11, nginx-fpm, docroot `web/` | Same setup on every machine; `ddev wp` for WP-CLI | The host runs a different PHP: match its version |
| CMS | WordPress (latest), classic PHP templates | Predictable markup, easy to test | The owner edits layouts themselves: consider a block theme |
| Theme | Astra (free) parent + our child theme (layout) + skins (look) | A stable parent with good speed; the look can change without touching layout | The design truly fights the parent: write a lean classic theme instead |
| Logic | Core must-use plugin `<slug>-core`, one module per concern | Can't be switched off by accident; survives theme changes | Never; this is the backbone |
| Content | Custom post types + registered meta | Editors change items in the CMS; templates query them | Complex field UIs: ACF (free) on top |
| Domain plugin | Only when the domain needs it (bookings, shop: WooCommerce) | Don't rebuild payments or carts | Wrap its output in core functions so templates never call it directly |
| SEO | Rank Math (free), the one schema owner | Sitemaps, titles, schema in one place | Yoast or SEOPress if the owner knows it; still only one |
| Languages | Polylang (free): default language at `/`, others at `/xx/`; UI strings in `.po/.mo` in the repo | Free, works with custom types | Many languages or a translation team: consider paid tools |
| Forms | The core `enquiry` module (admin-post, nonce, saved lead) | No third-party keys in the page; leads never lost | A CRM needs it: forward from the server, never from the browser |
| Speed | Page cache (host cache or a cache plugin), image CDN, WebP/AVIF, self-hosted fonts | Fast pages without a page builder | — |
| Edge | Cloudflare DNS: TLS 1.2+, HSTS, Always HTTPS, Brotli, HTTP/3, WAF rules; Access in front of staging | Free, strong defaults; staging stays private | The host's CDN rate-limits by IP: test before you proxy |
| Analytics | Cloudflare Web Analytics (no cookies) | Usually no consent banner; list it in the privacy notice and check your regulator | Marketing needs more: add a consent-gated tool |
| Hosting | Any host with SSH, WP-CLI and rsync; staging = its own folder + database | Lets you deploy from a commit by script | Managed hosts without SSH: adapt `deploy-safe.sh` to their deploy tool |
| Tests | Playwright + axe, PHPCS (WordPress Coding Standards), shellcheck, gitleaks | Catches layout, SEO, a11y, security and secret regressions | — |
| Agents | One `AGENTS.md` for every agent (`CLAUDE.md`, `GEMINI.md` are symlinks); builder agents work from task files | One set of rules; long work runs unattended under review | — |

## Paid options that worked
A cache and optimisation plugin (for example FlyingPress) plus an image CDN (for example
BunnyCDN) gave good speed on shared hosting. Start free; add them when the numbers ask for it.

## Never
Page builders. Google Fonts from Google's servers. Nulled or "pro for free" plugins. A
plugin for something a dozen lines in the core plugin can do.
