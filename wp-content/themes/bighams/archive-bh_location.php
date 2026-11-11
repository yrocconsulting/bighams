<?php
/**
 * Locations archive (/locations/).
 */

defined( 'ABSPATH' ) || exit;
get_header();

$locations = bht_locations();
$names     = wp_list_pluck( $locations, 'short_name' );
$summary   = $locations
	? sprintf(
		/* translators: 1: count word 2: list of names */
		__( 'Bigham\'s Smokehouse has %1$s barbecue restaurants in Lubbock, Texas: %2$s. Every location offers dine-in, carry-out and online ordering for pickup, plus catering.', 'bighams' ),
		bht_location_count_word(),
		wp_sprintf( '%l', $names )
	)
	: '';

get_template_part(
	'template-parts/page-hero',
	null,
	array(
		'title'   => __( 'Lubbock BBQ Locations', 'bighams' ),
		'eyebrow' => __( 'Find your Bigham\'s', 'bighams' ),
		'summary' => $summary,
		'actions' => '<button class="btn btn--primary btn--lg" type="button" data-order-open aria-haspopup="dialog" aria-controls="order-modal">' . bht_icon( 'bag' ) . ' ' . esc_html__( 'Order Online', 'bighams' ) . '</button>',
	)
);
?>
<section class="section section--paper" aria-label="<?php esc_attr_e( 'All locations', 'bighams' ); ?>">
	<div class="container">
		<div class="loc-list">
			<?php foreach ( $locations as $loc ) : ?>
				<article class="loc-row card">
					<div class="loc-row__map">
						<iframe title="<?php echo esc_attr( sprintf( __( 'Map of %s', 'bighams' ), $loc['title'] ) ); ?>" src="<?php echo esc_url( bht_map_embed( $loc ) ); ?>" width="600" height="400" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
					</div>
					<div class="loc-row__body">
						<h2 class="loc-row__title"><a href="<?php echo esc_url( $loc['permalink'] ); ?>"><?php echo esc_html( $loc['title'] ); ?></a></h2>
						<p class="status <?php echo $loc['status']['open'] ? 'status--open' : 'status--closed'; ?>"><span class="status__dot" aria-hidden="true"></span><?php echo esc_html( $loc['status']['label'] ); ?></p>
						<address><?php echo esc_html( $loc['full_address'] ); ?><?php if ( $loc['cross_street'] ) : ?><br><small><?php echo esc_html( $loc['cross_street'] ); ?></small><?php endif; ?></address>
						<p><a href="tel:<?php echo esc_attr( $loc['phone_e164'] ); ?>"><?php echo esc_html( $loc['phone'] ); ?></a></p>
						<dl class="hours">
							<?php foreach ( $loc['hours_rows'] as $row ) : ?>
								<div><dt><?php echo esc_html( $row['days'] ); ?></dt><dd><?php echo esc_html( $row['value'] ); ?></dd></div>
							<?php endforeach; ?>
						</dl>
						<?php if ( $loc['features'] ) : ?>
							<ul class="tags"><?php foreach ( $loc['features'] as $f ) : ?><li><?php echo esc_html( $f ); ?></li><?php endforeach; ?></ul>
						<?php endif; ?>
						<div class="btn-row">
							<?php if ( $loc['order_url'] ) : ?>
								<a class="btn btn--primary" href="<?php echo esc_url( $loc['order_url'] ); ?>" target="_blank" rel="noopener"<?php echo bht_track( 'order', $loc['id'] ); // phpcs:ignore ?>><?php esc_html_e( 'Order pickup', 'bighams' ); ?><span class="screen-reader-text"> — <?php echo esc_html( $loc['title'] ); ?></span></a>
							<?php endif; ?>
							<a class="btn btn--outline" href="<?php echo esc_url( $loc['directions_url'] ); ?>" target="_blank" rel="noopener"<?php echo bht_track( 'directions', $loc['id'] ); // phpcs:ignore ?>><?php esc_html_e( 'Directions', 'bighams' ); ?></a>
							<a class="link-arrow" href="<?php echo esc_url( $loc['permalink'] ); ?>"><?php esc_html_e( 'Location details', 'bighams' ); ?> <?php echo bht_icon( 'arrow' ); // phpcs:ignore ?></a>
						</div>
					</div>
				</article>
			<?php endforeach; ?>
			<?php if ( ! $locations ) : ?>
				<p><?php esc_html_e( 'Location details coming soon.', 'bighams' ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</section>

<?php get_template_part( 'template-parts/faq-list', null, array( 'topic' => 'locations', 'title' => __( 'Location & Hours FAQ', 'bighams' ) ) ); ?>
<?php get_template_part( 'template-parts/cta-band' ); ?>
<?php
get_footer();
