<?php
/**
 * {{SITE_NAME}} core: one module per concern.
 *
 * Plugin = the kitchen: data, queries, forms, schema, SEO and every policy sentence.
 * Theme = layout. Skin = look. A redesign must never lose a feature.
 * Adding or removing a module? Update tests/structure/core.spec.ts in the same commit.
 *
 * @package {{PREFIX_UC}}_Core
 */

defined( 'ABSPATH' ) || exit;

define( '{{PREFIX_UC}}_CORE_VERSION', '0.1.0' );
define( '{{PREFIX_UC}}_CORE_DIR', __DIR__ );

require_once __DIR__ . '/modules/environment.php'; // First: other modules ask which environment this is.
require_once __DIR__ . '/modules/edge-security.php';
require_once __DIR__ . '/modules/content.php';
require_once __DIR__ . '/modules/media.php';
require_once __DIR__ . '/modules/enquiry.php';
require_once __DIR__ . '/modules/seo.php';
require_once __DIR__ . '/modules/schema.php'; // After seo.php: it asks whether an SEO plugin owns the schema.
require_once __DIR__ . '/modules/performance.php';

/**
 * Load the core's translations (.po/.mo live in the repo, never in the database).
 *
 * @return void
 */
function {{PREFIX}}_core_load_textdomain() {
	load_muplugin_textdomain( '{{SLUG}}-core', '{{SLUG}}-core/languages' );
}
add_action( 'init', '{{PREFIX}}_core_load_textdomain' );
