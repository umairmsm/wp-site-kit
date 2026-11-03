# Working from more than one machine

Typical setup: the owner's workstation (decisions, merges, pushes, deploys, secrets) plus a
cloud build box (long unattended builds, work from a phone over ssh). The box should be
replaceable at any time.

## Where the truth is
| What | Truth | How it moves |
|---|---|---|
| Code, docs, tests, task files | git (private remote) | push (owner) → pull |
| Unpushed work on a box without push rights | that box only | `git bundle create work.bundle --all` → copy to the owner's machine → `git fetch work.bundle 'refs/heads/*:refs/box/*'` |
| Git-ignored config (`deploy/targets.conf`, `config.local.yaml`, agent-loop config) | each machine | `scripts/make-state-bundle.sh` (encrypted) |
| Secrets | password manager | by hand; the build box gets only what it needs (never production keys) |
| Content (DB, uploads) | live server; local copies | `scripts/backup.sh`; sanitised copies only on boxes |
| Agent memory / team memory | `~/.claude/projects/...` per machine; shared notes in `docs/team/` | the backup zip; commit shared notes |

## Rules that kept two machines from colliding
1. **One builder per task.** Lock it in `docs/team/BRIDGE.md` for human-paced work, or with a local lock file for loops.
2. **Handoff only through git:** commit, the owner pushes, the other side does `git fetch && git switch -C <branch> origin/<branch>`. Never copy a working tree.
3. **Base branches must match.** Cut task branches from the same commit on both machines. A different base once made the gate count a whole batch of committed task files as "changes to protected files".
4. **An untracked file can block a loop.** Before starting one, `git status` must be clean (move scratch files out of the repo).
5. **Don't run a second gate** on a machine whose gate is already running (they share the worktree names).
6. **Reset agent-loop state** after switching machines, or it resends stale feedback.
7. **Start long jobs detached:** `setsid nohup <cmd> >> <log> 2>&1 < /dev/null & disown`. Some agent CLIs don't see their login inside tmux.

## Workstation doctor
`scripts/workstation-doctor.sh` checks:
- tools;
- repo state (behind the remote? stashes? local-only branches?);
- git-ignored files (add the project's own list with `DOCTOR_LOCAL_FILES="…"`);
- Docker and DDEV;
- the WordPress environment type;
- agent guard hooks.
Run it on arrival and before leaving a machine.

## Exit drill (before a box, a credit or a plan ends)
1. On the box: commit or bundle everything; list stashes and local-only branches (the doctor shows them).
2. Owner's machine: fetch the bundle, push what should be pushed.
3. A fresh machine: clone, unpack the state bundle, restore a sanitised DB, run the doctor and `npm run test:structure`.
4. Write the minutes it took in `DISASTER_RECOVERY.md` §5.

## Agents on a build box
- Guard hooks and deny rules **before** any unattended loop (a box without them runs agents with full permissions).
- No production keys, no `_private/`, no customer data on the box.
- Logins: one per Linux user for some agent CLIs. Switching accounts there switches it for every repo on that user.
