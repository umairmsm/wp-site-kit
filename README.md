# wp-site-kit

**Build a WordPress site from an empty folder to production, the same careful way every time.**

A Claude Code plugin (also readable by Antigravity, Codex and Gemini through `AGENTS.md`) with:
- eight stage skills;
- a starter project you generate with one command;
- two read-only reviewer agents.

The method was learned on a live, multilingual booking site that real customers pay through:
- a redesign that kept every feature;
- a lead form that never loses an enquiry;
- deploys that roll back on their own;
- a language added without touching the original;
- a launch that replaced a running business without breaking it.

## What's inside
| Part | What it gives you |
|---|---|
| `skills/wp-site` | Start here: the 8 stages, their exit gates, the hard rules, and which skill comes next |
| `skills/wp-kickoff` · `wp-design` · `wp-build` · `wp-content` · `wp-seo` · `wp-quality` · `wp-ship` · `wp-operate` | One skill per stage, each with checklists in `references/` |
| `skills/wp-task-file` | Hand long work to a builder agent safely: scope, hard rules, test-first steps, stop conditions |
| `agents/wp-rules-reviewer` | Read-only diff review: layering, escaping, nonces, prefixes, prices, keys, tests |
| `agents/launch-auditor` | Read-only launch checklist against staging or live (GET requests only) |
| `bin/wp-site-new` | Generates the starter project below |
| `tests/` | `selftest.sh` (no Docker) and `playground-smoke.sh` (a real WordPress, no Docker) |

### The starter project
```
AGENTS.md (+ CLAUDE.md, GEMINI.md)   one rule file for every agent
SITE_STATUS.md                       stage gates, ticked with evidence
wordpress/<slug>/                    DDEV (PHP 8.3, MariaDB 10.11, docroot web/)
  mu-plugins/<slug>-core/            ALL logic: environment, edge-security, content, media,
                                     enquiry, seo, schema, performance
  themes/<slug>-child/               Astra child: layout only, swappable skins
scripts/                             deploy-safe, apply-content-change, backup, probe,
                                     check-emoji, workstation-doctor, make-state-bundle, photos/
tests/                               Playwright: structure, seo, accessibility, security, forms, visual
docs/                                brief, design direction, recovery, credentials (names only),
                                     hardening, redirects, task template, team bridge
static/                              design prototype sharing token names with the skin
```

What the starter does out of the box:
- **Leads are saved before they are forwarded.** Honeypot, rate limit, consent, retry and purge cron, GDPR export and erase. No third-party key in the page.
- **Local and staging are never indexed** and never email customers.
- **Security headers**, no user enumeration, REST user list closed, XML-RPC pingbacks off, no file editor.
- **One schema graph.** It joins the SEO plugin's graph, and never prints an Offer without a price.
- **`/llms.txt` for AI assistants**, plus a meta description and canonical until an SEO plugin takes over.
- **Self-hosted fonts only**, emoji script off, speculative prefetch.
- **Safe deploys**: only merged commits, with a drift check, a typed confirmation, a backup, a one-command swap, a smoke test and automatic rollback.
- **Guarded content changes**: a field is written only if its current value matches "before".

## Install (Claude Code)
```
/plugin marketplace add umairmsm/wp-site-kit        # or a local path to a clone
/plugin install wp-site-kit@wp-site-kit
```
Then ask: *"Use wp-site-kit: start a new WordPress site for …"*. The `wp-site` skill finds the stage and leads from there.

Other agents: point them at `skills/<name>/SKILL.md`. Generated projects carry their rules in `AGENTS.md`.

## Quick start without an agent
```bash
bin/wp-site-new --name "Acme Tours" --slug acme-tours --prefix acme --domain example.com --dir ~/sites/acme-tours --git
cd ~/sites/acme-tours && cat README.md      # "First run": ddev start, WordPress install, Astra, tests
```

## The eight stages
1. **Kickoff**: brief, stack, scaffold, credentials list.
2. **Design**: static prototype, tokens, owner sign-off.
3. **Build**: core plugin, theme, content model, enquiries.
4. **Content**: approved copy, real photos, legal pages.
5. **SEO and languages**: schema, redirects, language pilots.
6. **Quality**: every suite green, accessibility, speed.
7. **Ship**: staging, hardening, safe deploys, cutover, first week.
8. **Operate**: backups, restore drills, updates, more than one machine.

## Hard rules (short version)
- Plugin-first.
- No hard-coded prices or cards.
- Leads are saved first.
- No fake proof.
- Real photos for anything named.
- Facts come from the owner.
- No AI-written PHP to production without review.
- No page builders and no Google Fonts.
- Staging is never indexed.
- Tests are never weakened.
- Deploys belong to the owner.
- The database changes only through guarded scripts.
- Secrets are named, never stored.
- One schema owner.

The full list, with the reason for each, is in `skills/wp-site/SKILL.md`.

## Unattended build queue and phone status
Builder agents run task files (`docs/supervision/TASK_*.md`) through `agy-loop`, step by step,
with a gate and a reviewer. Two small tools keep that going for hours without anyone watching and
show where it stands on a phone:

```bash
bin/loop-queue  [PROJECT]          # run .supervision/QUEUE task by task (start it in tmux)
bin/loop-status [PROJECT]          # phone-sized picture: ✅ approved · 🔨 building · ⬜ waiting · 🛑 stopped · ⏳ retrying
bin/loop-queue raw [PROJECT]       # plain-text details
```

- **QUEUE** (`.supervision/QUEUE`): task files in order. Finished tasks ("all N steps APPROVED on
  &lt;branch&gt;" in `loop.log`) are skipped; the file is re-read before every task, so tasks can be appended
  while it runs.
- **status.conf** (`.supervision/status.conf`, made from `status.conf.example`): title, task-ID pattern,
  one plain-English line per task, optional team board and ntfy topic file. Plain text, never executed.
- **Waits instead of stopping** when the cause is not the code: reviewers unavailable (retry every
  15 min, 4 times) or a usage limit (retry every 30 min, up to 12 h). Anything else stops the queue with a
  high-priority push, because a stop needs a person.
- **One runner per project** (`queue.lock`); status checks the locks without taking them.
- New projects get `.supervision/QUEUE`, `status.conf` and a phone runbook, `PHONE.md` (one command per
  line, for SSH apps on a phone). `.supervision/` stays out of git: `agy-loop` stops on a dirty tree, so a
  tracked QUEUE edited during a build would stop it.

## Check the kit
```bash
tests/selftest.sh            # scaffold + php -l + shellcheck + JSON/XML + scrub + the starter's own rules + loop dashboard
tests/loop-dashboard.sh      # loop-queue / loop-status against a fixture queue and a fake agy-loop
tests/playground-smoke.sh    # serve the starter in WordPress Playground; check pages, headers, schema, enquiries
```

## Status and limits (0.1)
Verified on every change: the self-test, PHPCS (WordPress Coding Standards 3 + PHPCompatibilityWP)
with 0 errors and 0 warnings, and gitleaks. Verified before release: the Playwright suites against a real
WordPress in WordPress Playground (32 passed, 0 failed) and the Playground smoke test.

Not yet proven by this kit's own tests:
- `deploy-safe.sh`, `apply-content-change.sh` and `check-emoji.sh` need a real server over SSH.
  They port scripts that run in production on the site the method came from; run them against
  staging first and read their dry-run output.
- Visual tests need the pinned Playwright Docker image; the Mailpit check needs DDEV.
- No shop (WooCommerce) or booking module yet. Multilingual is documented, not scaffolded.

Reports from the first real sites built with it are welcome (see `CONTRIBUTING.md`).

## Works well with
- **[agent-skills-kit](https://github.com/umairmsm/agent-skills-kit)**: general WordPress API skills (`wordpress-modern`, `wp-plugin-development`, `wp-wpcli-and-ops`, `wp-performance`).
- **[agent-loop-kit](https://github.com/umairmsm/agent-loop-kit)**: unattended builder loops with a script gate and tiered reviewers (`supervised-build`, `agent-supervisor`).

## Contributing and security
See [CONTRIBUTING.md](CONTRIBUTING.md). Report vulnerabilities privately: [SECURITY.md](SECURITY.md).

## License
MIT
