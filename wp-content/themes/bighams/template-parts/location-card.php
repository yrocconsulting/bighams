<?php
/**
 * Location card. Expects $args['loc'] (array from bh_get_location) and optional $args['heading'] tag.
 */

defined( 'ABSPATH' ) || exit;
$loc = $args['loc'] ?? array();
if ( ! $loc ) {
	return;
}
$tag = $args['heading'] ?? 'h3';
?>
<article class="loc-card">
	<a class="loc-card__media" href="<?php echo esc_url( $loc['permalink'] ); ?>" tabindex="-1" aria-hidden="true">
		<?php bht_thumb( $loc['image_id'], 'bh-card', $loc['short_name'], 'loc-card__img' ); ?>
		<span class="status status--chip <?php echo $loc['status']['open'] ? 'status--open' : 'status--closed'; ?>"><span class="status__dot"></span><?php echo esc_html( $loc['status']['label'] ); ?></span>
	</a>
	<div class="loc-card__body">
		<<?php echo tag_escape( $tag ); ?> class="loc-card__title"><a href="<?php echo esc_url( $loc['permalink'] ); ?>"><?php echo esc_html( $loc['title'] ); ?></a></<?php echo tag_escape( $tag ); ?>>
		<?php if ( $loc['cross_street'] ) : ?>
			<p class="loc-card__cross"><?php echo esc_html( $loc['cross_street'] ); ?></p>
		<?php endif; ?>
		<ul class="loc-card__meta">
			<li><?php echo bht_icon( 'pin' ); // phpcs:ignore ?><span><?php echo esc_html( $loc['full_address'] ); ?></span></li>
			<li><?php echo bht_icon( 'clock' ); // phpcs:ignore ?><span><?php esc_html_e( 'Today:', 'bighams' ); ?> <?php echo esc_html( $loc['status']['today'] ); ?></span></li>
			<li><?php echo bht_icon( 'phone' ); // phpcs:ignore ?><a href="tel:<?php echo esc_attr( $loc['phone_e164'] ); ?>"><?php echo esc_html( $loc['phone'] ); ?></a></li>
		</ul>
		<?php if ( $loc['features'] ) : ?>
			<ul class="tags">
				<?php foreach ( array_slice( $loc['features'], 0, 4 ) as $f ) : ?>
					<li><?php echo esc_html( $f ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<div class="loc-card__actions">
			<?php if ( $loc['order_url'] ) : ?>
				<a class="btn btn--primary btn--sm" href="<?php echo esc_url( $loc['order_url'] ); ?>" target="_blank" rel="noopener"<?php echo bht_track( 'order', $loc['id'] ); // phpcs:ignore ?>><?php esc_html_e( 'Order', 'bighams' ); ?><span class="screen-reader-text"> <?php echo esc_html( $loc['short_name'] ); ?></span></a>
			<?php endif; ?>
			<a class="btn btn--outline btn--sm" href="<?php echo esc_url( $loc['directions_url'] ); ?>" target="_blank" rel="noopener"<?php echo bht_track( 'directions', $loc['id'] ); // phpcs:ignore ?>><?php esc_html_e( 'Directions', 'bighams' ); ?></a>
			<a class="link-arrow" href="<?php echo esc_url( $loc['permalink'] ); ?>"><?php esc_html_e( 'Details', 'bighams' ); ?> <?php echo bht_icon( 'arrow' ); // phpcs:ignore ?><span class="screen-reader-text"> — <?php echo esc_html( $loc['title'] ); ?></span></a>
		</div>
	</div>
</article>
