# Task: <ID> <one-line goal>

You are working in `<repo path>`. Work through every step below, in order. Read `AGENTS.md`,
`SITE_STATUS.md` and the newest `SESSION_LOG.md` lines first. They win if anything conflicts.

**Why:** <the problem in two sentences, and what "better" looks like for the owner>.

**Scope:** <what changes>. **Not in scope:** <what must not change; list it, don't imply it>.

## Hard rules
- Branch `<type>/<topic>` from the current tip of `main`. Never commit to `main`. Never push, merge or deploy.
- **Local only** (`https://{{SLUG}}.ddev.site`). Never request staging or live, never ssh, never touch DNS or Cloudflare.
- **No database writes** except through `scripts/apply-content-change.sh --target local` with a reviewable JSON file (if the task needs content).
- **No paid calls** (LLM APIs, SEO data, other services) unless a step names one.
- **Never read or search for credentials.** If something needs access you don't have, answer BLOCKED.
- **Never write** the reviewer's verdict files or the gate's files, and never start a reviewer.
- Test first: write the failing test, see it fail for the right reason, then implement. Revert-check each guard test and say so in the commit body.
- Never weaken, skip or delete a test, and never update a snapshot or baseline unless a step says so (list each file in the commit body).
- After each step these must be clean:
  - `npm test` (no fewer passing tests than `main`)
  - `npm run lint:php` 0 errors
  - `npm run lint:sh`
- One commit per step, Conventional Commits, step ID in the subject (for example `feat(core): <ID>-02 add policy module`).
- Plugin-first: logic and data in the core plugin; the theme lays out; the skin styles.

## Supervision protocol
<!-- With agent-loop-kit installed, keep its status/verdict protocol here (status file, polling, verdicts).
     Without it: after each commit, stop and print "READY_FOR_REVIEW <sha>: <summary, test counts>". -->
1. After committing a step: report `READY_FOR_REVIEW`, the commit, what changed, and the test counts.
2. Wait for the verdict:
   - `APPROVED` → next step.
   - `CHANGES_REQUIRED` → fix in a new commit, report again.
   - `ESCALATED` → wait.
3. A decision this spec doesn't cover → `BLOCKED`, with the options and your recommendation. Never guess.
4. All steps approved → write `docs/supervision/REPORT_<ID>.md` and report `DONE`.

## Steps

### Step 1: <ID>-01, <name>
- Do: <exact files and behaviour>
- Tests: <new tests, with what each proves>
- Done when: <checks with expected numbers>

### Step 2: <ID>-02, <name>
- Do:
- Tests:
- Done when:

## Definition of done (report each item with evidence)
- Every step committed and APPROVED.
- Test counts before and after; no test removed or skipped; snapshots unchanged unless listed.
- PHPCS 0 errors; shellcheck clean; gitleaks clean.
- No database writes outside the change file, no paid calls, no production requests, no secrets read, no pushes.
- What the owner must do next (deploy, content change, approvals), as exact commands.

## Stop conditions (stop, commit nothing broken, report BLOCKED)
1. The baseline is not green on `main` before you start.
2. A step needs a database write, a URL change, a production action or a paid service.
3. A step would need a test weakened or a snapshot re-taken that this spec doesn't list.

## Decisions (added by the supervisor after BLOCKED questions)
<!-- D1 (date, step): the question, the decision, and exactly what to do. -->
