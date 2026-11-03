<?php
/**
 * {{SITE_NAME}} child theme: layout only.
 *
 * No business logic, queries, prices or policy text here; they live in {{SLUG}}-core.
 * This file loads the skin, fonts and theme chrome settings.
 *
 * @package {{PREFIX_UC}}_Child
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/skin.php';

/**
 * Load the theme's translations.
 *
 * @return void
 */
function {{PREFIX}}_child_load_textdomain() {
	load_child_theme_textdomain( '{{SLUG}}-child', get_stylesheet_directory() . '/languages' );
}
add_action( 'after_setup_theme', '{{PREFIX}}_child_load_textdomain' );

/**
 * Enqueue the active skin: tokens first, then components. Versioned by file time.
 *
 * @return void
 */
function {{PREFIX}}_enqueue_skin() {
	$skin = {{PREFIX}}_skin();
	$dir  = get_stylesheet_directory() . '/skins/' . $skin;
	$uri  = get_stylesheet_directory_uri() . '/skins/' . $skin;
	$deps = wp_style_is( 'astra-theme-css', 'registered' ) ? array( 'astra-theme-css' ) : array();
	foreach ( array( 'tokens', 'skin' ) as $file ) {
		$path = $dir . '/' . $file . '.css';
		if ( is_readable( $path ) ) {
			wp_enqueue_style( '{{PREFIX}}-' . $file, $uri . '/' . $file . '.css', $deps, (string) filemtime( $path ) );
			$deps = array( '{{PREFIX}}-' . $file );
		}
	}
}
add_action( 'wp_enqueue_scripts', '{{PREFIX}}_enqueue_skin', 20 );

/**
 * Preload the one or two fonts the first screen needs. Name them *-preload.woff2.
 *
 * @return void
 */
function {{PREFIX}}_preload_fonts() {
	$fonts = glob( get_stylesheet_directory() . '/assets/fonts/*-preload.woff2' );
	if ( ! $fonts ) {
		return; // No match, or glob() failed (false): print nothing rather than a broken preload.
	}
	foreach ( $fonts as $font ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( get_stylesheet_directory_uri() . '/assets/fonts/' . basename( $font ) )
		);
	}
}
add_action( 'wp_head', '{{PREFIX}}_preload_fonts', 1 );

// No Google Fonts: Astra must not load them (fonts are self-hosted in assets/fonts/).
add_filter( 'astra_google_fonts_selection', '__return_empty_array' );
add_filter( 'astra_load_google_fonts', '__return_false' );
