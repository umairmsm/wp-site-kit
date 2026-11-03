---
name: wp-site
description: Start here for any WordPress website built with wp-site-kit, from an empty folder to a live site. Use when someone says "build a WordPress site", "new website for a client", "where are we on the site", "what is next before launch", or asks to continue a site project. Finds the current stage from SITE_STATUS.md, applies the hard rules, and hands over to the right stage skill.
---

# WordPress site: from empty folder to production

This kit is a method plus a starter project. It was learned on a live multilingual booking
site that real customers pay through. Follow the stages in order. Each stage ends with a
gate: leave a stage only when its gate is true and the evidence is written down.

## 1. Find where the project is
1. Look for `SITE_STATUS.md` in the project root.
   - Missing: the project has not started. Use **wp-kickoff**.
   - Present: read it. The current stage is the first stage with an unticked gate.
2. Read `AGENTS.md` (the project's rules), then the newest lines of `SESSION_LOG.md` and `DECISIONS.md`.
3. Tell the owner in two lines: the current stage, and the next concrete step.

## 2. The stages
| # | Stage | Skill | Gate to leave it (evidence goes in `SITE_STATUS.md`) |
|---|---|---|---|
| 1 | Kickoff | wp-kickoff | Brief approved by the owner; locked decisions written; private repo; credentials listed by name |
| 2 | Design | wp-design | Static prototype signed off (screenshots + date); tokens set; fonts self-hosted |
| 3 | Build | wp-build | Local site runs; core plugin + child theme active; real items render through core functions; enquiries save before they forward; structure tests green |
| 4 | Content | wp-content | Every public page has owner-approved copy and real photos; legal pages exist; no unapproved AI text |
| 5 | SEO & languages | wp-seo | Title, description, canonical, schema and hreflang tests green; old-URL redirect map; each extra language passed its pilot |
| 6 | Quality | wp-quality | Full suite green; 0 accessibility violations; performance budgets met on staging |
| 7 | Ship | wp-ship | Staging mirrors live; hardening applied; launch-auditor PASS; cutover done; 7 days watched |
| 8 | Operate | wp-operate | Automatic backups with an off-machine copy; one restore drill done; update and alert routines written |

- Handing a stage's work to a builder agent (Antigravity, Codex, another Claude): **wp-task-file**.
- Review before a merge: agent **wp-rules-reviewer**. Before go-live: agent **launch-auditor**.

## 3. Hard rules (every stage, every agent)
1. **Plugin-first.** Logic, data, policy text, forms, schema and SEO live in the core must-use plugin. The theme is layout. The skin is the look. A redesign must not lose a feature.
2. **No hard-coded cards, prices or slugs** in templates. Query the content type and render through the core's render function, so a CMS edit shows everywhere.
3. **Leads are saved first.** Forms post to `admin-post.php` with a nonce. The site stores the lead in its own database, then forwards it. No API keys or third-party endpoints in the page.
4. **No fake proof.** Never show ratings, review counts or testimonials nobody can prove. Imported or "legacy" review numbers count as fake until proven.
5. **Real photos** for any named place, room, product or person. AI images only as general scenery, named `ai-*`, logged, and never presented as "this is your room".
6. **Facts come from the owner.** Prices, policies, inclusions and safety facts are never invented. AI copy is a draft until the owner approves it. Unknown = `PENDING-<who>`.
7. **No AI-written PHP to production** without branch, local test, diff and a human merge. WordPress Coding Standards. Prefix everything.
8. **No page builders. No Google Fonts calls.** Self-host fonts: a German court ordered damages because loading them from Google leaked visitors' IP addresses.
9. **Staging and local are never indexed** and never email real customers.
10. **Tests are never weakened** to get green: no deleting, skipping or re-snapshotting. Renaming a test needs the owner's OK.
11. **Deploys belong to the owner.** From a commit, through `scripts/deploy-safe.sh`: dry run first, backup first, the word `live` typed by a human.
12. **The database is content.** Change it only through a reviewable change file and a guarded script with a dry run. Back up before every DB change.
13. **Secrets never enter** git, logs, prompts or the page. `CREDENTIALS_INVENTORY.md` lists names and where each secret lives, never values.
14. **One schema owner** (usually the SEO plugin). Never output an `Offer` without a price.

## 4. Ask the owner first
- Installing plugins, themes, tools or paid services (page builders: never).
- Any change to a price, currency, policy, brand name or logo. Publishing a new language.
- Anything that touches DNS, Cloudflare, hosting, staging or live. Destructive DB or file commands.
- Sending code or customer content to a third-party AI API.

## 5. How to work
- One stage at a time, one small step at a time. Commit after each meaningful step (Conventional Commits).
- Branch, local test, diff, then the owner merges. Agents never push, merge, deploy or `ssh` to a server unless the owner asks for that exact action.
- Every decision goes in `DECISIONS.md` (date, who, why). End each session with a `SESSION_LOG.md` line.
- "Done" means checked: open the page, run the test, read the server's answer. Never repeat another agent's "resolved" without checking it yourself.

## 6. Helpful skills from other kits (if installed)
- agent-skills-kit: `wordpress-modern`, `wp-plugin-development`, `wp-wpcli-and-ops`, `wp-performance` for WordPress API detail.
- agent-loop-kit: `supervised-build`, `agent-supervisor` for unattended builder loops with a gate and a reviewer.
