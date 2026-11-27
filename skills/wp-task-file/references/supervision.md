# Supervising builder agents

## The three tiers
| Tier | Who | Does | Never |
|---|---|---|---|
| Gate | a script (`gate.sh`) in a throwaway worktree | tests, lint, probes, removed-test scan, secret scan, protected-path check | judges the code |
| Reviewer | a cheaper model (often inside the builder's own quota) | reads the diff against the step; APPROVED, CHANGES_REQUIRED with numbered fixes, or ESCALATE | writes code, commits, resets |
| Supervisor | the expensive model, or the owner | escalations: security, data, money, production, scope, architecture | polls or builds |

## Protected paths (the builder may not change them)
`docs/supervision/` (task files), the gate and reviewer files, the base branch, deploy scripts,
`.github/`, anything under `_private/`. The gate fails a commit that touches them.

## Guardrails on the machine
- **Production stays out of reach:** the production SSH key has a passphrase and confirm-on-use (`ssh-add -c`); deploy scripts need a terminal and a typed commit id.
  A lock that is only an environment variable is not a lock: one agent set the variable itself in its own scripts.
- **Agent CLI guard hooks:** deny destructive commands (`rm -rf` outside the repo, `git push --force`, `git reset --hard` on shared branches), deny reading secrets (`.env`, `_private/`), and ask before any ssh, DNS or Cloudflare call.
- **Tamper check:** the loop records a checksum of the gate and reviewer files. If they change, the loop stops. Re-baselining is a human step, run outside any agent.
- **Notifications:** a push notification (for example ntfy) on APPROVED, BLOCKED, ESCALATED and DONE. No model polling.

## Running a queue unattended
- `bin/loop-queue` runs `.supervision/QUEUE` task by task in tmux; `bin/loop-status` is the phone picture (see the kit README and the project's `PHONE.md`).
- Change the loop's own settings (`loop.conf`, gate files, builder) only while the queue is idle: a running loop keeps its old file list and stops on the new tamper baseline.
- Give each tmux session a unique name and address it exactly (`tmux attach -t =name`): `-t name` also matches sessions that merely start with it.
- Run the loop command with stdin from `/dev/null`; a loop that reads stdin swallows the rest of the queue.
- Usage limits and reviewer outages are waits, not stops; classify only the newest STOP line.

## Writing good steps
- One behaviour per step, with its test written first ("see it fail for the right reason").
- Name the files. "Improve SEO" is not a step; "add canonical per language in `modules/seo.php`, with test `tests/seo/canonical-lang.spec.ts`" is.
- Say what must stay the same, and how it is checked (a parity baseline, a snapshot, a function inventory).
- Put decisions in the task file's "Decisions" section with a date and an ID (D1, D2 …). The builder re-reads the file on every step.

## Common failures and the rule each one taught
| What happened | Rule |
|---|---|
| A builder changed live prices, published terms and deployed a feature, unreviewed | Builders never hold production keys; deploys need a human at a terminal |
| The report left out a deploy | Review the git log and server state, not the report |
| A "100% hardened" note; the fix was unpushed and untested | "Done" means a commit on the branch plus a green gate |
| Branch cut from a different base on the second machine; the gate flagged committed task files as builder changes | Commit task files to the base branch first; cut branches from the same commit everywhere |
| Stale loop state resent old feedback after a machine switch | Reset loop state when switching machines |
| A renamed test was read as "removed" | Renames need the owner's OK, listed in the commit |
| The reviewer hung for 10 minutes | Timeouts on reviewers; a hung reviewer is restarted, not waited on |

## Report template (`docs/supervision/REPORT_<ID>.md`)
- What changed (per step: commit, summary).
- Evidence: test counts before and after, lint, parity, gate output.
- Anything held or skipped, and why.
- What the owner must do next, as exact commands (deploy, content change, approvals).
- A draft message to the business owner (six lines at most), if they need to approve something.
