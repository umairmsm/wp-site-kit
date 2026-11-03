<?php
/**
 * Offer card layout. Data comes from {{PREFIX}}_offer_card_data() in the core plugin;
 * this file only decides where things go. A skin can override it in skins/<skin>/parts/.
 *
 * @package {{PREFIX_UC}}_Child
 */

${{PREFIX}}_card = isset( $args['offer'] ) ? (array) $args['offer'] : array();
if ( empty( ${{PREFIX}}_card ) ) {
	return;
}
?>
<article class="offer-card">
	<?php if ( ! empty( ${{PREFIX}}_card['image'] ) ) : ?>
		<a class="offer-card__media" href="<?php echo esc_url( ${{PREFIX}}_card['url'] ); ?>" tabindex="-1" aria-hidden="true"><?php echo wp_kses_post( ${{PREFIX}}_card['image'] ); ?></a>
	<?php endif; ?>
	<h3 class="offer-card__title"><a href="<?php echo esc_url( ${{PREFIX}}_card['url'] ); ?>"><?php echo esc_html( ${{PREFIX}}_card['title'] ); ?></a></h3>
	<?php if ( '' !== ${{PREFIX}}_card['excerpt'] ) : ?>
		<p class="offer-card__excerpt"><?php echo esc_html( ${{PREFIX}}_card['excerpt'] ); ?></p>
	<?php endif; ?>
	<p class="offer-card__price"><?php echo esc_html( ${{PREFIX}}_card['price_txt'] ); ?></p>
</article>
