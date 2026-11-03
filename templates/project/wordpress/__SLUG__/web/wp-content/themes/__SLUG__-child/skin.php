<?php
/**
 * Skins: the look is swappable without touching layout or logic.
 *
 * A skin is a folder in skins/<name>/ with tokens.css, skin.css and optional parts/
 * overrides. Choose it with the {{PREFIX}}_skin option
 * (`ddev wp option update {{PREFIX}}_skin <name>`) or the {{PREFIX_UC}}_SKIN constant.
 * Admins preview any skin with ?skin=<name>. Skins named dev-* never appear on production.
 *
 * @package {{PREFIX_UC}}_Child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Skin folder names available in this environment.
 *
 * @return string[]
 */
function {{PREFIX}}_available_skins() {
	$skins = array();
	$dirs  = glob( get_stylesheet_directory() . '/skins/*', GLOB_ONLYDIR );
	foreach ( $dirs ? $dirs : array() as $dir ) { // glob() can return false.
		$name = basename( $dir );
		if ( 0 === strpos( $name, 'dev-' ) && 'production' === wp_get_environment_type() ) {
			continue;
		}
		$skins[] = $name;
	}
	return $skins ? $skins : array( 'default' );
}

/**
 * Whether an admin is previewing a valid skin with ?skin=<name>.
 *
 * @return string Skin name, or '' when not previewing.
 */
function {{PREFIX}}_preview_skin() {
	if ( empty( $_GET['skin'] ) || ! current_user_can( 'manage_options' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only preview.
		return '';
	}
	$name = sanitize_key( wp_unslash( $_GET['skin'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only preview.
	return in_array( $name, {{PREFIX}}_available_skins(), true ) ? $name : '';
}

/**
 * The active skin: admin preview, then the saved option, then the constant, then "default".
 *
 * @return string
 */
function {{PREFIX}}_skin() {
	static $active = null;
	if ( null !== $active ) {
		return $active;
	}
	$available = {{PREFIX}}_available_skins();
	$preview   = {{PREFIX}}_preview_skin();
	$saved     = (string) get_option( '{{PREFIX}}_skin', '' );
	$constant  = defined( '{{PREFIX_UC}}_SKIN' ) ? (string) constant( '{{PREFIX_UC}}_SKIN' ) : '';
	foreach ( array( $preview, $saved, $constant, 'default' ) as $candidate ) {
		if ( '' !== $candidate && in_array( $candidate, $available, true ) ) {
			$active = $candidate;
			return $active;
		}
	}
	$active = $available[0];
	return $active;
}

/**
 * A skin preview must never be cached and served to visitors.
 *
 * @param array $headers Response headers.
 * @return array
 */
function {{PREFIX}}_skin_preview_headers( $headers ) {
	if ( '' !== {{PREFIX}}_preview_skin() ) {
		$headers['Cache-Control'] = 'private, no-store';
	}
	return $headers;
}
add_filter( 'wp_headers', '{{PREFIX}}_skin_preview_headers' );

/**
 * Load a template part, letting the active skin override it.
 *
 * Looks for skins/<skin>/parts/<name>.php, then parts/<name>.php.
 *
 * @param string $name Part name, e.g. 'offer-card'.
 * @param array  $args Data for the part (available as $args inside it).
 * @return void
 */
function {{PREFIX}}_part( $name, $args = array() ) {
	$skinned = 'skins/' . {{PREFIX}}_skin() . '/parts/' . $name;
	if ( locate_template( array( $skinned . '.php' ) ) ) {
		get_template_part( $skinned, null, $args );
		return;
	}
	get_template_part( 'parts/' . $name, null, $args );
}
