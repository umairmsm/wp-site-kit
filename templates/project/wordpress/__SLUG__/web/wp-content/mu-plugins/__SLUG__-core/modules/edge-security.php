<?php
/**
 * Edge security: what the application itself must do on any host.
 *
 * Server rules (.htaccess / nginx, wp-config constants, real cron) are in
 * docs/reference/DEPLOY_HARDENING.md. HSTS and TLS belong to the edge (Cloudflare).
 *
 * @package {{PREFIX_UC}}_Core
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
	define( 'DISALLOW_FILE_EDIT', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- WordPress core constant: no theme or plugin editor in wp-admin.
}

add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'the_generator', '__return_empty_string' );
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'rsd_link' );

/**
 * Drop XML-RPC pingback methods.
 *
 * @param array $methods XML-RPC methods.
 * @return array
 */
function {{PREFIX}}_remove_pingback_methods( $methods ) {
	unset( $methods['pingback.ping'], $methods['pingback.extensions.getPingbacks'] );
	return $methods;
}
add_filter( 'xmlrpc_methods', '{{PREFIX}}_remove_pingback_methods' );

/**
 * Remove X-Pingback and add security headers to every front-end response.
 *
 * @param array $headers Response headers.
 * @return array
 */
function {{PREFIX}}_security_headers( $headers ) {
	unset( $headers['X-Pingback'] );
	$headers['X-Content-Type-Options']  = 'nosniff';
	$headers['X-Frame-Options']         = 'SAMEORIGIN';
	$headers['Content-Security-Policy'] = "frame-ancestors 'self'";
	$headers['Referrer-Policy']         = 'strict-origin-when-cross-origin';
	$headers['Permissions-Policy']      = 'geolocation=(), microphone=(), camera=()';
	return $headers;
}
add_filter( 'wp_headers', '{{PREFIX}}_security_headers' );

/**
 * Stop user enumeration through ?author=N and author archives (they reveal login names).
 *
 * Runs before WordPress's canonical redirect, which would otherwise send ?author=1 to /author/<login>/.
 *
 * @return void
 */
function {{PREFIX}}_block_author_enumeration() {
	if ( is_admin() || is_user_logged_in() ) {
		return;
	}
	$asks_author = isset( $_GET['author'] ) && '' !== sanitize_text_field( wp_unslash( $_GET['author'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only check.
	if ( $asks_author || is_author() ) {
		wp_safe_redirect( (string) apply_filters( '{{PREFIX}}_author_redirect', home_url( '/' ) ), 301 );
		exit;
	}
}
add_action( 'template_redirect', '{{PREFIX}}_block_author_enumeration', 0 );

/**
 * Drop the users sitemap (/wp-sitemap-users-1.xml): it lists author slugs, which are often logins.
 * (Rank Math has its own author sitemap: keep it off in its settings.)
 *
 * @param WP_Sitemaps_Provider|false $provider Sitemap provider.
 * @param string                     $name     Provider name.
 * @return WP_Sitemaps_Provider|false
 */
function {{PREFIX}}_no_user_sitemap( $provider, $name ) {
	return 'users' === $name ? false : $provider;
}
add_filter( 'wp_sitemaps_add_provider', '{{PREFIX}}_no_user_sitemap', 10, 2 );

/**
 * Hide the REST user list from anonymous visitors (401).
 *
 * @param mixed           $result  Response so far (usually null).
 * @param WP_REST_Server  $server  REST server.
 * @param WP_REST_Request $request Request.
 * @return mixed
 */
function {{PREFIX}}_restrict_rest_users( $result, $server, $request ) {
	if ( ! empty( $result ) || is_user_logged_in() ) {
		return $result;
	}
	if ( 0 === strpos( $request->get_route(), '/wp/v2/users' ) ) {
		return new WP_Error(
			'rest_user_cannot_view',
			__( 'Sorry, you are not allowed to list users.', '{{SLUG}}-core' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}
	return $result;
}
add_filter( 'rest_pre_dispatch', '{{PREFIX}}_restrict_rest_users', 10, 3 );

/**
 * Same message for every failed login, so it doesn't confirm which usernames exist.
 *
 * @return string
 */
function {{PREFIX}}_generic_login_error() {
	return __( 'Login failed. Check your details and try again.', '{{SLUG}}-core' );
}
add_filter( 'login_errors', '{{PREFIX}}_generic_login_error' );

/**
 * Cloudflare edge ranges (https://www.cloudflare.com/ips/). Re-check the list once a year.
 *
 * @return string[]
 */
function {{PREFIX}}_cloudflare_cidrs() {
	return (array) apply_filters(
		'{{PREFIX}}_cloudflare_cidrs',
		array(
			'173.245.48.0/20',
			'103.21.244.0/22',
			'103.22.200.0/22',
			'103.31.4.0/22',
			'141.101.64.0/18',
			'108.162.192.0/18',
			'190.93.240.0/20',
			'188.114.96.0/20',
			'197.234.240.0/22',
			'198.41.128.0/17',
			'162.158.0.0/15',
			'104.16.0.0/13',
			'104.24.0.0/14',
			'172.64.0.0/13',
			'131.0.72.0/22',
			'2400:cb00::/32',
			'2606:4700::/32',
			'2803:f800::/32',
			'2405:b500::/32',
			'2405:8100::/32',
			'2a06:98c0::/29',
			'2c0f:f248::/32',
		)
	);
}

/**
 * Whether an IP (v4 or v6) is inside a CIDR block.
 *
 * @param string $ip   IP address.
 * @param string $cidr CIDR block.
 * @return bool
 */
function {{PREFIX}}_ip_in_cidr( $ip, $cidr ) {
	list( $net, $bits ) = array_pad( explode( '/', $cidr ), 2, '' );
	$ip_bin             = filter_var( $ip, FILTER_VALIDATE_IP ) ? inet_pton( $ip ) : false;
	$net_bin            = filter_var( $net, FILTER_VALIDATE_IP ) ? inet_pton( $net ) : false;
	if ( false === $ip_bin || false === $net_bin || strlen( $ip_bin ) !== strlen( $net_bin ) ) {
		return false;
	}
	$bits  = (int) $bits;
	$bytes = intdiv( $bits, 8 );
	if ( substr( $ip_bin, 0, $bytes ) !== substr( $net_bin, 0, $bytes ) ) {
		return false;
	}
	$rem = $bits % 8;
	if ( 0 === $rem ) {
		return true;
	}
	$mask = chr( ( 0xff << ( 8 - $rem ) ) & 0xff );
	return ( $ip_bin[ $bytes ] & $mask ) === ( $net_bin[ $bytes ] & $mask );
}

/**
 * Best-effort visitor IP, for rate limits only (never stored raw).
 *
 * Many hosts already put the real visitor IP in REMOTE_ADDR. CF-Connecting-IP is trusted
 * only when the connection itself comes from Cloudflare; otherwise anyone could forge it.
 *
 * @return string IP address, or '' when unknown.
 */
function {{PREFIX}}_client_ip() {
	$remote = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	// Dual-stack servers report IPv4 visitors as ::ffff:1.2.3.4; compare them as plain IPv4.
	if ( 0 === stripos( $remote, '::ffff:' ) && filter_var( substr( $remote, 7 ), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
		$remote = substr( $remote, 7 );
	}
	$ip = $remote;
	if ( $remote && ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
		foreach ( {{PREFIX}}_cloudflare_cidrs() as $cidr ) {
			if ( {{PREFIX}}_ip_in_cidr( $remote, $cidr ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
				break;
			}
		}
	}
	return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
}

/**
 * Whether an IP is a public internet address (not empty, private, loopback or reserved).
 * Behind a misconfigured proxy every visitor shows the proxy's private IP: never rate-limit those.
 *
 * @param string $ip IP address.
 * @return bool
 */
function {{PREFIX}}_is_public_ip( $ip ) {
	return '' !== $ip && false !== filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );
}
