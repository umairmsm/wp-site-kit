# Performance: budgets and how to measure

## Budgets (staging, mobile, a mid-range phone on 4G)
| Metric | Budget |
|---|---|
| LCP (largest contentful paint) | ≤ 2.5 s |
| CLS (layout shift) | ≤ 0.1 |
| INP (interaction to next paint) | ≤ 200 ms |
| Total transfer on the home page | ≤ 1 MB (aim for 500 KB) |
| Hero image | ≤ 150 KB desktop, ≤ 100 KB mobile |
| Requests on first load | ≤ 40 |
| TTFB (cached) | ≤ 600 ms |

## Measure
- Lab: Lighthouse (mobile) on staging, three runs; take the median.
- Field: Cloudflare Web Analytics (Core Web Vitals per page) once real visitors arrive.
- Page weight: in the browser, `performance.getEntriesByType('resource')` and sum `transferSize` (wire) and `decodedBodySize` (unpacked). Don't use `content-length`: Cloudflare compresses responses and drops that header, which once produced a wrong number in a report.

## What usually fixes it
- **Images:** WebP or AVIF; `srcset` with an 800 px small size (`<prefix>_photo()` does this); `loading="lazy"` and `decoding="async"` below the fold; the LCP image `eager` with `fetchpriority="high"`; width and height set to stop layout shift. Resize before upload (on the source site, resizing cut 21 images by 72%).
- **Fonts:** self-hosted `.woff2`, subset; preload only the one or two first-screen fonts (`*-preload.woff2`); `font-display: swap`.
- **Plugin assets:** dequeue a domain plugin's CSS and JS on pages where you render your own UI (the core's `<prefix>_dequeue_styles` filter). One site cut page weight by about 80% this way.
- **Caching:** a page cache (host or plugin), long cache for versioned static assets, an image CDN when traffic grows.
- **Speculative loading:** the core sets prefetch on hover (WordPress 6.8+). Prerender can double-count analytics.
- **Expensive PHP:** cache derived data with `<prefix>_cached()`. Look for slow queries with Query Monitor locally. Keep autoloaded options small (`wp option list --autoload=on --format=total_bytes`).

## Don't
- Add a "speed plugin" that rewrites HTML before measuring what is actually slow.
- Lazy-load the LCP image.
- Inline huge CSS; keep critical CSS small or skip it.
