<?php
/**
 * Content model: what the site sells or shows lives in posts, so editors change it in the CMS.
 *
 * Templates never hard-code cards, prices or slugs. They call {{PREFIX}}_get_offers() and
 * {{PREFIX}}_render_offer_card(). Rename "offer" to the business's word (tour, service,
 * course) in the first build step, before any content exists.
 *
 * @package {{PREFIX_UC}}_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the offer type and its fields.
 *
 * @return void
 */
function {{PREFIX}}_register_offer_type() {
	register_post_type(
		'{{PREFIX}}_offer',
		array(
			'labels'       => array(
				'name'          => __( 'Offers', '{{SLUG}}-core' ),
				'singular_name' => __( 'Offer', '{{SLUG}}-core' ),
				'add_new_item'  => __( 'Add offer', '{{SLUG}}-core' ),
				'edit_item'     => __( 'Edit offer', '{{SLUG}}-core' ),
			),
			'description'  => __( 'Every offer we run, with what is included and the starting price.', '{{SLUG}}-core' ),
			'public'       => true,
			'has_archive'  => true,
			'rewrite'      => array( 'slug' => 'offers' ),
			'menu_icon'    => 'dashicons-tickets-alt',
			'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes' ),
			'show_in_rest' => true,
		)
	);
	register_post_meta(
		'{{PREFIX}}_offer',
		'{{PREFIX}}_price_from',
		array(
			'type'              => 'number',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => '{{PREFIX}}_sanitize_price',
			'auth_callback'     => '{{PREFIX}}_can_edit_offers',
		)
	);
}
add_action( 'init', '{{PREFIX}}_register_offer_type' );

/**
 * Whether the current user may edit offer fields.
 *
 * @return bool
 */
function {{PREFIX}}_can_edit_offers() {
	return current_user_can( 'edit_posts' );
}

/**
 * A price is a non-negative number with at most two decimals. 0 means "on request".
 *
 * @param mixed $value Raw value.
 * @return float
 */
function {{PREFIX}}_sanitize_price( $value ) {
	return round( max( 0, (float) $value ), 2 );
}

/**
 * The site's currency code. Set {{PREFIX_UC}}_CURRENCY in wp-config.php to change it.
 *
 * @return string
 */
function {{PREFIX}}_currency() {
	return defined( '{{PREFIX_UC}}_CURRENCY' ) ? (string) constant( '{{PREFIX_UC}}_CURRENCY' ) : 'EUR';
}

/**
 * Format a price for display. Never shows 0: an unknown price reads "Price on request".
 *
 * @param float $amount Amount.
 * @return string
 */
function {{PREFIX}}_format_price( $amount ) {
	$amount = (float) $amount;
	if ( $amount <= 0 ) {
		return __( 'Price on request', '{{SLUG}}-core' );
	}
	$decimals = floor( $amount ) === $amount ? 0 : 2;
	/* translators: 1: amount, 2: currency code. */
	return sprintf( __( 'From %1$s %2$s', '{{SLUG}}-core' ), number_format_i18n( $amount, $decimals ), {{PREFIX}}_currency() );
}

/**
 * Published offers, in the editor's order (Page attributes > Order), then by title.
 *
 * @param array $args WP_Query arguments to override.
 * @return WP_Post[]
 */
function {{PREFIX}}_get_offers( $args = array() ) {
	$query = new WP_Query(
		wp_parse_args(
			$args,
			array(
				'post_type'      => '{{PREFIX}}_offer',
				'post_status'    => 'publish',
				'posts_per_page' => 12,
				'orderby'        => array(
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				),
				'no_found_rows'  => true,
			)
		)
	);
	return $query->posts;
}

/**
 * Everything a card needs, ready to print. Layouts read this; they never query on their own.
 *
 * @param int|WP_Post $post Offer.
 * @return array Empty when the post doesn't exist.
 */
function {{PREFIX}}_offer_card_data( $post ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return array();
	}
	$price = (float) get_post_meta( $post->ID, '{{PREFIX}}_price_from', true );
	return array(
		'id'        => $post->ID,
		'title'     => get_the_title( $post ),
		'url'       => get_permalink( $post ),
		'excerpt'   => wp_strip_all_tags( get_the_excerpt( $post ) ),
		'price'     => $price,
		'price_txt' => {{PREFIX}}_format_price( $price ),
		'image'     => get_the_post_thumbnail(
			$post,
			'medium_large',
			array(
				'loading'  => 'lazy',
				'decoding' => 'async',
			)
		),
	);
}

/**
 * Print one offer card: the theme's part when it has one, else working default markup.
 *
 * @param int|WP_Post $post Offer.
 * @return void
 */
function {{PREFIX}}_render_offer_card( $post ) {
	$data = {{PREFIX}}_offer_card_data( $post );
	if ( empty( $data ) ) {
		return;
	}
	if ( function_exists( '{{PREFIX}}_part' ) ) {
		{{PREFIX}}_part( 'offer-card', array( 'offer' => $data ) );
		return;
	}
	printf(
		'<article class="offer-card"><h3 class="offer-card__title"><a href="%1$s">%2$s</a></h3><p class="offer-card__price">%3$s</p></article>',
		esc_url( $data['url'] ),
		esc_html( $data['title'] ),
		esc_html( $data['price_txt'] )
	);
}

/**
 * Cache expensive derived data (parsing, remote calls) until content changes.
 *
 * Every save of an offer or page bumps the cache version, so editors never wait for a
 * cache to expire. Don't use this for plain WP_Query calls; the object cache covers those.
 *
 * @param string   $key   Short key, unique per use.
 * @param int      $ttl   Seconds to keep it.
 * @param callable $build Builds the value when the cache is empty.
 * @return mixed
 */
function {{PREFIX}}_cached( $key, $ttl, $build ) {
	$name  = '{{PREFIX}}_' . $key . '_v' . (int) get_option( '{{PREFIX}}_cache_version', 1 );
	$value = get_transient( $name );
	if ( false === $value ) {
		$value = call_user_func( $build );
		set_transient( $name, $value, $ttl );
	}
	return $value;
}

/**
 * Invalidate everything stored with {{PREFIX}}_cached().
 *
 * @return void
 */
function {{PREFIX}}_bump_cache_version() {
	update_option( '{{PREFIX}}_cache_version', (int) get_option( '{{PREFIX}}_cache_version', 1 ) + 1, false );
}
add_action( 'save_post_{{PREFIX}}_offer', '{{PREFIX}}_bump_cache_version' );
add_action( 'save_post_page', '{{PREFIX}}_bump_cache_version' );
add_action( 'deleted_post', '{{PREFIX}}_bump_cache_version' );
