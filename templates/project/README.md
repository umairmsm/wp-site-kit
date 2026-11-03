# {{SITE_NAME}}

WordPress site for {{DOMAIN}}. Rules for people and agents: `AGENTS.md`. Progress: `SITE_STATUS.md`.

## First run (local)
```bash
cd wordpress/{{SLUG}}
ddev start
ddev wp core download
ddev wp core install --url='https://{{SLUG}}.ddev.site' --title='{{SITE_NAME}}' \
  --admin_user=admin --admin_password=admin --admin_email='admin@{{DOMAIN}}'   # local only
ddev wp theme install astra
ddev wp theme activate {{SLUG}}-child
ddev wp rewrite structure '/%postname%/' --hard
ddev wp eval 'echo wp_get_environment_type();'    # must print: local
cd ../..
npm install && npx playwright install chromium
npm run test:structure
```
DDEV catches all outgoing mail. Open it with `ddev launch -m` (Mailpit).

## Daily
- `ddev start` / `ddev stop` in `wordpress/{{SLUG}}`
- `npm test`, `npm run lint:php`
- `scripts/backup.sh` before risky work

## Layout
See "Project map" in `AGENTS.md`.
