# Design system: tokens, skins, and the port

## Tokens
- One `:root` block per skin in `skins/<skin>/tokens.css` (and the prototype's `static/css/tokens.css`).
- Groups: colour (`--color-*`), type (`--font-*`, `--step-*` fluid sizes), space (`--space-*`), shape (`--radius`, `--shadow`), layout (`--content-width`).
- Components (`skin.css`) use `var(--…)` only. No hex, `rgb()`, font names or pixel sizes outside tokens. A skin is "new tokens + a few overrides", not a rewrite.
- Name tokens by role (`--color-primary`), not by value (`--blue`), so a new skin can change the value without the name lying.

## Skins in the theme
```
themes/<slug>-child/
  skin.php                 # <prefix>_skin(), <prefix>_part(), preview + cache headers
  skins/default/tokens.css
  skins/default/skin.css
  skins/default/parts/     # optional overrides of theme parts/
  skins/dev-try/           # dev-* skins never appear on production
  parts/offer-card.php     # default layout parts
```
- Choosing: admin preview `?skin=<name>` (sends `Cache-Control: private, no-store` so the preview is never cached for visitors). Saved choice: `wp option update <prefix>_skin <name>`. Fallback: the `<PREFIX>_SKIN` constant, then `default`.
- `<prefix>_part( 'offer-card', $args )` loads `skins/<skin>/parts/offer-card.php` if it exists, else `parts/offer-card.php`.

## Porting the prototype
1. Copy the token values from `static/css/tokens.css` into `skins/default/tokens.css`.
2. Move the component rules into `skin.css`. Keep class names the core already prints (`offer-card`, `enquiry-form`, `enquiry-notice`), or add a skin part override.
3. Build each template from core calls: `<prefix>_get_offers()`, `<prefix>_render_offer_card()`, `<prefix>_render_enquiry_form()`, `<prefix>_photo()`.
4. Replace placeholder images with library photos (`wp-content` skill).
5. Compare prototype and WordPress side by side at 375 px and 1440 px. Then make the visual baselines in the pinned Docker image (`npm run test:visual:update`), after the owner says yes.

## Accessibility checklist for every skin
- Text contrast ≥ 4.5:1 (large text ≥ 3:1); also check focus rings and placeholder text.
- Visible `:focus-visible` style; never `outline: none` without a replacement.
- Tap targets ≥ 44 × 44 px on mobile.
- Motion: at most one expressive moment (usually the hero). Respect `prefers-reduced-motion`.
- One H1 per page; headings in order; descriptive link text.

## Fonts
- `.woff2` only; subset (for example with `pyftsubset`) to the scripts the site's languages need.
- Declare `@font-face` with `font-display: swap` in the skin tokens file.
- Licence noted in `assets/fonts/README.md`.
- The theme already turns off Astra's Google Fonts. The structure test fails if `fonts.googleapis.com` appears anywhere in the theme.
