# Architecture: plugin-first

## Why
On the site this kit came from, the design changed three times in a month. Features that lived
in templates or skin CSS disappeared or had to be redone after each change. Features that
lived in the core plugin survived every redesign untouched. So:

| Layer | Holds | Never holds |
|---|---|---|
| Core must-use plugin `<slug>-core` | data model, queries, sorting and filtering, forms and lead handling, schema, SEO gaps, policy sentences, plugin JS, emails | layout decisions, colours |
| Child theme `<slug>-child` | templates, `parts/`, menus and chrome, which section goes where | queries, prices, policy text, `pll_*` or other plugin API calls |
| Skin `skins/<name>/` | tokens, component CSS, optional part overrides | logic of any kind |

The core is a must-use plugin: nobody can deactivate it from wp-admin by accident, and it loads before the theme.

## Module layout
```
mu-plugins/
  <slug>-core.php                 # loader: one require_once
  <slug>-core/<slug>-core.php     # bootstrap: constants + one require per module
  <slug>-core/modules/
    environment.php   # is_production(), noindex + mail guard outside production, env badge
    edge-security.php # headers, xmlrpc, user enumeration, REST users, client IP
    content.php       # post types, meta, queries, card data + render, cache helper
    media.php         # photo library helper
    enquiry.php       # form, handler, save → email → retry → purge, privacy tools
    seo.php           # gaps the SEO plugin leaves, utility noindex, /llms.txt
    schema.php        # JSON-LD, joined to the SEO plugin's graph
    performance.php   # emoji off, speculative loading, dequeue hook
  <slug>-core/languages/          # .po/.mo for the core text domain
```
One concern per file. If a module grows past about 400 lines, split it by feature, not by hook type.

## The render pattern
For anything that shows data (cards, lists, policy blocks):
1. `<prefix>_<thing>_data( $id )` returns a plain array with everything the layout needs, already formatted.
2. `<prefix>_render_<thing>( $id )` loads the theme's part through `<prefix>_part()` when it exists. Otherwise it prints working default markup with the shared class names.
3. The theme's `parts/<thing>.php` only arranges and escapes `$args`. A skin can override it in `skins/<skin>/parts/`.

Result: changing the skin or the whole theme never breaks the data, and the plugin always has a fallback.

## Naming
- Prefix every function, option, post type, taxonomy, meta key, hook, nonce action, cron hook, transient and CSS class that belongs to a feature: `<prefix>_`.
- Private meta starts with an underscore: `_<prefix>_email`. It is hidden from the custom fields box.
- Text domains: `<slug>-core` and `<slug>-child`. Strings are translated in code (`__()`, `esc_html_e()`), never stored translated in the DB.
- Post type names: 20 characters at most, so keep the prefix short.

## Caching
- Plain `WP_Query`: no transient; the object cache is enough.
- Expensive derived data (parsing HTML, remote calls, building `llms.txt`): use `<prefix>_cached( $key, $ttl, $builder )`.
  Saving an offer or page bumps the cache version, so editors see their change at once.
  If a value can be `false`, store it wrapped (for example `array( false )`); `false` means "not cached".

## Domain plugins (bookings, shop)
Use the plugin for what it does well: payments, carts, availability. Wrap what the theme shows
in core functions, so templates never call the plugin directly. Dequeue its front-end assets on
pages where you render your own UI. On the source site this cut page weight by about 80%. Silence its schema if
the SEO plugin owns schema.

## Dependencies between theme and plugin
Deploy the theme and the core together, always from the same commit. The theme calls core
functions, and an old core with a new theme (or the reverse) can fatal. `scripts/deploy-safe.sh`
ships both in one step.
