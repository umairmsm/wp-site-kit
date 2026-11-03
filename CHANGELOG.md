# Changelog

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
