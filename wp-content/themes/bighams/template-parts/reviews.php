<?php
/**
 * Reviews carousel (scroll-snap). Silent when no reviews.
 */

defined( 'ABSPATH' ) || exit;
$reviews = bht_reviews( 8 );
if ( ! $reviews ) {
	return;
}
$rating = bht_setting( 'rating_value' );
$count  = bht_setting( 'rating_count' );
?>
<section class="section section--dark reviews" aria-labelledby="reviews-title">
	<div class="container">
		<div class="section-head section-head--split">
			<div>
				<?php bht_heading( 'reviews-title', __( 'What Lubbock Is Saying', 'bighams' ), __( 'Guest reviews', 'bighams' ) ); ?>
				<?php if ( $rating && $count ) : ?>
					<p class="reviews__summary"><?php echo bht_stars( round( (float) $rating ) ); // phpcs:ignore ?> <strong><?php echo esc_html( $rating ); ?></strong> <?php printf( esc_html__( 'from %s reviews', 'bighams' ), esc_html( number_format_i18n( (int) $count ) ) ); ?></p>
				<?php endif; ?>
			</div>
			<div class="scroller-nav" data-scroller-nav="reviews-track">
				<button type="button" data-dir="-1" aria-label="<?php esc_attr_e( 'Previous reviews', 'bighams' ); ?>"><?php echo bht_icon( 'arrow', 'flip' ); // phpcs:ignore ?></button>
				<button type="button" data-dir="1" aria-label="<?php esc_attr_e( 'Next reviews', 'bighams' ); ?>"><?php echo bht_icon( 'arrow' ); // phpcs:ignore ?></button>
			</div>
		</div>
		<ul class="scroller reviews__track" id="reviews-track" tabindex="0" aria-label="<?php esc_attr_e( 'Reviews', 'bighams' ); ?>">
			<?php foreach ( $reviews as $r ) : ?>
				<li class="review">
					<figure>
						<?php echo bht_stars( $r['rating'] ); // phpcs:ignore ?>
						<blockquote><p><?php echo esc_html( $r['text'] ); ?></p></blockquote>
						<figcaption>— <?php echo esc_html( $r['author'] ?: $r['title'] ); ?><span><?php echo esc_html( $r['source'] ); ?></span></figcaption>
					</figure>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php if ( bht_setting( 'google' ) ) : ?>
			<p class="reviews__cta"><a class="link-arrow" href="<?php echo esc_url( bht_setting( 'google' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Read more reviews on Google', 'bighams' ); ?> <?php echo bht_icon( 'arrow' ); // phpcs:ignore ?></a></p>
		<?php endif; ?>
	</div>
</section>
