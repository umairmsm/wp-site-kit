<?php
/**
 * Environment: local and staging are never indexed and never email real customers.
 *
 * WordPress reads WP_ENVIRONMENT_TYPE (local, development, staging, production).
 * DDEV sets "local" in .ddev/config.yaml; staging sets "staging" in wp-config.php.
 * Unset means "production". Check it on every new machine:
 * `ddev wp eval 'echo wp_get_environment_type();'`
 *
 * @package {{PREFIX_UC}}_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether this is the live site.
 *
 * @return bool
 */
function {{PREFIX}}_is_production() {
	return 'production' === wp_get_environment_type();
}

/**
 * Ask robots not to index anything outside production.
 *
 * @param array $robots Robots directives.
 * @return array
 */
function {{PREFIX}}_noindex_outside_production( $robots ) {
	if ( ! {{PREFIX}}_is_production() ) {
		$robots['noindex']  = true;
		$robots['nofollow'] = true;
	}
	return $robots;
}
add_filter( 'wp_robots', '{{PREFIX}}_noindex_outside_production', 99 );

/**
 * Send X-Robots-Tag outside production, so it holds even if an SEO plugin rewrites the meta tag.
 *
 * @param array $headers Response headers.
 * @return array
 */
function {{PREFIX}}_robots_header_outside_production( $headers ) {
	if ( ! {{PREFIX}}_is_production() ) {
		$headers['X-Robots-Tag'] = 'noindex, nofollow';
	}
	return $headers;
}
add_filter( 'wp_headers', '{{PREFIX}}_robots_header_outside_production' );

/**
 * Block all crawling in robots.txt outside production.
 *
 * @param string $output Robots.txt content.
 * @return string
 */
function {{PREFIX}}_robots_txt_outside_production( $output ) {
	if ( ! {{PREFIX}}_is_production() ) {
		return "User-agent: *\nDisallow: /\n";
	}
	return $output;
}
add_filter( 'robots_txt', '{{PREFIX}}_robots_txt_outside_production', 99 );

/**
 * Outside production, send every email to one test inbox, never to customers.
 *
 * The test inbox is {{PREFIX_UC}}_ENQUIRY_TO when wp-config.php defines it (set it to a tester's
 * address on staging), else the site admin email. A staging copy of the live database carries
 * the business owner's admin email, so define the constant on staging.
 * The original recipients go into the subject; Cc and Bcc headers are dropped.
 *
 * @param array $args wp_mail() arguments.
 * @return array
 */
function {{PREFIX}}_guard_mail_outside_production( $args ) {
	if ( {{PREFIX}}_is_production() ) {
		return $args;
	}
	$inbox           = defined( '{{PREFIX_UC}}_ENQUIRY_TO' ) && is_email( constant( '{{PREFIX_UC}}_ENQUIRY_TO' ) ) ? constant( '{{PREFIX_UC}}_ENQUIRY_TO' ) : get_option( 'admin_email' );
	$to              = is_array( $args['to'] ) ? implode( ', ', $args['to'] ) : (string) $args['to'];
	$args['subject'] = sprintf( '[%s, to: %s] %s', strtoupper( wp_get_environment_type() ), $to, $args['subject'] );
	$args['to']      = $inbox;
	$headers         = $args['headers'];
	if ( ! is_array( $headers ) ) {
		$headers = explode( "\n", str_replace( "\r\n", "\n", (string) $headers ) );
	}
	$args['headers'] = array_values(
		array_filter(
			$headers,
			static function ( $line ) {
				return ! preg_match( '/^\s*b?cc\s*:/i', (string) $line );
			}
		)
	);
	return $args;
}
add_filter( 'wp_mail', '{{PREFIX}}_guard_mail_outside_production', 99 );

/**
 * Show the environment in the admin bar, so nobody edits live by mistake.
 *
 * @param WP_Admin_Bar $bar Admin bar.
 * @return void
 */
function {{PREFIX}}_environment_badge( $bar ) {
	$bar->add_node(
		array(
			'id'    => '{{PREFIX}}-environment',
			'title' => esc_html( strtoupper( wp_get_environment_type() ) ),
		)
	);
}
add_action( 'admin_bar_menu', '{{PREFIX}}_environment_badge', 1 );
