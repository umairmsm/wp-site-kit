#!/usr/bin/env bash
# Read-only check that this machine can work on the project: tools, repo state, git-ignored
# local files, and runtime health. Run it on a new machine, and after switching machines.
#
#   scripts/workstation-doctor.sh [repo-path]
#
# Prints OK / MISSING / WARN. Exit 1 if anything is MISSING. Changes nothing.
# Extra tools for this project (for example an agent-loop kit): DOCTOR_EXTRA_TOOLS="agy-loop loop-status".
REPO="${1:-$(git rev-parse --show-toplevel 2>/dev/null || pwd)}"
WP="$REPO/wordpress/{{SLUG}}"
miss=0
ok()   { printf 'OK      %s\n' "$1"; }
bad()  { printf 'MISSING %s\n' "$1"; miss=1; }
warn() { printf 'WARN    %s\n' "$1"; }
have() { command -v "$1" >/dev/null 2>&1; }

echo "== tools"
for c in git node npm npx php composer ddev docker python3 jq rsync zip gpg shellcheck gitleaks ${DOCTOR_EXTRA_TOOLS:-}; do
  if have "$c"; then ok "$c"; else bad "$c"; fi
done

echo "== repo $REPO"
if [ -d "$REPO/.git" ]; then
  git -C "$REPO" fetch origin --quiet 2>/dev/null || warn "git fetch failed (offline, or no remote yet)"
  main=$(git -C "$REPO" symbolic-ref --short refs/remotes/origin/HEAD 2>/dev/null || echo origin/main)
  ok "branch $(git -C "$REPO" branch --show-current) at $(git -C "$REPO" rev-parse --short HEAD); $(git -C "$REPO" rev-list --count "HEAD..$main" 2>/dev/null || echo '?') commits behind $main"
  if [ -z "$(git -C "$REPO" status --porcelain --untracked-files=no)" ]; then ok "tracked files clean"; else warn "uncommitted changes to tracked files"; fi
  stashes=$(git -C "$REPO" stash list | wc -l)
  [ "$stashes" = 0 ] || warn "$stashes stash(es): they exist only on this machine"
  for br in $(git -C "$REPO" for-each-ref --format='%(refname:short)' refs/heads); do
    git -C "$REPO" rev-parse -q --verify "origin/$br" >/dev/null || warn "branch '$br' exists only on this machine"
  done
else
  bad "no git repo at $REPO"
fi

echo "== git-ignored local files (copy them, or rebuild them from the .example files)"
if [ -e "$REPO/deploy/targets.conf" ]; then ok "deploy/targets.conf"; else warn "deploy/targets.conf (needed only for deploys)"; fi
for f in ${DOCTOR_LOCAL_FILES:-}; do
  if [ -e "$REPO/$f" ]; then ok "$f"; else bad "$f"; fi
done

echo "== runtime"
if docker info >/dev/null 2>&1; then ok "docker daemon"; else bad "docker daemon not reachable"; fi
if have ddev && [ -d "$WP" ]; then
  # Read the status first: `ddev wp` on a stopped project would START it (this script only looks).
  status=$(cd "$WP" && ddev describe -j 2>/dev/null | jq -r '.raw.status // empty' 2>/dev/null)
  if [ "$status" = running ]; then
    ok "ddev project running"
    env_t=$(cd "$WP" && ddev wp eval 'echo wp_get_environment_type();' 2>/dev/null | tail -n 1)
    if [ "$env_t" = local ]; then ok "WordPress environment = local"; else warn "WordPress environment = '${env_t:-unknown}' (must be local; see .ddev/config.local.yaml.example)"; fi
  else
    warn "ddev project ${status:-not created} (start it with 'ddev start' to check the WordPress environment)"
  fi
fi
# shellcheck disable=SC1091  # the OS release file exists only at run time
os_name=$( (. /etc/os-release 2>/dev/null && echo "$PRETTY_NAME") || uname -s)
echo "INFO    php $(php -r 'echo PHP_VERSION;' 2>/dev/null) on $os_name: visual baselines come only from the pinned Docker image"

echo "== agents"
if have claude; then ok "claude CLI"; else warn "claude CLI not installed"; fi
if [ -d "$HOME/.claude/hooks" ]; then ok "$HOME/.claude/hooks present"; else warn "no $HOME/.claude/hooks (no guard hook on this machine)"; fi
for f in AGENTS.md SITE_STATUS.md SESSION_LOG.md docs/team/BRIDGE.md; do
  if [ -f "$REPO/$f" ]; then ok "$f"; else warn "$f missing"; fi
done

echo
if [ "$miss" = 0 ]; then echo "RESULT: ready"; else echo "RESULT: gaps above (MISSING)"; fi
exit $miss
