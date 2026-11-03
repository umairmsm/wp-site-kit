<?php
/**
 * Structured data (JSON-LD), with one owner.
 *
 * With an SEO plugin active, our nodes join its graph (Rank Math: rank_math/json_ld), so
 * the page has one JSON-LD graph, not two that disagree. Without one, we print the graph.
 * Domain plugins that print their own schema must be silenced (see wp-seo skill).
 * Never print an Offer without a price: Google's Rich Results test reports it as an error.
 *
 * @package {{PREFIX_UC}}_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * The schema nodes for the current page.
 *
 * @param bool $with_organization Include the Organization node (SEO plugins print their own).
 * @return array[]
 */
function {{PREFIX}}_schema_nodes( $with_organization ) {
	$org_id = home_url( '/#organization' );
	$nodes  = array();
	if ( $with_organization ) {
		$nodes[] = array(
			'@type' => 'Organization',
			'@id'   => $org_id,
			'name'  => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			'url'   => home_url( '/' ),
		);
	}
	if ( is_singular( '{{PREFIX}}_offer' ) ) {
		$id    = get_queried_object_id();
		$node  = array(
			'@type'       => 'Service',
			'@id'         => get_permalink( $id ) . '#service',
			'name'        => wp_strip_all_tags( get_the_title( $id ) ),
			'url'         => get_permalink( $id ),
			'description' => wp_strip_all_tags( get_the_excerpt( $id ) ),
			'provider'    => array( '@id' => $org_id ),
		);
		$price = (float) get_post_meta( $id, '{{PREFIX}}_price_from', true );
		if ( $price > 0 ) {
			$node['offers'] = array(
				'@type'         => 'Offer',
				'price'         => $price,
				'priceCurrency' => {{PREFIX}}_currency(),
				'url'           => get_permalink( $id ),
			);
		}
		$nodes[] = $node;
	}
	return (array) apply_filters( '{{PREFIX}}_schema_nodes', $nodes );
}

/**
 * Add our nodes to Rank Math's graph.
 *
 * @param array $data Rank Math's schema entities.
 * @return array
 */
function {{PREFIX}}_rank_math_schema( $data ) {
	foreach ( {{PREFIX}}_schema_nodes( false ) as $i => $node ) {
		$data[ '{{PREFIX}}_' . $i ] = $node;
	}
	return $data;
}
add_filter( 'rank_math/json_ld', '{{PREFIX}}_rank_math_schema', 99 );

/**
 * Print our own graph when no SEO plugin owns schema.
 *
 * @return void
 */
function {{PREFIX}}_print_schema() {
	if ( {{PREFIX}}_seo_plugin_active() ) {
		return;
	}
	$graph = {{PREFIX}}_schema_nodes( true );
	if ( empty( $graph ) ) {
		return;
	}
	$json = wp_json_encode(
		array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		),
		JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
	);
	echo '<script type="application/ld+json">' . $json . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON with < > & hex-escaped.
}
add_action( 'wp_head', '{{PREFIX}}_print_schema', 20 );
