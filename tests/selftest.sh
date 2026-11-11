#!/usr/bin/env bash
# Self-test for wp-site-kit: scaffold a sample site into a temp folder and check everything that
# can be checked without a running WordPress. Never starts Docker or DDEV.
#
#   tests/selftest.sh            # exit 1 on any failure
set -euo pipefail

KIT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
SITE="$TMP/acme-tours"
fails=0
pass() { printf 'ok    %s\n' "$1"; }
fail() { printf 'FAIL  %s\n' "$1"; fails=$((fails + 1)); }
check() { local name="$1"; shift; if "$@" >"$TMP/out" 2>&1; then pass "$name"; else fail "$name"; sed 's/^/      /' "$TMP/out" | head -20; fi; }

echo "== kit"
check "plugin.json is JSON" jq empty "$KIT/.claude-plugin/plugin.json"
check "marketplace.json is JSON" jq empty "$KIT/.claude-plugin/marketplace.json"
for skill in "$KIT"/skills/*/; do
  name="$(basename "$skill")"
  file="$skill/SKILL.md"
  fm_name="$(sed -n '2,5s/^name: //p' "$file" | head -n 1)"
  desc="$(sed -n '2,6s/^description: //p' "$file" | head -n 1)"
  if [ "$fm_name" = "$name" ] && [ -n "$desc" ] && [ "${#desc}" -le 1024 ]; then pass "skill $name frontmatter"; else fail "skill $name frontmatter (name='$fm_name', description ${#desc} chars)"; fi
  while IFS= read -r ref; do
    [ -f "$skill/$ref" ] || fail "skill $name links missing $ref"
  done < <(grep -o 'references/[a-z0-9-]*\.md' "$file" | sort -u)
done
for agent in "$KIT"/agents/*.md; do
  if sed -n '1,6p' "$agent" | grep -q '^name: ' && sed -n '1,6p' "$agent" | grep -q '^description: '; then pass "agent $(basename "$agent")"; else fail "agent $(basename "$agent") frontmatter"; fi
done
check "shellcheck kit scripts" shellcheck "$KIT/bin/wp-site-new" "$KIT/bin/loop-queue" "$KIT/tests/selftest.sh" "$KIT/tests/playground-smoke.sh" "$KIT/tests/loop-dashboard.sh"
# shellcheck disable=SC2016  # Python code, not shell
check "loop-status parses (python)" python3 -I -c 'import ast, sys; ast.parse(open(sys.argv[1]).read())' "$KIT/bin/loop-status"

echo "== scrub (nothing private in what would be published)"
# Scan the files git tracks (git-ignored owner notes are never published), or the whole folder outside git.
PUB="$TMP/publish"
mkdir -p "$PUB"
if git -C "$KIT" rev-parse --is-inside-work-tree >/dev/null 2>&1; then
  (cd "$KIT" && git ls-files -z | xargs -0 cp --parents -t "$PUB")
else
  cp -R "$KIT/." "$PUB/"
fi
# Real email addresses: only example domains and noreply addresses may appear.
if grep -rnoE '[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}' "$PUB" --exclude-dir=.git \
     | grep -vE '@(example\.(com|test|org)|users\.noreply\.github\.com|\{\{DOMAIN\}\})' >"$TMP/emails"; then
  fail "email addresses found:"; sed 's/^/      /' "$TMP/emails" | head -10
else
  pass "no real email addresses"
fi
# Private words live outside the kit (a scrub scanner next to it, as in an OSS staging folder).
SCANNER="${WPK_SCRUB_SCANNER:-$KIT/../scan.sh}"
if [ -f "$SCANNER" ]; then
  bash "$SCANNER" "$PUB" >"$TMP/scan" 2>&1 || true
  # The public owner handle (plugin.json author) is allowed in the marketplace owner field only (exact lines).
  owner="$(jq -r .author.name "$KIT/.claude-plugin/plugin.json")"
  : >"$TMP/scan-hits"
  while read -r loc rest; do
    file="${loc%:*}" line="${loc##*:}"
    if [ "$file" = .claude-plugin/marketplace.json ] && [[ "$rest" = "(rule: "*")" ]] &&
       sed -n "${line}p" "$PUB/$file" | grep -qxE "\\s*\"(name\": \"$owner|url\": \"https://github\\.com/$owner)\",?"; then
      continue
    fi
    echo "$loc $rest" >>"$TMP/scan-hits"
  done < <(grep -E '^\s+\S+:[0-9]+ ' "$TMP/scan" || true)
  if [ -s "$TMP/scan-hits" ]; then
    fail "scrub scanner found private words:"; head -10 "$TMP/scan-hits"
  elif grep -qE '^\s+\S+:[0-9]+ ' "$TMP/scan" || ! grep -q 'FAIL' "$TMP/scan"; then
    pass "scrub scanner ($SCANNER; owner handle allowed in the marketplace owner field)"
  else
    fail "scrub scanner failed:"; tail -5 "$TMP/scan"
  fi
else
  echo "skip  no scrub scanner at $SCANNER (set WPK_SCRUB_SCANNER before publishing)"
fi

echo "== scaffold"
check "wp-site-new runs" "$KIT/bin/wp-site-new" --name "Acme Tours & Co" --slug acme-tours --prefix acme --domain example.com --dir "$SITE" --git
if grep -rIn -e '{{[A-Z_]*}}' -e '__SLUG__' "$SITE" >/dev/null 2>&1; then fail "placeholders left"; else pass "no placeholders left"; fi
check "CLAUDE.md links to AGENTS.md" test "$(readlink "$SITE/CLAUDE.md")" = AGENTS.md
check "core plugin present" test -f "$SITE/wordpress/acme-tours/web/wp-content/mu-plugins/acme-tours-core/acme-tours-core.php"
check "theme present" test -f "$SITE/wordpress/acme-tours/web/wp-content/themes/acme-tours-child/style.css"
check "build queue files present" test -f "$SITE/.supervision/QUEUE" -a -f "$SITE/.supervision/status.conf" -a -f "$SITE/PHONE.md"
check "loop-status runs on the new project" "$KIT/bin/loop-status" "$SITE"
check "git: uploads/.htaccess tracked" git -C "$SITE" ls-files --error-unmatch wordpress/acme-tours/web/wp-content/uploads/.htaccess
check "refuses a non-empty folder" bash -c "! '$KIT/bin/wp-site-new' --name X --slug abc --prefix abcd --domain example.com --dir '$SITE'"
check "refuses a clashing prefix" bash -c "! '$KIT/bin/wp-site-new' --name X --slug abc --prefix astra --domain example.com --dir '$TMP/other'"
# WPCS PrefixAllGlobals rejects prefixes shorter than 4 characters (ShortPrefixPassed is an error).
check "refuses a 3-character prefix" bash -c "! '$KIT/bin/wp-site-new' --name X --slug abc --prefix abc --domain example.com --dir '$TMP/short'"
check "accepts a 4-character prefix" bash -c "'$KIT/bin/wp-site-new' --name X --slug abcd --prefix abcd --domain example.com --dir '$TMP/four' >/dev/null"

echo "== generated project"
php_fail=0
while IFS= read -r -d '' f; do php -l "$f" >/dev/null 2>&1 || { fail "php -l ${f#"$SITE"/}"; php_fail=1; }; done < <(find "$SITE" -name '*.php' -print0)
[ "$php_fail" = 0 ] && pass "php -l on every PHP file"
check "shellcheck project scripts" bash -c "cd '$SITE' && shellcheck scripts/*.sh"
check "python compiles" python3 -I -m py_compile "$SITE/scripts/photos/photos.py"
for j in package.json composer.json; do check "$j is JSON" jq empty "$SITE/$j"; done
# shellcheck disable=SC2016  # PHP code, not shell
check "phpcs.xml.dist is XML" php -r 'exit(simplexml_load_file($argv[1]) ? 0 : 1);' "$SITE/phpcs.xml.dist"

echo "== the starter obeys its own rules"
THEME="$SITE/wordpress/acme-tours/web/wp-content/themes/acme-tours-child"
# shellcheck disable=SC2016  # regex, not shell
if grep -rlE 'new WP_Query|get_posts\s*\(|\$wpdb|wp_mail\s*\(|register_post_type|register_post_meta|add_meta_box|\bpll_[a-z_]+\s*\(|wp_remote_(get|post)' "$THEME" --include='*.php' >"$TMP/logic"; then
  fail "business logic in theme: $(tr '\n' ' ' < "$TMP/logic")"; else pass "theme holds no business logic"; fi
if grep -rlE '(€|£|\$)\s?[0-9]|\b[0-9][0-9.,]*\s?(EUR|USD|GBP)\b' "$THEME" --include='*.php' --include='*.html' >"$TMP/prices"; then
  fail "prices in theme: $(tr '\n' ' ' < "$TMP/prices")"; else pass "no prices in theme"; fi
if grep -rlE 'fonts\.(googleapis|gstatic)\.com' "$THEME" "$SITE/static" --include='*.php' --include='*.css' --include='*.js' --include='*.html' >"$TMP/fonts"; then
  fail "Google Fonts: $(tr '\n' ' ' < "$TMP/fonts")"; else pass "no Google Fonts"; fi
modules_required="$(grep -oE "modules/[a-z0-9-]+\.php" "$SITE/wordpress/acme-tours/web/wp-content/mu-plugins/acme-tours-core/acme-tours-core.php" | sort | tr '\n' ' ')"
modules_on_disk="$(cd "$SITE/wordpress/acme-tours/web/wp-content/mu-plugins/acme-tours-core" && find modules -maxdepth 1 -name '*.php' | sort | tr '\n' ' ')"
if [ "$modules_required" = "$modules_on_disk" ]; then pass "every module loaded"; else fail "modules: required [$modules_required] vs disk [$modules_on_disk]"; fi

# Cron jobs scheduled during `wp core install` hit tables that don't exist yet (DB errors in the install output).
ENQ="$SITE/wordpress/acme-tours/web/wp-content/mu-plugins/acme-tours-core/modules/enquiry.php"
if sed -n '/^function acme_schedule_enquiry_jobs/,/^}/p' "$ENQ" | grep -q 'wp_installing()'; then pass "no cron scheduling during install"; else fail "enquiry jobs are scheduled during wp core install (guard with wp_installing())"; fi

echo "== loop dashboard (loop-queue / loop-status)"
if bash "$KIT/tests/loop-dashboard.sh" >"$TMP/loop" 2>&1; then pass "loop dashboard tests ($(grep -c '^ok' "$TMP/loop") checks)"; else fail "loop dashboard tests:"; grep -v '^ok' "$TMP/loop" | sed 's/^/      /' | head -20; fi

echo "== manifest"
if git -C "$KIT" rev-parse --git-dir >/dev/null 2>&1; then
  # The starter's own .gitignore applies inside the kit too; a template file it hides would never ship.
  if git -C "$KIT" ls-files --others --ignored --exclude-standard -- templates | grep . >"$TMP/ignored"; then
    fail "template files hidden by a .gitignore (add them with git add -f):"; sed 's/^/      /' "$TMP/ignored" | head -10
  else pass "no template file is git-ignored"; fi
  if diff <(git -C "$KIT" ls-files | sort) <(grep -vE '^(#|$)' "$KIT/MANIFEST.txt" | sort) >"$TMP/manifest"; then
    pass "MANIFEST.txt lists exactly the tracked files"
  else fail "MANIFEST.txt differs from git ls-files (< tracked only, > manifest only):"; sed 's/^/      /' "$TMP/manifest" | head -20; fi
fi

echo
if [ "$fails" = 0 ]; then echo "SELFTEST: all passed"; else echo "SELFTEST: $fails failed"; fi
exit $((fails > 0))
