<?php
/**
 * E-gift card promo pop-up for first-time visitors (Settings → Rewards & Gift Cards).
 * Desktop: centered dialog. Phones: a bottom sheet that doesn't cover the page.
 */

defined( 'ABSPATH' ) || exit;
$url   = bht_setting( 'gift_card_url' );
$until = bht_setting( 'giftcard_until' );
if ( ! $url || '1' !== (string) bht_setting( 'giftcard_popup', '1' ) || ( $until && wp_date( 'Y-m-d' ) > $until ) ) {
	return;
}
$code  = bht_setting( 'giftcard_code' );
$offer = bht_setting( 'giftcard_offer', '$10 off every $50 gift card purchase' );
// Changing the offer or code shows the pop-up again to people who closed an older one.
$key = substr( md5( $offer . '|' . $code ), 0, 8 );
?>
<dialog class="gift-pop" id="gift-pop" aria-labelledby="gift-pop-title" data-gift-pop="<?php echo esc_attr( $key ); ?>">
	<button class="gift-pop__close" type="button" data-gift-close aria-label="<?php esc_attr_e( 'Close', 'bighams' ); ?>"><?php echo bht_icon( 'close' ); // phpcs:ignore ?></button>
	<div class="gift-pop__body" tabindex="-1" autofocus>
		<span class="gift-pop__icon" aria-hidden="true"><?php echo bht_icon( 'gift' ); // phpcs:ignore ?></span>
		<p class="eyebrow"><?php esc_html_e( 'E-gift cards', 'bighams' ); ?></p>
		<h2 id="gift-pop-title"><?php esc_html_e( 'Give the gift of BBQ', 'bighams' ); ?></h2>
		<p class="gift-pop__offer"><?php echo esc_html( $offer ); ?></p>
		<?php if ( $code ) : ?>
			<div class="gift-pop__code">
				<span><?php esc_html_e( 'Use code at checkout', 'bighams' ); ?></span>
				<button type="button" class="gift-pop__copy" data-gift-copy="<?php echo esc_attr( $code ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s promo code */ __( 'Copy code %s', 'bighams' ), $code ) ); ?>">
					<strong><?php echo esc_html( $code ); ?></strong>
					<em data-gift-copied><?php esc_html_e( 'Tap to copy', 'bighams' ); ?></em>
				</button>
			</div>
		<?php endif; ?>
		<a class="btn btn--primary btn--block btn--lg" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener" data-gift-go<?php echo bht_track( 'gift_card' ); // phpcs:ignore ?>><?php esc_html_e( 'Buy e-gift cards', 'bighams' ); ?><span class="screen-reader-text"> (<?php esc_html_e( 'opens Toast in a new tab', 'bighams' ); ?>)</span></a>
		<button type="button" class="gift-pop__later" data-gift-close><?php esc_html_e( 'No thanks', 'bighams' ); ?></button>
	</div>
</dialog>
