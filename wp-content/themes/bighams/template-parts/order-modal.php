<?php
/**
 * Order Online location picker (native <dialog>).
 */

defined( 'ABSPATH' ) || exit;
$locations = bht_locations();
?>
<dialog class="order-modal" id="order-modal" aria-labelledby="order-modal-title">
	<div class="order-modal__inner">
		<header class="order-modal__head">
			<p class="eyebrow"><?php esc_html_e( 'Pickup · Delivery', 'bighams' ); ?></p>
			<h2 id="order-modal-title"><?php esc_html_e( 'Choose your Bigham\'s', 'bighams' ); ?></h2>
			<button class="order-modal__close" type="button" data-order-close aria-label="<?php esc_attr_e( 'Close', 'bighams' ); ?>"><?php echo bht_icon( 'close' ); // phpcs:ignore ?></button>
		</header>

		<?php if ( $locations ) : ?>
			<ul class="order-list">
				<?php foreach ( $locations as $loc ) : ?>
					<li class="order-list__item">
						<div class="order-list__info">
							<h3><?php echo esc_html( $loc['title'] ); ?></h3>
							<p class="order-list__addr"><?php echo esc_html( $loc['full_address'] ); ?></p>
							<p class="status <?php echo $loc['status']['open'] ? 'status--open' : 'status--closed'; ?>"><span class="status__dot" aria-hidden="true"></span><?php echo esc_html( $loc['status']['label'] ); ?></p>
						</div>
						<div class="order-list__actions">
							<?php if ( $loc['order_url'] ) : ?>
								<a class="btn btn--primary" href="<?php echo esc_url( $loc['order_url'] ); ?>" target="_blank" rel="noopener"<?php echo bht_track( 'order', $loc['id'] ); // phpcs:ignore ?>><?php esc_html_e( 'Order pickup', 'bighams' ); ?><span class="screen-reader-text"> — <?php echo esc_html( $loc['title'] ); ?> (<?php esc_html_e( 'opens Toast in a new tab', 'bighams' ); ?>)</span></a>
							<?php else : ?>
								<a class="btn btn--primary" href="tel:<?php echo esc_attr( $loc['phone_e164'] ); ?>"><?php esc_html_e( 'Call to order', 'bighams' ); ?></a>
							<?php endif; ?>
							<?php if ( $loc['ubereats_url'] || $loc['doordash_url'] ) : ?>
								<div class="order-list__delivery">
									<?php if ( $loc['ubereats_url'] ) : ?>
										<a href="<?php echo esc_url( $loc['ubereats_url'] ); ?>" target="_blank" rel="noopener"<?php echo bht_track( 'delivery', $loc['id'] ); // phpcs:ignore ?>>Uber Eats</a>
									<?php endif; ?>
									<?php if ( $loc['doordash_url'] ) : ?>
										<a href="<?php echo esc_url( $loc['doordash_url'] ); ?>" target="_blank" rel="noopener"<?php echo bht_track( 'delivery', $loc['id'] ); // phpcs:ignore ?>>DoorDash</a>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p><?php esc_html_e( 'Call us to order:', 'bighams' ); ?> <a href="tel:<?php echo esc_attr( bht_tel( bht_setting( 'phone' ) ) ); ?>"><?php echo esc_html( bht_setting( 'phone' ) ); ?></a></p>
		<?php endif; ?>

		<footer class="order-modal__foot">
			<?php echo bht_icon( 'users' ); // phpcs:ignore ?>
			<p><?php esc_html_e( 'Feeding a group of 10 or more?', 'bighams' ); ?> <a href="<?php echo esc_url( home_url( '/catering/' ) ); ?>"><?php esc_html_e( 'See catering packages', 'bighams' ); ?></a></p>
		</footer>
	</div>
</dialog>
