#!/usr/bin/env bash
# Dated full-recovery backup of the LOCAL site: everything needed to rebuild it on a new machine.
#
#   scripts/backup.sh [dest-dir] [--encrypt]
#
# One zip with:
#   1. the database (ddev export-db): the DB is the source of truth for content;
#   2. the working tree, including web/wp-content/uploads (minus .git, node_modules, vendor);
#   3. repo.bundle: every git branch and stash, including ones never pushed;
#   4. a snapshot of the project's Claude memory, if there is one (override with CLAUDE_MEM=).
# Never inside: secrets (.env, deploy/targets.conf, _private/), other dumps, old backups.
# The zip holds customer data (enquiries). --encrypt wraps it with gpg (asks for a passphrase).
# Keep one copy on another machine or drive. Restore: docs/reference/DISASTER_RECOVERY.md.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
NAME="$(basename "$ROOT")"
WP_DIR="$ROOT/wordpress/{{SLUG}}"
DEST="" ENCRYPT=0
for arg in "$@"; do
  case "$arg" in
    --encrypt) ENCRYPT=1 ;;
    *) DEST="$arg" ;;
  esac
done
DEST="${DEST:-$(dirname "$ROOT")/Backups}"
STAMP="$(date +%Y-%m-%d_%H%M)"
mkdir -p "$DEST"
OUT="$DEST/${NAME}-${STAMP}.zip"
DR="$ROOT/_dr"
rm -rf "$DR"
mkdir -p "$DR"
trap 'rm -rf "$DR"' EXIT

echo "1. Database"
if command -v ddev >/dev/null 2>&1 && (cd "$WP_DIR" && ddev export-db --gzip --file="$DR/db.sql.gz"); then
  echo "   ok"
else
  echo "   WARN: no database dump (is the DDEV site running?). Files only."
fi

echo "2. Git: all branches and stashes"
if git -C "$ROOT" bundle create "$DR/repo.bundle" --all 2>/dev/null; then echo "   ok"; else echo "   WARN: git bundle failed (no commits yet?)"; fi
git -C "$ROOT" status --short > "$DR/git-status.txt" 2>/dev/null || true

echo "3. Claude memory"
CLAUDE_MEM="${CLAUDE_MEM:-$HOME/.claude/projects/$(printf '%s' "$ROOT" | sed 's|/|-|g')/memory}"
if [ -d "$CLAUDE_MEM" ]; then
  cp -a "$CLAUDE_MEM" "$DR/claude-memory" && echo "   ok ($CLAUDE_MEM)"
else
  echo "   none at $CLAUDE_MEM (set CLAUDE_MEM=… if the sessions run from another folder)"
fi

echo "4. Zip"
cd "$(dirname "$ROOT")"
zip -r -q "$OUT" "$NAME" \
  -x "$NAME/.git/*" -x "$NAME/node_modules/*" -x "$NAME/vendor/*" \
  -x "$NAME/test-results/*" -x "$NAME/playwright-report/*" \
  -x "$NAME/_private/*" -x "$NAME/backups/*" -x "$NAME/deploy/targets.conf" \
  -x "$NAME/.env" -x "$NAME/.env.*" -x "$NAME/**/*.sql" -x "*.zip"

if [ "$ENCRYPT" = 1 ]; then
  gpg --symmetric --cipher-algo AES256 -o "$OUT.gpg" "$OUT" && rm -f "$OUT" && OUT="$OUT.gpg"
fi
echo "Backup written: $OUT ($(du -h "$OUT" | cut -f1))"
echo "Now copy it off this machine. It holds customer data: keep it private."
