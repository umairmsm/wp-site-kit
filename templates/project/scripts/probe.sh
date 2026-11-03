#!/usr/bin/env bash
# Probe pages on any environment: status code, time, and PHP errors in the body.
#
#   scripts/probe.sh <base-url> [path ...]      e.g. scripts/probe.sh https://{{SLUG}}.ddev.site / /contact/
#
# Without paths it uses SMOKE_PATHS from deploy/targets.conf, else "/".
# Behind Cloudflare Access, export CF_ACCESS_CLIENT_ID and CF_ACCESS_CLIENT_SECRET first.
# Exit 1 if any page fails.
set -euo pipefail

BASE="${1:?usage: $0 <base-url> [path ...]}"
BASE="${BASE%/}"
shift
paths=("$@")
if [ ${#paths[@]} -eq 0 ]; then
  root="$(git rev-parse --show-toplevel 2>/dev/null || pwd)"
  if [ -f "$root/deploy/targets.conf" ]; then
    # shellcheck source=/dev/null
    . "$root/deploy/targets.conf"
  fi
  read -r -a paths <<< "${SMOKE_PATHS:-/}"
fi

auth=()
if [ -n "${CF_ACCESS_CLIENT_ID:-}" ] && [ -n "${CF_ACCESS_CLIENT_SECRET:-}" ]; then
  auth=(-H "CF-Access-Client-Id: $CF_ACCESS_CLIENT_ID" -H "CF-Access-Client-Secret: $CF_ACCESS_CLIENT_SECRET")
fi

bad=0
body_file="$(mktemp)"
trap 'rm -f "$body_file"' EXIT
for p in "${paths[@]}"; do
  meta=$(curl -sk "${auth[@]}" -o "$body_file" -w '%{http_code} %{time_total}' "$BASE$p") || meta="000 0"
  code=${meta%% *}
  secs=${meta##* }
  note=""
  if grep -qiE 'critical error|Fatal error|Parse error|Warning: |Notice: |Deprecated: ' "$body_file"; then
    note="PHP error text in page"
  fi
  status=ok
  case "$code" in
    2??|3??) ;;
    *) note="${note:-HTTP $code}" ;;
  esac
  if [ -n "$note" ]; then status=FAIL; bad=1; fi
  printf '%-4s %s %6ss  %s %s\n' "$status" "$code" "$secs" "$p" "$note"
done
exit $bad
