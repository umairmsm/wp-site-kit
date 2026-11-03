#!/usr/bin/env bash
# Safe code deploy for {{SITE_NAME}}: the child theme, the core must-use plugin and the
# uploads guard, always together (the theme calls core functions; one without the other fatals).
#
#   scripts/deploy-safe.sh <commit> --target staging|live [--dry-run] [--assume-live <commit>]
#
# Settings come from deploy/targets.conf (git-ignored; copy deploy/targets.conf.example).
# Staging behind Cloudflare Access needs CF_ACCESS_CLIENT_ID and CF_ACCESS_CLIENT_SECRET.
#
# What it guarantees:
#   - Deploys ONLY a commit already on origin/<main branch> (reviewed and merged), built with
#     `git archive`, never from the working folder. PHP is linted first.
#   - Stops if the server's code was changed outside this script (drift check).
#   - Shows what will change, then a human types the commit id. Agents can't: it needs a terminal.
#   - Backs up the server's code and database first.
#   - Swaps in under a second, smoke-tests, and rolls back by itself on failure.
#   - Uses ONE ssh connection, so a confirm-on-use production key asks once.
#
# Content (prices, pages, legal text) is never deployed by this script: that is
# scripts/apply-content-change.sh. First run on a server with code already on it:
# pass --assume-live <commit> (the commit the server's code matches).
# shellcheck disable=SC2088  # a quoted ~ is meant literally here: the server's shell expands it
set -euo pipefail

WPC_GIT=wordpress/{{SLUG}}/web/wp-content
# Deployed paths, relative to wp-content. A path may be missing in older commits.
PARTS=(themes/{{SLUG}}-child mu-plugins/{{SLUG}}-core.php mu-plugins/{{SLUG}}-core uploads/.htaccess)

die() { echo "STOP: $*" >&2; exit 1; }
say() { echo; echo "== $*"; }

TARGET="" DRY=0 ASSUME="" DEST=""
while [ $# -gt 0 ]; do
  case "$1" in
    --dry-run) DRY=1 ;;
    --assume-live) ASSUME="${2:-}"; shift ;;
    --target) DEST="${2:-}"; shift ;;
    -*) die "unknown option $1" ;;
    *) TARGET="$1" ;;
  esac
  shift
done
[ -n "$TARGET" ] && [ -n "$DEST" ] || die "usage: $0 <commit> --target staging|live [--dry-run] [--assume-live <commit>]"

cd "$(git rev-parse --show-toplevel)"
[ -f deploy/targets.conf ] || die "deploy/targets.conf missing (copy deploy/targets.conf.example and fill it)"
# shellcheck source=/dev/null
. deploy/targets.conf
MAIN_BRANCH="${MAIN_BRANCH:-main}"
read -r -a SMOKE <<< "${SMOKE_PATHS:-/}"
CURL_AUTH=()
case "$DEST" in
  live)    HOST="${LIVE_SSH_HOST:-}" DOCROOT="${LIVE_DOCROOT:-}" SITE="${LIVE_URL:-}" ;;
  staging) HOST="${STAGING_SSH_HOST:-}" DOCROOT="${STAGING_DOCROOT:-}" SITE="${STAGING_URL:-}"
           if [ -n "${CF_ACCESS_CLIENT_ID:-}" ] && [ -n "${CF_ACCESS_CLIENT_SECRET:-}" ]; then
             CURL_AUTH=(-H "CF-Access-Client-Id: $CF_ACCESS_CLIENT_ID" -H "CF-Access-Client-Secret: $CF_ACCESS_CLIENT_SECRET")
           fi ;;
  *) die "--target must be staging or live" ;;
esac
[ -n "$HOST" ] && [ -n "$DOCROOT" ] && [ -n "$SITE" ] || die "deploy/targets.conf has no complete $DEST block"
[ -t 0 ] || die "run this yourself in a terminal (a human must confirm)"

WPC_REMOTE="$DOCROOT/wp-content"
STATE='~/deploy-state'
MARKER="$STATE/{{SLUG}}-$DEST.sha"

WORK=$(mktemp -d)
trap 'rm -rf "$WORK"' EXIT
trap 'echo "STOP: unexpected failure at line $LINENO (the server only changes in step 7)" >&2' ERR

say "1. Check the commit is reviewed and merged"
git fetch -q origin
SHA=$(git rev-parse --verify "$TARGET^{commit}") || die "unknown commit $TARGET"
git merge-base --is-ancestor "$SHA" "origin/$MAIN_BRANCH" || die "$SHA is not on origin/$MAIN_BRANCH (not merged)"
echo "Target: $(git log -1 --format='%h %s' "$SHA") -> $DEST ($SITE)"

# Extract the deployed parts of commit $1 into $2 (laid out like wp-content).
export_parts() {
  local paths=() p
  for p in "${PARTS[@]}"; do git cat-file -e "$1:$WPC_GIT/$p" 2>/dev/null && paths+=("$WPC_GIT/$p"); done
  mkdir -p "$2"
  [ ${#paths[@]} -gt 0 ] || return 0
  git archive "$1" -- "${paths[@]}" | tar -x -C "$2" --strip-components=4
}
md5_list() { (cd "$1" && find . -type f -print0 | sort -z | xargs -0 -r md5sum); }

say "2. Build from git (not the working folder) and lint PHP"
export_parts "$SHA" "$WORK/new"
[ -f "$WORK/new/themes/{{SLUG}}-child/functions.php" ] || die "theme missing in $SHA"
[ -f "$WORK/new/mu-plugins/{{SLUG}}-core.php" ] || die "core plugin missing in $SHA"
lint_fail=0
while IFS= read -r -d '' f; do
  php -l "$f" >/dev/null 2>&1 || { echo "PHP error: ${f#"$WORK"/new/}"; lint_fail=1; }
done < <(find "$WORK/new" -name '*.php' -print0)
[ $lint_fail = 0 ] || die "PHP syntax errors"
md5_list "$WORK/new" > "$WORK/new.md5"
echo "$(wc -l < "$WORK/new.md5") files, PHP lint OK"

say "3. Connect to the server (confirm the key if asked)"
SOCK="${XDG_RUNTIME_DIR:-/tmp}/{{SLUG}}-deploy-$$.sock"
ssh -fNM -o ControlPersist=no -S "$SOCK" "$HOST"
trap 'ssh -S "$SOCK" -O exit "$HOST" 2>/dev/null; rm -rf "$WORK"' EXIT
# Run a command on the server. Empty output is not an error.
r() {
  local out rc=0
  out=$(ssh -S "$SOCK" "$HOST" "$@" 2>&1) || rc=$?
  [ -z "$out" ] || printf '%s\n' "$out"
  return $rc
}

say "4. Drift check: is $DEST exactly what we last deployed?"
LIVE_SHA=$(r "cat $MARKER 2>/dev/null" || true)
if [ -z "$LIVE_SHA" ]; then
  [ -n "$ASSUME" ] || die "no deploy record on the server; pass --assume-live <commit> (the commit the server should match)"
  LIVE_SHA=$(git rev-parse --verify "$ASSUME^{commit}")
fi
echo "$DEST should be: $(git log -1 --format='%h %s' "$LIVE_SHA")"
export_parts "$LIVE_SHA" "$WORK/live"
md5_list "$WORK/live" > "$WORK/expected.md5"
r "cd $WPC_REMOTE && find ${PARTS[*]} -type f -print0 2>/dev/null | sort -z | xargs -0 -r md5sum | sed 's|  |  ./|'" > "$WORK/remote.md5" || true
if ! diff -q "$WORK/expected.md5" "$WORK/remote.md5" >/dev/null; then
  echo "Server code differs from $LIVE_SHA (changed outside a deploy):"
  diff "$WORK/expected.md5" "$WORK/remote.md5" | grep '^[<>]' | awk '{print $1, $3}' | sort -k2 | sed -n '1,40p' || true
  die "drift found; investigate before deploying"
fi
echo "No drift."

say "5. What will change"
# Capture first: `git log | head` exits on SIGPIPE, which pipefail turns into a failure.
CHANGES=$(git log --format='  %h %s' "$LIVE_SHA..$SHA" -- "${PARTS[@]/#/$WPC_GIT/}")
N_CHANGES=$(printf '%s\n' "$CHANGES" | grep -c . || true)
printf '%s\n' "$CHANGES" | sed -n '1,30p'
[ "$N_CHANGES" -le 30 ] || echo "  … and $((N_CHANGES - 30)) more commits"
echo "Files:"
diff "$WORK/remote.md5" "$WORK/new.md5" | grep '^[<>]' | awk '{print $3}' | sort -u | sed 's/^/  /' || true
[ "$LIVE_SHA" != "$SHA" ] || die "$DEST already matches $SHA; nothing to deploy"

if [ $DRY = 1 ]; then say "Dry run: stopping here. Nothing was changed."; exit 0; fi

echo
SHORT=$(echo "$SHA" | cut -c1-7)
read -r -p "Type the first 7 characters of the commit ($SHORT) to deploy to $DEST: " ok < /dev/tty
[ "$ok" = "$SHORT" ] || die "not confirmed"

TS=$(date +%Y%m%d-%H%M%S)
BK="~/backups/deploy-$TS-$SHORT"
STAGE="~/deploy-staging/$TS"

say "6. Back up $DEST code and database"
r "mkdir -p $BK/code $STATE && cd $WPC_REMOTE && for p in ${PARTS[*]}; do [ ! -e \$p ] || cp -a --parents \$p $BK/code/; done && cd $DOCROOT && wp db export $BK/db.sql --quiet" >/dev/null
echo "Backup: $BK (it holds customer data: keep it outside the web root, delete old ones)"

say "7. Upload and switch"
r "mkdir -p $STAGE/new"
tar -C "$WORK/new" -c . | ssh -S "$SOCK" "$HOST" "tar -x -C $STAGE/new"
r "cd $STAGE/new && find . -type f -print0 | sort -z | xargs -0 -r md5sum" > "$WORK/staged.md5"
diff -q "$WORK/new.md5" "$WORK/staged.md5" >/dev/null || die "upload check failed; $DEST NOT changed (upload left at $STAGE)"
# Swap every part in ONE server command: old parts to $1, new parts from $2.
# shellcheck disable=SC2016  # $1, $2 and $p expand on the server
SWAP='set -e; cd '"$WPC_REMOTE"'; for p in '"${PARTS[*]}"'; do mkdir -p "$(dirname "$1/$p")" "$(dirname "$p")"; [ ! -e "$p" ] || mv "$p" "$1/$p"; done; for p in '"${PARTS[*]}"'; do [ ! -e "$2/$p" ] || mv "$2/$p" "$p"; done'
# POST_SWAP_CMD (optional, deploy/targets.conf): a server command that clears PHP's OPcache when the
# host doesn't re-check file times (validate_timestamps=0), e.g. a cachetool call or the host's own tool.
swap() { r "bash -c '$SWAP' swap $1 $2 && cd $DOCROOT && (wp cache flush --quiet 2>/dev/null || true)${POST_SWAP_CMD:+ && ($POST_SWAP_CMD)}"; }
swap "$STAGE/prev" "$STAGE/new"

# One page: 200 after redirects, within 30 s, no PHP error text, and a complete document for HTML.
# Three tries, 5 s apart, so one slow warm-up request doesn't trigger a rollback.
check_page() {
  local p="$1" try out code type body
  for try in 1 2 3; do
    out=$(curl -sL --max-time 30 "${CURL_AUTH[@]}" -o "$WORK/smoke.body" -w '%{http_code} %{content_type}' "$SITE$p?deploycheck=$TS") || out="000 "
    code=${out%% *}
    type=${out#* }
    body=$(cat "$WORK/smoke.body" 2>/dev/null || true)
    if [ "$code" = 200 ] && ! grep -qiE 'critical error|Fatal error|Parse error' <<< "$body" \
       && { [[ "$type" != text/html* ]] || grep -qi '</html>' <<< "$body"; }; then
      echo "  ok   $p"
      return 0
    fi
    [ "$try" = 3 ] || sleep 5
  done
  echo "  FAIL $p ($code)"
  return 1
}
smoke() {
  local bad=0 p
  for p in "${SMOKE[@]}"; do check_page "$p" || bad=1; done
  return $bad
}

say "8. Smoke test $DEST"
if smoke; then
  r "echo $SHA > $MARKER && echo \"$TS $SHA ok $DEST\" >> $STATE/deploy.log"
  say "DEPLOYED $SHORT to $DEST. Previous code kept at $STAGE/prev, backup at $BK"
else
  say "Smoke test failed: rolling back"
  swap "$STAGE/failed" "$STAGE/prev" && r "echo \"$TS $SHA ROLLED-BACK $DEST\" >> $STATE/deploy.log"
  if smoke; then die "rolled back to $LIVE_SHA; the site is OK again. The failed build is at $STAGE/failed"
  else die "rolled back, but the site still fails; restore from $BK and check the server now"; fi
fi
