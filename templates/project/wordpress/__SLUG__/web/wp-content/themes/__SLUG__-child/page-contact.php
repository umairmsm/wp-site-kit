<?php
/**
 * Contact page (slug "contact"): the page's own text, then the core enquiry form.
 *
 * @package {{PREFIX_UC}}_Child
 */

get_header();
?>
<main id="primary" class="site-main contact-page">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article <?php post_class(); ?>>
			<h1><?php the_title(); ?></h1>
			<?php the_content(); ?>
			<?php
			if ( function_exists( '{{PREFIX}}_render_enquiry_form' ) ) {
				{{PREFIX}}_render_enquiry_notice();
				{{PREFIX}}_render_enquiry_form( array( 'context' => 'contact' ) );
			}
			?>
		</article>
	<?php endwhile; ?>
</main>
<?php
get_footer();
