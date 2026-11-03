# Static prototype (stage 2)

Design here first, in plain HTML and CSS. It is quick to change, and easy to show the owner before any WordPress work starts.

- `css/tokens.css` uses the **same variable names** as the theme's `skins/default/tokens.css`. Porting the look is a copy, not a rewrite.
- Components in `css/base.css` use tokens only.
- Placeholder text and cards are fine here. Prices and policy sentences are not: write "Price on request" and "[policy text]".
- Fonts may come from a font service while you explore. Before the WordPress port, self-host them (`.woff2` in the theme's `assets/fonts/`).
- Review: show it on a phone and on a laptop. Save screenshots with the date to `docs/design/` and record the owner's approval in `docs/DESIGN_DIRECTION.md`.
- After the port, this folder is no longer the source of truth. The WordPress build is.

Open `index.html` directly in a browser, or serve the folder: `npx serve static` / `python3 -m http.server -d static 4180`.
