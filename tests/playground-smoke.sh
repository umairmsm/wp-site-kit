#!/usr/bin/env bash
# Live smoke test of the starter on a real WordPress, without Docker or DDEV:
# WordPress Playground (PHP compiled to WebAssembly) serves a scaffolded sample site.
#
#   tests/playground-smoke.sh [--port 4150] [--keep]
#
# Needs node/npx and network on the first run (Playground CLI, WordPress, Astra).
# --keep leaves the temp folder (logs, sample site) for a look afterwards.
set -euo pipefail

KIT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PORT=4150 KEEP=0 PG_CLI="@wp-playground/cli@3.1.62"
while [ $# -gt 0 ]; do
  case "$1" in
    --port) PORT="${2:?--port needs a number}"; shift 2 ;;
    --keep) KEEP=1; shift ;;
    *) echo "usage: $0 [--port N] [--keep]" >&2; exit 2 ;;
  esac
done
B="http://127.0.0.1:$PORT"
if command -v ss >/dev/null 2>&1 && ss -ltn | grep -q ":$PORT "; then
  echo "port $PORT is busy; pick another with --port" >&2; exit 2
fi

TMP="$(mktemp -d)"
SITE="$TMP/acme-tours"
"$KIT/bin/wp-site-new" --name "Acme Tours" --slug acme-tours --prefix acme --domain example.com --dir "$SITE" >/dev/null
WPC="$SITE/wordpress/acme-tours/web/wp-content"

# Blueprint: local environment, the core loader, a read-only probe, Astra, our theme, sample content.
cat > "$TMP/blueprint.json" <<'JSON'
{
  "preferredVersions": { "php": "8.3", "wp": "latest" },
  "steps": [
    { "step": "defineWpConfigConsts", "consts": { "WP_ENVIRONMENT_TYPE": "local" } },
    { "step": "writeFile", "path": "/wordpress/wp-content/mu-plugins/acme-tours-core.php", "data": "<?php\nrequire_once __DIR__ . '/acme-tours-core/acme-tours-core.php';\n" },
    { "step": "writeFile", "path": "/wordpress/wp-content/mu-plugins/zz-smoke.php", "data": "<?php\nadd_action( 'init', function () {\n\tif ( isset( $_GET['wpk_smoke'] ) ) {\n\t\theader( 'Content-Type: text/plain' );\n\t\t$ids = get_posts( array( 'post_type' => 'acme_enquiry', 'post_status' => 'any', 'fields' => 'ids', 'posts_per_page' => -1 ) );\n\t\techo 'enquiries=' . count( $ids ) . ' env=' . wp_get_environment_type() . ' theme=' . get_stylesheet();\n\t\texit;\n\t}\n}, 99 );\n" },
    { "step": "installTheme", "themeData": { "resource": "wordpress.org/themes", "slug": "astra" }, "options": { "activate": false } },
    { "step": "activateTheme", "themeFolderName": "acme-tours-child" },
    { "step": "setSiteOptions", "options": { "blogname": "Acme Tours", "blogdescription": "Small-group walks and day trips, planned by people who live here.", "permalink_structure": "/%postname%/" } },
    { "step": "runPHP", "code": "<?php require '/wordpress/wp-load.php'; wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Contact', 'post_name' => 'contact', 'post_content' => 'Write to us and we will answer within a day.', 'post_excerpt' => 'Ask about walks, day trips and private groups. We answer within one working day.' ) ); $o = wp_insert_post( array( 'post_type' => 'acme_offer', 'post_status' => 'publish', 'post_title' => 'Harbour walk', 'post_name' => 'harbour-walk', 'post_content' => 'Two hours around the old harbour.', 'post_excerpt' => 'A two-hour walk around the old harbour with a local guide.' ) ); update_post_meta( $o, 'acme_price_from', 60 ); flush_rewrite_rules();" }
  ]
}
JSON

echo "Starting WordPress Playground on $B (first run downloads WordPress and Astra) …"
setsid npx -y "$PG_CLI" server --port="$PORT" --php=8.3 --blueprint="$TMP/blueprint.json" \
  --mount="$WPC/mu-plugins/acme-tours-core:/wordpress/wp-content/mu-plugins/acme-tours-core" \
  --mount="$WPC/themes/acme-tours-child:/wordpress/wp-content/themes/acme-tours-child" \
  >"$TMP/server.log" 2>&1 &
PG=$!
# shellcheck disable=SC2317,SC2329  # called by the EXIT trap (SC2317 on older shellcheck)
cleanup() {
  kill -TERM -- "-$PG" 2>/dev/null || kill "$PG" 2>/dev/null || true
  # Belt and braces: stop any Playground process still serving THIS blueprint (never anything else).
  for pid in $(pgrep -f "$TMP/blueprint.json" 2>/dev/null || true); do kill "$pid" 2>/dev/null || true; done
  if [ "$KEEP" = 1 ]; then echo "kept: $TMP"; else rm -rf "$TMP"; fi
}
trap cleanup EXIT

for _ in $(seq 1 120); do
  if grep -q 'Ready!' "$TMP/server.log" 2>/dev/null; then break; fi
  if ! kill -0 "$PG" 2>/dev/null; then echo "Playground stopped:"; tail -20 "$TMP/server.log"; exit 1; fi
  sleep 2
done
grep -q 'Ready!' "$TMP/server.log" || { echo "Playground did not start in 4 minutes"; tail -20 "$TMP/server.log"; exit 1; }

fails=0
pass() { printf 'ok    %s\n' "$1"; }
fail() { printf 'FAIL  %s\n' "$1"; fails=$((fails + 1)); }
probe() { curl -s --max-time 60 "$@"; }

state=""
for _ in 1 2 3 4 5 6; do  # the first requests after "Ready!" can come back empty while workers warm up
  state="$(probe "$B/?wpk_smoke=1" || true)"
  case "$state" in *theme=*) break ;; esac
  sleep 3
done
case "$state" in *"env=local theme=acme-tours-child"*) pass "core loaded, theme active, environment local" ;; *) fail "site state: $state" ;; esac

for path in / /contact/ /offers/ /offers/harbour-walk/ /llms.txt /robots.txt /wp-sitemap.xml; do
  code="$(probe -o "$TMP/body" -w '%{http_code}' "$B$path")"
  if [ "$code" = 200 ] && ! grep -qE 'Fatal error|Parse error|Warning: |Notice: |Deprecated: |critical error' "$TMP/body"; then
    pass "$path 200, no PHP errors"
  else
    fail "$path answered $code or printed PHP errors"
  fi
done

headers="$(probe -D - -o /dev/null "$B/")"
for h in 'x-robots-tag: noindex' 'x-content-type-options: nosniff' 'x-frame-options: SAMEORIGIN' 'referrer-policy: strict-origin-when-cross-origin'; do
  if grep -qi "^$h" <<<"$headers"; then pass "header $h"; else fail "header $h missing"; fi
done
author="$(probe -o /dev/null -w '%{http_code} %{redirect_url}' "$B/?author=1")"
case "$author" in 301*author*) fail "?author=1 reveals a login: $author" ;; 301*|404*) pass "?author=1 hidden" ;; *) fail "?author=1: $author" ;; esac
if [ "$(probe -o /dev/null -w '%{http_code}' "$B/wp-json/wp/v2/users")" = 401 ]; then pass "REST users 401"; else fail "REST users not 401"; fi

probe "$B/offers/harbour-walk/" | sed -n 's|.*<script type="application/ld+json">\([^<]*\)</script>.*|\1|p' > "$TMP/ld.json"
if python3 -I -m json.tool "$TMP/ld.json" >/dev/null 2>&1 && grep -q '"Service"' "$TMP/ld.json" && grep -q '"Offer"' "$TMP/ld.json"; then
  pass "JSON-LD parses, Service with Offer"
else
  fail "JSON-LD on the offer page"
fi

nonce="$(probe "$B/contact/" | grep -oE 'name="acme_enquiry_nonce" value="[^"]+"' | sed -E 's/.*value="([^"]+)"/\1/')"
post() { probe -o /dev/null -w '%{http_code} %{redirect_url}' -X POST "$B/wp-admin/admin-post.php" "$@"; }
sent="$(post --data-urlencode action=acme_enquiry --data-urlencode "acme_enquiry_nonce=$nonce" --data-urlencode website= \
  --data-urlencode "name=Smoke Tester" --data-urlencode email=tester@example.com --data-urlencode "message=Smoke test" \
  --data-urlencode consent=1 --data-urlencode context=contact)"
case "$sent" in 302*enquiry=sent*) pass "valid enquiry → thank-you" ;; *) fail "valid enquiry: $sent" ;; esac
post --data-urlencode action=acme_enquiry --data-urlencode website=http://spam.example.com --data-urlencode name=Bot \
  --data-urlencode email=bot@example.com --data-urlencode consent=1 >/dev/null
if [ "$(post --data-urlencode action=acme_enquiry --data-urlencode name=NoConsent --data-urlencode email=nc@example.com | cut -c1-3)" = 400 ]; then
  pass "missing consent → 400"
else
  fail "missing consent not rejected"
fi
case "$(probe "$B/?wpk_smoke=1")" in *enquiries=1*) pass "exactly one enquiry stored (bot and invalid ones dropped)" ;; *) fail "stored enquiries: $(probe "$B/?wpk_smoke=1")" ;; esac

echo
if [ "$fails" = 0 ]; then echo "PLAYGROUND SMOKE: all passed"; else echo "PLAYGROUND SMOKE: $fails failed"; fi
exit $((fails > 0))
