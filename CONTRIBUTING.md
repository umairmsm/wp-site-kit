# Contributing to wp-site-kit

Thank you for helping. The kit only holds what has worked on a real site, so every change needs evidence.

## Ways to help
1. **Field reports.** You built a site with the kit: what went wrong, what was missing, what you had to change. Open an issue with the "Field report" template.
2. **Fixes** to the starter (`templates/project/`), the scaffolder (`bin/wp-site-new`) or the tests.
3. **Skills and references** (`skills/<name>/`): a lesson you proved on a live site, written as a step or a checklist line, with the reason.

## Rules
- Keep the architecture: logic in the core must-use plugin, layout in the child theme, look in a skin. Every name carries `{{PREFIX}}_`.
- Never weaken a safety step (save-before-email, deploy rollback, the before/after content guard, noindex outside production) without an issue that explains why.
- Never delete or skip a test to get green.
- No real business names, emails, domains or keys in examples: use `example.com` and the prefix `acme`.
- Keep `SKILL.md` files short; long checklists go in `references/`.

## Before you open a pull request
```bash
tests/selftest.sh                 # must end with "SELFTEST: all passed"
tests/playground-smoke.sh         # when you touched PHP in the starter (needs node; no Docker)
```
If you add or remove a file, update `MANIFEST.txt` (the self-test checks it).
For PHP changes, run PHPCS on a scaffolded sample: `composer install && vendor/bin/phpcs` inside it.

Use Conventional Commits (`fix(starter): …`, `docs(wp-seo): …`). By contributing you agree your work is released under the MIT licence.
