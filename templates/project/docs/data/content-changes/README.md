# Content changes

The database is content. Every change to live content goes through a file here, so it is
reviewable, repeatable and guarded.

- Name: `<YYYY-MM-DD>-<topic>.json`.
- Shape: `{ "<post_type>/<slug>": { "<field>": { "before": "…", "after": "…" } } }`.
- Fields: `post_content`, `post_title`, `post_excerpt`, or a meta key such as `{{PREFIX}}_price_from`.
- Get `before` from the target itself (for example `wp post get <id> --field=post_content`), not from memory.
- Apply: `scripts/apply-content-change.sh <file> --target staging` (dry run), then add `--apply`. Do staging first, then live.
- Who approved the new text or price goes in the commit message (owner, date).

Example: `example-price.json.sample`.
