<?php
/**
 * Closing CTA band. $args: title, text.
 */

defined( 'ABSPATH' ) || exit;
$title = $args['title'] ?? __( 'Hungry Yet?', 'bighams' );
$text  = $args['text'] ?? __( 'Order ahead for pickup, find the Bigham\'s closest to you, or let us cater your next event.', 'bighams' );
?>
<section class="cta-band" aria-labelledby="cta-band-title">
	<div class="container cta-band__inner">
		<div>
			<h2 id="cta-band-title" class="cta-band__title"><?php echo esc_html( $title ); ?></h2>
			<p><?php echo esc_html( $text ); ?></p>
		</div>
		<div class="cta-band__actions">
			<button class="btn btn--light btn--lg" type="button" data-order-open aria-haspopup="dialog" aria-controls="order-modal"><?php echo bht_icon( 'bag' ); // phpcs:ignore ?> <?php esc_html_e( 'Order Online', 'bighams' ); ?></button>
			<a class="btn btn--outline-light btn--lg" href="<?php echo esc_url( home_url( '/catering/' ) ); ?>"><?php esc_html_e( 'Plan Catering', 'bighams' ); ?></a>
		</div>
	</div>
</section>
