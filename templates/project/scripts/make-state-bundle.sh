#!/usr/bin/env bash
# Pack the git-ignored local state a new machine needs into ONE encrypted file.
#
#   scripts/make-state-bundle.sh [--owner] <out-dir>      (gpg asks for a passphrase)
#
# Packs: deploy/targets.conf, .ddev/config.local.yaml, agent-loop config (.supervision/*.conf and
# rubrics, if present), and a MANIFEST of the environment variable NAMES the machine needs.
# Never packs: values of secrets, ~/.claude settings or hooks (they hold absolute paths and
# per-account settings), logs, dumps. --owner also packs _private/ (owner's machines only).
set -euo pipefail
OWNER=0
if [ "${1:-}" = "--owner" ]; then OWNER=1; shift; fi
OUT="${1:?usage: make-state-bundle.sh [--owner] <out-dir>}"
REPO="$(git rev-parse --show-toplevel)"
STAMP="$(date +%F)"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
cd "$REPO"

FILES=(deploy/targets.conf wordpress/{{SLUG}}/.ddev/config.local.yaml
       .supervision/loop.conf .supervision/gate.conf .supervision/rubrics)
[ "$OWNER" = 1 ] && FILES+=(_private)
packed=0
for f in "${FILES[@]}"; do
  if [ -e "$f" ]; then tar -rf "$TMP/state.tar" "$f"; packed=$((packed + 1)); fi
done
{
  echo "# state bundle $STAMP from $(hostname)"
  echo "# environment variable NAMES this machine uses (values are NOT in this bundle):"
  env | cut -d= -f1 | grep -E 'KEY|TOKEN|SECRET|CF_ACCESS|NTFY|SMTP|API' | sort || true
} > "$TMP/MANIFEST.txt"
tar -rf "$TMP/state.tar" -C "$TMP" MANIFEST.txt
mkdir -p "$OUT"
gpg --symmetric --cipher-algo AES256 -o "$OUT/state-$STAMP.tar.gpg" "$TMP/state.tar"
echo "wrote $OUT/state-$STAMP.tar.gpg ($packed items + MANIFEST). Unpack: gpg -d <file> | tar -x"
