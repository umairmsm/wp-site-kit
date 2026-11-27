# Changelog

## Unreleased
- Lessons from the source site since 0.1.0, in the skills: claims (written prices, quoting the partner's own words, per-region contacts, rebuilding derived copies after live content), multilingual (Polylang query scoping, layout in longer languages, pinned string counts), test results (colour codes in counts, compare with main, long suites in tmux), deploys (same-day staging content, a content ledger, per-language smoke paths, hosting gotchas, shared-account risk), operations (off-server copies of live backups, live restore drills), supervision (eleven loop failures and their rules, steering a running loop) and working with the owner.
- Starter: a failed enquiry save emails the lead marked NOT SAVED, and shows an error only if that email fails too.
- Starter: `scripts/backup.sh` stops when it cannot dump the database; `--files-only` makes a files-only zip on purpose.
- Starter: `deploy/targets.conf.example` says to add each public language's pages to `SMOKE_PATHS`.

## 0.2.0 — 2026-11-27
- `bin/loop-queue` and `bin/loop-status`: run `.supervision/QUEUE` unattended with agy-loop and show a phone-sized status picture; wait and retry on reviewer outages and usage limits; one runner per project.
- New projects get `.supervision/QUEUE`, `.supervision/status.conf(.example)` and `PHONE.md`.
- `tests/loop-dashboard.sh`, run by the self-test.
- Fix: `loop-queue` classifies only the STOP line the current run wrote, so a crash is never mistaken for an old usage limit.
- Starter fixes: prefix must be 4+ characters (WPCS); no enquiry cron jobs during `wp core install`; README first run creates the Contact page; a static front page's excerpt supplies the front-page meta description.

## 0.1.0 — 2026-11-03
First public version.
- Ten skills: `wp-site` (stage map and hard rules), `wp-kickoff`, `wp-design`, `wp-build`, `wp-content`, `wp-seo`, `wp-quality`, `wp-ship`, `wp-operate`, `wp-task-file`.
- Two read-only agents: `wp-rules-reviewer`, `launch-auditor`.
- Starter project via `bin/wp-site-new`:
  - core must-use plugin (8 modules) and Astra child theme with skins;
  - DDEV config;
  - Playwright suites (structure, SEO, accessibility, security, forms, visual in a pinned Docker image);
  - PHPCS (WPCS 3 + PHPCompatibilityWP);
  - scripts for safe deploys, guarded content changes, backups, probes, emoji checks, a workstation doctor, an encrypted state bundle and a photo review/import;
  - reference docs.
- Verified:
  - self-test;
  - PHPCS 0 errors and 0 warnings;
  - gitleaks clean;
  - the suites pass against WordPress Playground (32 passed, 0 failed);
  - the Playground smoke test passes.
