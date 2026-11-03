---
name: wp-design
description: Stage 2 of a wp-site-kit WordPress build - design direction, a static HTML/CSS prototype with design tokens, owner sign-off with screenshots, self-hosted fonts, and porting the approved look into a swappable WordPress skin. Use for "design the site", "new look", "redesign", "change colours or fonts", "make a new skin", "port the prototype to WordPress".
---

# Stage 2: Design

Goal: the owner has approved a look they have seen on a phone and a laptop. It is written as tokens, so it
can become a WordPress skin by copying, and be replaced later without losing features.

## Steps
1. **Direction.** Fill the project's `docs/DESIGN_DIRECTION.md`: north star, audience, what we must
   not look like (competitors, templates), and the non-negotiables. Offer two or three directions at most,
   as small mood boards or hero mockups, not full sites.
2. **Prototype in `static/`.** Plain HTML and CSS. `css/tokens.css` uses the same names as the
   theme's `skins/default/tokens.css`. Components use tokens only. Real structure (header, hero, cards,
   form, footer), placeholder copy, no prices and no policy text.
3. **Accessibility from the start:**
   - contrast ≥ 4.5:1 for text;
   - visible focus;
   - one H1;
   - a skip link;
   - keyboard paths;
   - `prefers-reduced-motion` respected.
   Check the prototype with axe (browser extension) before showing it.
4. **Owner review.** Screenshots of every page at phone (375 px) and laptop (1440 px) widths. Record the
   approval date in `DESIGN_DIRECTION.md` and save the screenshots in `docs/design/`.
5. **Fonts:** self-host `.woff2` files, subset to the needed languages, check the licence, and preload the one or two
   first-screen faces (name them `*-preload.woff2`). No Google Fonts requests anywhere.
6. **Port to WordPress** (`references/design-system.md`):
   - copy the tokens into the skin;
   - move component CSS into `skin.css`;
   - lay out templates with the core's render functions;
   - add skin part overrides only where the markup must differ.
   The look changes; the core's data and markup contracts don't.
7. **Later redesigns** are new skins: copy `skins/default` to `skins/<name>`, change tokens, preview with
   `?skin=<name>` (admins), and switch with the `<prefix>_skin` option once approved.

## Gate (tick in SITE_STATUS.md)
- [ ] `DESIGN_DIRECTION.md` written; owner approval recorded with screenshots
- [ ] Tokens defined; components use tokens only
- [ ] Fonts self-hosted; 0 requests to Google Fonts

## Lessons
- The locked design changed when the business partner sent his own redesign. Because the look was a
  skin, the switch kept every feature. Build every design as a skin, even the first one.
- Don't copy a competitor's palette, type pairing or layout. Owners notice, and visitors don't remember you.
- A test-only skin (`dev-*`) for trying ideas never shows on production.
- If the `frontend-design` or `design-motion-principles` skills are installed, use them for direction and motion.
