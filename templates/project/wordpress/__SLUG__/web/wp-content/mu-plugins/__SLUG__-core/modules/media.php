<?php
/**
 * Photo library: curated site photos, version-controlled with the theme.
 *
 * Files live in the theme at assets/img/photos/<key>.webp (max 1600 px wide) and
 * <key>-sm.webp (800 px). Alt text and sizes live in one registry, the theme's
 * photo-library.php, never in templates. Editor-managed images (featured images)
 * stay in the Media Library.
 *
 * Rules: real photos only for named places, rooms, products and people. AI scenery
 * keys start with "ai-" and are listed in docs/data/photos/AI_IMAGES.md.
 *
 * @package {{PREFIX_UC}}_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * The photo registry: key => array( alt, w, h, credit ).
 *
 * @return array
 */
function {{PREFIX}}_photo_library() {
	static $library = null;
	if ( null === $library ) {
		$file    = get_stylesheet_directory() . '/photo-library.php';
		$library = is_readable( $file ) ? (array) include $file : array();
		$library = (array) apply_filters( '{{PREFIX}}_photo_library', $library );
	}
	return $library;
}

/**
 * A responsive <img> for a library photo, or '' for an unknown key (never a broken image).
 *
 * @param string $key  Photo key.
 * @param array  $args sizes (CSS sizes attribute), class, eager (true for the LCP image only).
 * @return string HTML.
 */
function {{PREFIX}}_photo( $key, $args = array() ) {
	$library = {{PREFIX}}_photo_library();
	if ( empty( $library[ $key ] ) ) {
		return '';
	}
	$photo = wp_parse_args(
		$library[ $key ],
		array(
			'alt' => '',
			'w'   => 1600,
			'h'   => 1067,
		)
	);
	$args  = wp_parse_args(
		$args,
		array(
			'sizes' => '100vw',
			'class' => '',
			'eager' => false,
		)
	);
	$base  = get_stylesheet_directory_uri() . '/assets/img/photos/' . rawurlencode( $key );
	$attrs = array(
		'src'      => esc_url( $base . '.webp' ),
		'srcset'   => esc_attr( esc_url( $base . '-sm.webp' ) . ' 800w, ' . esc_url( $base . '.webp' ) . ' ' . (int) $photo['w'] . 'w' ),
		'sizes'    => esc_attr( $args['sizes'] ),
		'width'    => (int) $photo['w'],
		'height'   => (int) $photo['h'],
		'alt'      => esc_attr( $photo['alt'] ), // An empty alt stays: it marks a decorative photo.
		'loading'  => $args['eager'] ? 'eager' : 'lazy',
		'decoding' => 'async',
	);
	if ( $args['eager'] ) {
		$attrs['fetchpriority'] = 'high';
	}
	if ( '' !== $args['class'] ) {
		$attrs['class'] = esc_attr( $args['class'] );
	}
	$html = '<img';
	foreach ( $attrs as $name => $value ) {
		$html .= ' ' . $name . '="' . $value . '"';
	}
	return $html . '>';
}
