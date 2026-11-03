---
name: wp-task-file
description: Write a task file that a builder agent (Antigravity, Codex, Gemini, another Claude) can carry out unattended on a wp-site-kit WordPress project - scope, hard rules, one commit per step with tests, definition of done with evidence, stop conditions - and run it under review with agent-loop-kit when installed. Use for "write a task for Antigravity", "hand this to the builder", "make a task file", "run this unattended", "supervise the build".
---

# Task files: let a builder agent do the long work safely

Split of work that saved the most quota and caused the fewest incidents:
- **The expensive model** writes the task file, decides escalations, and does the final check before staging.
- **A builder agent** builds, one step per commit.
- **A script gate** runs tests, lint, probes and secret scans on each commit.
- **A cheaper model** reviews first, and the expensive model only gets what the cheaper one escalates.
- **Scripts and notifications** watch progress instead of a model polling.

## Write the task
1. Copy the project's `docs/supervision/TASK_TEMPLATE.md` to `docs/supervision/TASK_<ID>_<date>.md`.
2. **Why and scope.** Two sentences of why. Then the out-of-scope list, written out: anything the builder might
   "helpfully" touch (prices, URLs, deploy scripts, other modules, the database).
3. **Hard rules.** Keep the template's. Add the task's own: which files may change, what must stay
   byte-identical (a parity baseline), which tests must not move.
4. **Steps** (usually 3–7). Each step says:
   - exactly what to do;
   - which tests to write first and what each proves;
   - "done when" with the commands and the expected numbers.
   A step should take an agent under an hour. Split bigger ones.
5. **Definition of done** with evidence (counts, command output). **Stop conditions** that end the run instead of letting the builder improvise.
6. Commit the task file to `main` (or the base branch) **before** the builder's branch is cut, on every machine. Then the gate doesn't see the task file as a change the builder made to protected files.

## Run it
- With **agent-loop-kit**: `supervised-build` (builder side) and `agent-supervisor` (review side) give the status and verdict protocol, the gate and tiered reviewers.
  - Start the loop detached with `setsid nohup … & disown`.
  - The tree must be clean (no untracked scratch files).
- Without it: give the builder the task file. After each commit, review it yourself with the **wp-rules-reviewer** agent plus the test output, and reply APPROVED or CHANGES_REQUIRED with a numbered fix list.

## Review each step against reality
- Read the diff, not the builder's summary. Run the tests yourself or read the gate's output.
- Check claims: "fixed", "deployed" and "resolved" mean nothing until you see the commit on the branch, the green test, or the live response.
  Builders have reported work as done that was unpushed, untested or never deployed, and once left a production deploy out of a report.
- Escalate to the owner for anything about money, prices, production, customer data, security, legal text or scope.

## Gate for this skill
- The task file names: branch, scope, out-of-scope, hard rules, steps with tests, definition of done, stop conditions.
- The builder can't reach production: no production keys on its machine, and deploys need a human at a terminal.

More: `references/supervision.md`.
