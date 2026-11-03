# DESIGN_DIRECTION — {{SITE_NAME}}

> The design follows what the owner approves, and it is built as a swappable skin, so it can
> change later without losing features. Record each approval with its date below.

## Approvals
| Date | What was approved | By | Evidence (screenshots, staging URL) |
|---|---|---|---|

## North star (one or two sentences)
<!-- e.g. "Calm, editorial, planned by someone who lives there." -->

## Audience (from docs/BRIEF.md)
<!-- who visits, on what device, from which countries, what they must find in 10 seconds -->

## What we must NOT look like
<!-- competitors' palettes, type pairings and layouts we won't copy; "default template" looks -->

## Decisions
- Palette (token names and values):
- Type (display + body; self-hosted; licence):
- Layout (grid, spacing scale, signature components):
- Motion (one expressive moment at most; reduced-motion respected):
- Photography (real photos only for named places and people; style notes):

## Non-negotiables (carry over whatever the design)
- Self-hosted fonts (no Google Fonts requests).
- WCAG 2.1 AA: contrast ≥ 4.5:1 for text, visible focus, one H1 per page, full keyboard use, reduced-motion support.
- Hero image ≤ 150 KB desktop / ≤ 100 KB mobile; WebP or AVIF; width and height set.
- Every colour, font, radius and shadow is a token (`var(--…)`); no raw values in component CSS.
