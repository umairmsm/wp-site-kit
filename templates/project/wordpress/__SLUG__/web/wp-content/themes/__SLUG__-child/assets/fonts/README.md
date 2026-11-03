# Fonts (self-hosted)

- `.woff2` only. Subset to the scripts the site's languages need (Latin plus Latin Extended covers most of Western Europe).
- Name the one or two fonts the first screen needs `*-preload.woff2`. `functions.php` preloads exactly those.
- Declare `@font-face` in the skin's `tokens.css`, with `font-display: swap`.
- Check the licence allows self-hosting on the web (most open fonts do). Note the source and licence here.
- Never link fonts from Google's servers. Test: `npm run test:seo` checks for 0 `fonts.googleapis.com` / `fonts.gstatic.com` references.
