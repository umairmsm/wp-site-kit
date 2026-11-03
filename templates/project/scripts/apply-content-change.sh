#!/usr/bin/env bash
# Apply a recorded content change (docs/data/content-changes/*.json) to a database.
#
#   scripts/apply-content-change.sh <change.json> --target local|staging|live            # dry run: report only
#   scripts/apply-content-change.sh <change.json> --target local|staging|live --apply    # write (live: type "live")
#
# Change file: { "<post_type>/<slug>": { "<field>": { "before": "…", "after": "…" } } }
# Fields: post_content, post_title, post_excerpt, or any post meta key.
# Guard per field: written only if the current value equals "before" exactly. Equal to "after"
# means already applied. Anything else is SKIP: someone changed it there, so it is never overwritten.
# Before any write: `wp db export` to ~/backups/ (servers) or a DDEV snapshot (local).
set -euo pipefail
die() { echo "STOP: $*" >&2; exit 1; }

FILE="${1:-}"
[ $# -gt 0 ] && shift
TARGET="" APPLY=0
while [ $# -gt 0 ]; do
  case "$1" in
    --target) TARGET="${2:-}"; shift ;;
    --apply) APPLY=1 ;;
    *) die "unknown option $1" ;;
  esac
  shift
done
[ -f "$FILE" ] || die "usage: $0 <change.json> --target local|staging|live [--apply]"
ROOT="$(git rev-parse --show-toplevel)"

# shellcheck disable=SC2016  # PHP code in single quotes
php -r '
  $d = json_decode( (string) file_get_contents( $argv[1] ), true );
  if ( ! is_array( $d ) || ! $d ) { fwrite( STDERR, "not a JSON object\n" ); exit( 1 ); }
  $n = 0;
  foreach ( $d as $key => $fields ) {
    if ( false === strpos( (string) $key, "/" ) || ! is_array( $fields ) ) { fwrite( STDERR, "bad item key: $key (use post_type/slug)\n" ); exit( 1 ); }
    foreach ( $fields as $field => $v ) {
      if ( ! is_array( $v ) || ! array_key_exists( "before", $v ) || ! array_key_exists( "after", $v ) || $v["before"] === $v["after"] ) { fwrite( STDERR, "bad field: $key $field\n" ); exit( 1 ); }
      $n++;
    }
  }
  echo "  " . count( $d ) . " items, $n fields\n";
' "$FILE" || die "$FILE is not a valid change file"

APPLY_PHP=$(cat <<'PHP'
<?php
$apply  = isset( $args[0] ) && '1' === $args[0];
$change = json_decode( (string) file_get_contents( $args[1] ), true );
$n      = array( 'write' => 0, 'done' => 0, 'skip' => 0 );
$post_fields = array( 'post_content', 'post_title', 'post_excerpt' );
if ( $apply ) {
	// WP-CLI runs as no user, so WordPress would strip iframes, embeds and SVG from post content.
	// The text comes from a reviewed change file, so write it as written.
	kses_remove_filters();
}
foreach ( $change as $key => $fields ) {
	list( $type, $slug ) = explode( '/', $key, 2 );
	$p = get_page_by_path( $slug, OBJECT, array( $type ) ); // An array, so a media file with the same slug never matches.
	if ( ! $p ) { echo "SKIP  $key: not on this site\n"; $n['skip']++; continue; }
	foreach ( $fields as $field => $v ) {
		$is_post = in_array( $field, $post_fields, true );
		$now     = $is_post ? (string) get_post_field( $field, $p->ID, 'raw' ) : (string) get_post_meta( $p->ID, $field, true );
		if ( $now === $v['after'] ) { echo "DONE  $key $field: already applied\n"; $n['done']++; continue; }
		if ( $now !== $v['before'] ) { echo "SKIP  $key $field: value differs from 'before'; not touched\n"; $n['skip']++; continue; }
		if ( ! $apply ) { echo "WOULD $key $field\n"; $n['write']++; continue; }
		if ( $is_post ) {
			wp_update_post( array( 'ID' => $p->ID, $field => wp_slash( $v['after'] ) ) );
			clean_post_cache( $p->ID );
			$check = (string) get_post_field( $field, $p->ID, 'raw' );
		} else {
			update_post_meta( $p->ID, $field, wp_slash( $v['after'] ) );
			$check = (string) get_post_meta( $p->ID, $field, true );
		}
		echo ( $check === $v['after'] ? 'WROTE ' : 'CHECK ' ) . "$key $field\n";
		$n['write']++;
	}
}
if ( $apply && function_exists( '{{PREFIX}}_bump_cache_version' ) ) {
	{{PREFIX}}_bump_cache_version();
}
echo 'summary: ' . ( $apply ? 'written' : 'would write' ) . "={$n['write']} already={$n['done']} skipped={$n['skip']}\n";
PHP
)

if [ "$TARGET" = local ]; then
  W="$ROOT/wordpress/{{SLUG}}"
  T="$W/.content-change-tmp"
  mkdir -p "$T"
  trap 'rm -rf "$T"' EXIT
  printf '%s\n' "$APPLY_PHP" > "$T/apply.php"
  cp "$FILE" "$T/change.json"
  cd "$W"
  if [ "$APPLY" = 1 ]; then ddev snapshot --name "pre-content-change-$(date +%Y%m%d-%H%M%S)"; fi
  ddev wp eval-file .content-change-tmp/apply.php "$APPLY" .content-change-tmp/change.json
  ddev wp cache flush --quiet || true
  exit 0
fi

case "$TARGET" in staging|live) ;; *) die "--target must be local, staging or live" ;; esac
[ -f "$ROOT/deploy/targets.conf" ] || die "deploy/targets.conf missing"
# shellcheck source=/dev/null
. "$ROOT/deploy/targets.conf"
if [ "$TARGET" = live ]; then HOST="$LIVE_SSH_HOST" DOCROOT="$LIVE_DOCROOT"; else HOST="$STAGING_SSH_HOST" DOCROOT="$STAGING_DOCROOT"; fi
[ -t 0 ] || die "run this yourself in a terminal"
if [ "$APPLY" = 1 ] && [ "$TARGET" = live ]; then
  read -r -p "This writes to the LIVE site (only where the guard matches). Type 'live': " ok < /dev/tty
  [ "$ok" = live ] || die "not confirmed"
fi

LOG="$(mktemp)"
trap 'rm -f "$LOG"' EXIT
echo "== Server ($TARGET): confirm the key if asked"
{
  # DOCROOT stays unquoted on purpose: the server's shell expands a leading ~ in the assignment.
  printf 'APPLY=%q\nDOCROOT=%s\n' "$APPLY" "$DOCROOT"
  printf 'CHANGE_JSON=%q\n' "$(cat "$FILE")"
  printf 'APPLY_PHP=%q\n' "$APPLY_PHP"
  cat <<'REMOTE'
set -euo pipefail
main() {
  cd "$DOCROOT"
  T=$(mktemp -d ~/content-change.XXXX); trap 'rm -rf "$T"' EXIT
  printf '%s' "$CHANGE_JSON" > "$T/change.json"
  printf '%s\n' "$APPLY_PHP" > "$T/apply.php"
  if [ "$APPLY" = 1 ]; then
    mkdir -p ~/backups && wp db export ~/backups/pre-content-change-"$(date +%Y%m%d-%H%M%S)".sql --quiet && echo "database backed up to ~/backups/"
  fi
  wp eval-file "$T/apply.php" "$APPLY" "$T/change.json"
  wp cache flush --quiet 2>/dev/null || true
  echo "REMOTE-DONE"
}
main < /dev/null
REMOTE
} | ssh "$HOST" 'bash -s' 2>&1 | tee "$LOG" || true
grep -q '^REMOTE-DONE$' "$LOG" || die "the server part did not finish (see above)"
