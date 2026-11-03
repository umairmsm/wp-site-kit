# Local environment (DDEV) and WP-CLI habits

## Setup checks
- `ddev start` inside `wordpress/<slug>/`. The site runs at `https://<slug>.ddev.site`.
- `ddev wp eval 'echo wp_get_environment_type();'` must print `local`.
  - DDEV reads `web_environment` from `.ddev/config.yaml`. One DDEV release dropped this setting.
    The fix that worked: `.ddev/config.local.yaml` (git-ignored) with
    `web_environment: [WP_ENVIRONMENT_TYPE=local]`, then `ddev restart`.
  - While it prints `production`, the site is indexable and the mail guard is off.
- Mail: Mailpit catches everything (`ddev launch -m`).
- Ports busy? Set `router_http_port` / `router_https_port` in `config.local.yaml`. Never stop someone else's containers to free a port.

## Data
- The local DB holds real content once you import it. Treat it like production data: no copies to chat tools, no commits.
- Snapshot before risky work: `ddev snapshot --name before-<thing>`. Restore with `ddev snapshot restore before-<thing>`.
- Only a **sanitised** DB may leave the owner's machines. Remove enquiries, users other than admin, and order or customer tables first.

## WP-CLI habits
- Run scripts that live outside the container: `ddev wp eval-file - < ../../scripts/<name>.php`.
- Every write script has `--dry-run` and prints what it would change. Run the dry run first, every time.
- Search-replace: `wp search-replace OLD NEW --dry-run --precise --skip-columns=guid` first. Run it for real only after reading the counts.
- Plugin modules or settings: change them through the plugin's UI or its own CLI commands.
  Flipping Rank Math modules with a raw `wp option update` turned off WordPress's sitemap without starting Rank Math's.
  Re-check every page the change touches.
- After any remote write, read the result back (the page, the option, the file). Don't assume it worked.

## rsync gotcha
`rsync a/x.php b/y.svg host:dest/` drops both files into `dest/`, losing their folders. Sync one
folder per command, or use `rsync -R` with paths relative to a shared base. Then list the
destination and check.

## Keeping machines in step
If more than one machine works on the project, read the `wp-operate` skill (multi-machine):
one bridge file, a workstation doctor, an encrypted state bundle for what git doesn't hold.
