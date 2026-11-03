#!/usr/bin/env bash
# Read-only check for "????" corruption: 4-byte characters (emoji, some symbols) stored in a
# database or table that isn't utf8mb4. Run after every DB import, migration or host move.
#
#   scripts/check-emoji.sh --target local|staging|live
#
# Learned the hard way: a server import turned itinerary emoji into "????" on 15 pages while
# the local copy was fine. Fix: utf8mb4 tables + DB_CHARSET utf8mb4, then re-apply the text
# from the good copy through scripts/apply-content-change.sh. Exit 1 if anything looks wrong.
set -euo pipefail

TARGET=""
while [ $# -gt 0 ]; do
  case "$1" in
    --target) TARGET="${2:-}"; shift 2 ;;
    *) echo "usage: $0 --target local|staging|live" >&2; exit 2 ;;
  esac
done
ROOT="$(git rev-parse --show-toplevel)"

case "$TARGET" in
  local) ;;
  staging|live)
    [ -f "$ROOT/deploy/targets.conf" ] || { echo "deploy/targets.conf missing" >&2; exit 2; }
    # shellcheck source=/dev/null
    . "$ROOT/deploy/targets.conf"
    if [ "$TARGET" = live ]; then HOST="$LIVE_SSH_HOST" DOCROOT="$LIVE_DOCROOT"; else HOST="$STAGING_SSH_HOST" DOCROOT="$STAGING_DOCROOT"; fi ;;
  *) echo "usage: $0 --target local|staging|live" >&2; exit 2 ;;
esac

# Run WP-CLI on the target. Remote arguments are quoted with printf %q.
run_wp() {
  if [ "$TARGET" = local ]; then
    (cd "$ROOT/wordpress/{{SLUG}}" && ddev wp "$@")
  else
    # shellcheck disable=SC2029  # expanding DOCROOT and the quoted args here, on this machine, is intended
    ssh "$HOST" "cd $DOCROOT && wp $(printf '%q ' "$@")"
  fi
}

problems=0
P=$(run_wp db prefix | tr -d '[:space:]')
charset=$(run_wp config get DB_CHARSET 2>/dev/null | tr -d '[:space:]' || true)
echo "DB_CHARSET: ${charset:-unknown}"
[ "$charset" = utf8mb4 ] || { echo "  -> should be utf8mb4"; problems=1; }

echo "Tables not in utf8mb4:"
tables=$(run_wp db query "SELECT TABLE_NAME, TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_COLLATION NOT LIKE 'utf8mb4%'" --skip-column-names)
if [ -n "$tables" ]; then echo "$tables"; problems=1; else echo "  none"; fi

echo "Posts containing '????':"
posts=$(run_wp db query "SELECT ID, post_type, LEFT(post_title, 60) FROM ${P}posts WHERE post_status <> 'trash' AND (post_content LIKE '%????%' OR post_title LIKE '%????%' OR post_excerpt LIKE '%????%')" --skip-column-names)
if [ -n "$posts" ]; then echo "$posts"; problems=1; else echo "  none"; fi

echo "Post meta containing '????':"
meta=$(run_wp db query "SELECT post_id, meta_key FROM ${P}postmeta WHERE meta_value LIKE '%????%' LIMIT 100" --skip-column-names)
if [ -n "$meta" ]; then echo "$meta"; problems=1; else echo "  none"; fi

if [ $problems = 0 ]; then
  echo "OK: no corruption found on $TARGET."
else
  echo "CHECK: see above. A real '????' in text is possible; compare with the local copy."
fi
exit $problems
