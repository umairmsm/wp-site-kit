---
name: wp-operate
description: Stage 8 of a wp-site-kit WordPress build and everything after launch - backups with an off-machine copy, restore drills, the disaster-recovery runbook, credentials inventory, update policy, alerts, and working from more than one machine (bridge file, workstation doctor, encrypted state bundle, rescuing unpushed work). Use for "backup", "restore", "move to a new machine/server", "update plugins", "what if the server dies", "work from the cloud box", "set up a second machine".
---

# Stage 8: Operate

Goal: the site survives a lost laptop, a dead server, a bad update and a change of machine, and
nobody has to remember how.

## Steps
1. **Backups.**
   - Run `scripts/backup.sh` weekly, and before risky work. It packs the DB, uploads, every git branch and stash, and the Claude memory.
   - Use `--encrypt` before the zip leaves the machine, and keep one copy off it.
   - Live: the host's daily backups, plus the dumps the deploy and content scripts take.
2. **Write down where everything lives** in the project's `docs/reference/DISASTER_RECOVERY.md`, section 0. Include the photo originals folder: it is not in git.
3. **Restore drill.** Restore once per quarter onto a spare machine or a fresh DDEV project, time it, and log it in §5. A backup you have never restored is a hope, not a backup.
4. **Credentials:** every secret has a row in `CREDENTIALS_INVENTORY.md` (name, where it lives, who has it, when it was rotated). Rotate anything an agent has seen.
5. **Routines:** weekly, monthly and yearly checks, the update policy and alerts in `docs/reference/OPERATIONS.md`. Plugin updates go to staging first.
6. **More than one machine?** Follow `references/multi-machine.md`:
   - `docs/team/BRIDGE.md` holds roles, rules and the handoff log;
   - `scripts/workstation-doctor.sh` checks parity;
   - `scripts/make-state-bundle.sh` carries the git-ignored config, encrypted.

## Gate (tick in SITE_STATUS.md)
- [ ] Backups scheduled, with an off-machine copy
- [ ] Restore drill done (date, minutes)
- [ ] Update and alert routines written in `OPERATIONS.md`

## Lessons
- The backup script once pointed at the wrong Claude memory folder. The sessions ran from the parent folder, so the
  memory lived under a different path. The script derives the path but accepts `CLAUDE_MEM=` for this case.
- A cloud build box had a branch and stashes that existed nowhere else, and the box could not push. Pull them out with
  `git bundle` over ssh **before** the box stops or is deleted.
- Visual baselines made on one OS fail on another. Keep them in the pinned Docker image.
- A free plan or credit that ends on a date is a deadline for an exit drill: a week before, prove you can
  rebuild on the next machine from git + the state bundle + a backup.
