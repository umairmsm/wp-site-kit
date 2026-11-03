# Bridge — machines and people working on {{SITE_NAME}}

Every machine reads this first and updates it last. Newest log line on top.
Tracked in git: commit it, the owner pushes, the other machine pulls.

## Roles
| | Machine A (owner) | Machine B (build box) |
|---|---|---|
| Is | | |
| Does | decisions, merges, `git push`, deploys, secrets | long builds, unattended loops |
| Claude login | | |
| Other agent login | | |
| Never has | | production keys, `_private/`, customer data, push rights |

## Rules
1. **One builder per task.** Before a loop starts, write `LOCK: <machine> <task> <time>` in the log. Clear it when the loop stops. (For loops that start and stop often, use a local lock file instead; frequent lock commits cause merge noise.)
2. **Handoff** = commit, the owner pushes, the other machine runs `git fetch && git switch -C <branch> origin/<branch>`. Never copy a working tree by hand.
3. **Local-only files** are not synced by git (`deploy/targets.conf`, `.ddev/config.local.yaml`, agent-loop state). Keep them in step with `scripts/make-state-bundle.sh`, and check with `scripts/workstation-doctor.sh`.
4. After switching machines, reset any agent-loop state. Stale state resends old feedback.
5. A machine without push rights saves its work with `git bundle create work.bundle --all`, copied to the owner's machine, before it stops or is deleted.
6. Don't touch the repo on a machine while its test gate runs.

## Parity checklist
- [ ] `scripts/workstation-doctor.sh` says ready on both machines
- [ ] Same PHP major and minor version as the host, or tests proven independent of it
- [ ] Visual baselines only from the pinned Docker image
- [ ] Guard hooks / permissions on every machine that runs agents unattended

## Open decisions (owner)

## Task state

## Log (newest first)
- {{DATE}}: bridge created. LOCK: none.
