<?php
/**
 * Performance and privacy defaults that hold on any theme.
 *
 * Fonts and images are the theme's job (self-hosted fonts, preload, photo sizes).
 *
 * @package {{PREFIX_UC}}_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Remove WordPress's emoji script: it loads images from a third-party host (an IP leak under
 * GDPR) and every modern browser draws emoji itself.
 *
 * @return void
 */
function {{PREFIX}}_disable_emoji() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	add_filter( 'emoji_svg_url', '__return_false' );
}
add_action( 'init', '{{PREFIX}}_disable_emoji' );

/**
 * Speculative loading (WordPress 6.8+): prefetch likely next pages on hover.
 * Prefetch, not prerender: prerender runs page scripts early and can double-count analytics.
 *
 * @param array|null $config Core configuration.
 * @return array|null
 */
function {{PREFIX}}_speculation_config( $config ) {
	if ( ! is_array( $config ) ) {
		return $config;
	}
	$config['mode']      = 'prefetch';
	$config['eagerness'] = 'moderate';
	return $config;
}
add_filter( 'wp_speculation_rules_configuration', '{{PREFIX}}_speculation_config' );

/**
 * Drop assets a plugin loads on pages that don't use them. Measure first (page weight,
 * requests), then list the handles in the {{PREFIX}}_dequeue_styles / _scripts filters.
 *
 * @return void
 */
function {{PREFIX}}_dequeue_unused_assets() {
	foreach ( (array) apply_filters( '{{PREFIX}}_dequeue_styles', array() ) as $handle ) {
		wp_dequeue_style( $handle );
	}
	foreach ( (array) apply_filters( '{{PREFIX}}_dequeue_scripts', array() ) as $handle ) {
		wp_dequeue_script( $handle );
	}
}
add_action( 'wp_enqueue_scripts', '{{PREFIX}}_dequeue_unused_assets', 100 );
