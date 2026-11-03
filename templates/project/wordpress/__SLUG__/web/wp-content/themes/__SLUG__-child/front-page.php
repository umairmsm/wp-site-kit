<?php
/**
 * Front page: layout only. Each section calls one core function.
 *
 * @package {{PREFIX_UC}}_Child
 */

get_header();
?>
<main id="primary" class="site-main front-page">
	<section class="hero">
		<h1 class="hero__title"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></h1>
		<p class="hero__lead"><?php echo esc_html( get_bloginfo( 'description' ) ); ?></p>
		<p><a class="button" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'Send an enquiry', '{{SLUG}}-child' ); ?></a></p>
	</section>

	<section class="offers" aria-labelledby="offers-title">
		<h2 id="offers-title"><?php esc_html_e( 'What we offer', '{{SLUG}}-child' ); ?></h2>
		<?php
		${{PREFIX}}_offers = function_exists( '{{PREFIX}}_get_offers' ) ? {{PREFIX}}_get_offers() : array();
		if ( ${{PREFIX}}_offers ) :
			?>
			<div class="offers__grid">
				<?php
				foreach ( ${{PREFIX}}_offers as ${{PREFIX}}_offer ) {
					{{PREFIX}}_render_offer_card( ${{PREFIX}}_offer );
				}
				?>
			</div>
		<?php else : ?>
			<p><?php esc_html_e( 'New offers are coming soon.', '{{SLUG}}-child' ); ?></p>
		<?php endif; ?>
	</section>
</main>
<?php
get_footer();
