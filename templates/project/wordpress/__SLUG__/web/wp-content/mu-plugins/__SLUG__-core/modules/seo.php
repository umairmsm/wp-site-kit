<?php
/**
 * SEO basics the application owns. Titles, sitemaps and most meta belong to the one SEO
 * plugin (Rank Math by default); this module fills gaps and serves /llms.txt.
 *
 * @package {{PREFIX_UC}}_Core
 */

defined( 'ABSPATH' ) || exit;

// A 404 stays a 404: don't let WordPress guess a "nearby" URL and redirect to it.
add_filter( 'strict_redirect_guess_404_permalink', '__return_true' );

/**
 * Whether a full SEO plugin owns titles, meta and schema.
 *
 * @return bool
 */
function {{PREFIX}}_seo_plugin_active() {
	return defined( 'RANK_MATH_VERSION' ) || defined( 'WPSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' );
}

/**
 * Utility pages (search, thank-you, form states) are not search results: noindex, follow.
 *
 * @param array $robots Robots directives.
 * @return array
 */
function {{PREFIX}}_noindex_utility_pages( $robots ) {
	$form_state = isset( $_GET['enquiry'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only flag.
	if ( is_search() || is_404() || is_page( 'thank-you' ) || $form_state ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
	}
	return $robots;
}
add_filter( 'wp_robots', '{{PREFIX}}_noindex_utility_pages' );

/**
 * Keep the noindexed thank-you page out of the WordPress sitemap; a sitemap URL marked noindex is
 * reported as an error in Search Console. (With Rank Math, set the page to noindex there too.)
 *
 * @param array  $args      Sitemap query arguments.
 * @param string $post_type Post type of this sitemap.
 * @return array
 */
function {{PREFIX}}_sitemap_skip_utility_pages( $args, $post_type ) {
	if ( 'page' === $post_type ) {
		$thank_you = get_page_by_path( 'thank-you', OBJECT, array( 'page' ) );
		if ( $thank_you ) {
			$args['post__not_in'] = array_merge( isset( $args['post__not_in'] ) ? (array) $args['post__not_in'] : array(), array( $thank_you->ID ) );
		}
	}
	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', '{{PREFIX}}_sitemap_skip_utility_pages', 10, 2 );

/**
 * Until an SEO plugin owns titles: drop the tagline from the front page title when the
 * two together would pass 60 characters (long titles get cut in search results).
 *
 * @param array $parts Title parts.
 * @return array
 */
function {{PREFIX}}_fallback_title_parts( $parts ) {
	if ( ! {{PREFIX}}_seo_plugin_active() && is_front_page() && isset( $parts['tagline'] ) ) {
		$length = mb_strlen( wp_strip_all_tags( $parts['title'] . ' – ' . $parts['tagline'] ) );
		if ( $length > 60 ) {
			unset( $parts['tagline'] );
		}
	}
	return $parts;
}
add_filter( 'document_title_parts', '{{PREFIX}}_fallback_title_parts' );

/**
 * A meta description and canonical while no SEO plugin is active, so early pages never ship
 * without them. WordPress itself prints a canonical only on single posts and pages.
 *
 * @return void
 */
function {{PREFIX}}_fallback_head_tags() {
	if ( {{PREFIX}}_seo_plugin_active() ) {
		return;
	}
	$text      = '';
	$canonical = '';
	if ( is_front_page() ) {
		$text      = get_bloginfo( 'description' );
		$canonical = is_singular() ? '' : home_url( '/' ); // A static front page gets WordPress's own canonical.
	} elseif ( is_singular() ) {
		$text = get_the_excerpt( get_queried_object_id() );
	} elseif ( is_post_type_archive() ) {
		$text      = get_the_post_type_description();
		$text      = '' !== trim( wp_strip_all_tags( $text ) ) ? $text : get_bloginfo( 'description' );
		$canonical = get_pagenum_link( max( 1, (int) get_query_var( 'paged' ) ) );
	}
	$text = trim( wp_strip_all_tags( (string) $text ) );
	if ( '' !== $text ) {
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( wp_html_excerpt( $text, 155, '…' ) ) );
	}
	if ( '' !== $canonical ) {
		printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $canonical ) );
	}
}
add_action( 'wp_head', '{{PREFIX}}_fallback_head_tags', 1 );

/**
 * Plain text from WordPress output: no tags, no HTML entities (for llms.txt).
 *
 * @param string $text Text that may hold tags and entities.
 * @return string
 */
function {{PREFIX}}_plain_text( $text ) {
	return trim( html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
}

/**
 * Build /llms.txt: a plain map of the site for AI assistants (llmstxt.org format).
 *
 * @return string
 */
function {{PREFIX}}_llms_txt() {
	return (string) {{PREFIX}}_cached(
		'llms_txt',
		12 * HOUR_IN_SECONDS,
		static function () {
			$out = '# ' . {{PREFIX}}_plain_text( get_bloginfo( 'name' ) ) . "\n\n";
			$tag = {{PREFIX}}_plain_text( get_bloginfo( 'description' ) );
			if ( '' !== $tag ) {
				$out .= '> ' . $tag . "\n\n";
			}
			$sections = array(
				'Pages'  => 'page',
				'Offers' => '{{PREFIX}}_offer',
			);
			foreach ( $sections as $label => $type ) {
				$posts = get_posts(
					array(
						'post_type'      => $type,
						'post_status'    => 'publish',
						'posts_per_page' => 100,
						'orderby'        => 'menu_order title',
						'order'          => 'ASC',
					)
				);
				if ( ! $posts ) {
					continue;
				}
				$out .= '## ' . $label . "\n";
				foreach ( $posts as $post ) {
					$summary = {{PREFIX}}_plain_text( get_the_excerpt( $post ) );
					$out    .= sprintf( '- [%s](%s)', {{PREFIX}}_plain_text( get_the_title( $post ) ), get_permalink( $post ) );
					$out    .= '' !== $summary ? ': ' . wp_html_excerpt( $summary, 160, '…' ) . "\n" : "\n";
				}
				$out .= "\n";
			}
			return $out;
		}
	);
}

/**
 * Serve /llms.txt before WordPress decides it is a 404.
 *
 * @return void
 */
function {{PREFIX}}_serve_llms_txt() {
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	$home = untrailingslashit( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ) );
	if ( $home . '/llms.txt' !== $path ) {
		return;
	}
	header( 'Content-Type: text/plain; charset=utf-8' );
	echo {{PREFIX}}_llms_txt(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- text/plain, tags stripped.
	exit;
}
add_action( 'parse_request', '{{PREFIX}}_serve_llms_txt', 0 );
