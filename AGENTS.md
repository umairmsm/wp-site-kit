# AGENTS.md — wp-site-kit

> Rules for any agent changing this kit. `CLAUDE.md` and `GEMINI.md` are symlinks to this file.

## What this is
A Claude Code plugin: stage skills (`skills/`), read-only reviewer agents (`agents/`), a starter
WordPress project (`templates/project/`) and its generator (`bin/wp-site-new`).

## Layout
- `skills/<name>/SKILL.md`: frontmatter `name` = folder name; a `description` with trigger phrases (≤ 1024 characters); a body that is short and step by step. Long checklists go in `references/`.
- `agents/*.md`: read-only reviewers (tools: Read, Grep, Glob, Bash).
- `templates/project/`: every file is copied into a new site. Tokens: `{{SITE_NAME}} {{SLUG}} {{PREFIX}} {{PREFIX_UC}} {{DOMAIN}} {{PHP_VERSION}} {{DATE}} {{YEAR}} {{KIT_VERSION}}`. File and folder names use `__SLUG__`.
- `tests/selftest.sh` (fast, no Docker) and `tests/playground-smoke.sh` (a real WordPress via Playground).

## Rules
- **Before every commit:** `tests/selftest.sh` passes. After any change under `templates/project/wordpress/`, `tests/playground-smoke.sh` passes too.
- **Template PHP** follows WordPress Coding Standards: PHPCS 0 errors with the generated `phpcs.xml.dist`. Prefix every global with `{{PREFIX}}_`. Escape output, sanitize input.
- **The starter obeys its own rules:** no logic, prices or Google Fonts in the theme; every module is required in the bootstrap.
- **Nothing private:** no real names, emails, business domains, account IDs, server names or prices. Use `example.com`, the prefix `acme` and neutral examples. The self-test runs the scrub scanner when one sits next to the kit.
- **Scripts:** bash with `set -euo pipefail`, shellcheck clean, `--dry-run` for anything that writes, and a human confirmation for anything touching live.
- **Tests and docs never start Docker or DDEV.** `ddev wp` on a stopped project starts it, and that happened once by accident.
- Conventional Commits. Never push or publish without the owner.
