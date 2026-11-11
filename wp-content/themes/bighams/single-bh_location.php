<?php
/**
 * Single location (/locations/{slug}/).
 */

defined( 'ABSPATH' ) || exit;
get_header();

while ( have_posts() ) :
	the_post();
	$loc = bht_location( get_the_ID() );
	if ( ! $loc ) {
		// Plugin inactive: plain page.
		echo '<div class="container section"><h1>' . esc_html( get_the_title() ) . '</h1>';
		the_content();
		echo '</div>';
		continue;
	}
	$name    = bht_setting( 'business_name' );
	$near_tt = false !== stripos( $loc['title'] . ' ' . $loc['street'] . ' ' . $loc['cross_street'], '19th' );
	$summary = sprintf(
		/* translators: 1: business 2: location 3: address 4: phone */
		__( '%1$s %2$s is a family-owned Texas barbecue restaurant at %3$s. Call %4$s, order online for pickup, or stop in for brisket, ribs and sausage smoked fresh daily.', 'bighams' ),
		$name,
		$loc['short_name'],
		$loc['full_address'],
		$loc['phone']
	);

	$actions = '';
	if ( $loc['order_url'] ) {
		$actions .= '<a class="btn btn--primary btn--lg" href="' . esc_url( $loc['order_url'] ) . '" target="_blank" rel="noopener"' . bht_track( 'order', $loc['id'] ) . '>' . bht_icon( 'bag' ) . ' ' . esc_html__( 'Order pickup', 'bighams' ) . '</a>';
	}
	$actions .= '<a class="btn btn--outline-light btn--lg" href="tel:' . esc_attr( $loc['phone_e164'] ) . '">' . bht_icon( 'phone' ) . ' ' . esc_html( $loc['phone'] ) . '</a>';

	get_template_part(
		'template-parts/page-hero',
		null,
		array(
			'title'    => sprintf( __( '%1$s — %2$s', 'bighams' ), $name, $loc['short_name'] ),
			'eyebrow'  => sprintf( __( 'BBQ in %1$s, %2$s', 'bighams' ), $loc['city'], $loc['state'] ),
			'summary'  => $summary,
			'actions'  => $actions,
			'image_id' => $loc['image_id'],
		)
	);
	?>

	<section class="section section--paper" aria-labelledby="visit-title">
		<div class="container loc-detail">
			<div class="loc-detail__info">
				<h2 id="visit-title" class="section-title"><?php esc_html_e( 'Visit Us', 'bighams' ); ?></h2>
				<p class="status <?php echo $loc['status']['open'] ? 'status--open' : 'status--closed'; ?>"><span class="status__dot" aria-hidden="true"></span><?php echo esc_html( $loc['status']['label'] ); ?></p>
				<address class="loc-detail__addr">
					<strong><?php echo esc_html( $name . ' — ' . $loc['short_name'] ); ?></strong><br>
					<?php echo esc_html( $loc['street'] ); ?><br>
					<?php echo esc_html( $loc['city'] . ', ' . $loc['state'] . ' ' . $loc['zip'] ); ?>
					<?php if ( $loc['cross_street'] ) : ?><br><small><?php echo esc_html( $loc['cross_street'] ); ?></small><?php endif; ?>
				</address>
				<p><a href="tel:<?php echo esc_attr( $loc['phone_e164'] ); ?>"><?php echo esc_html( $loc['phone'] ); ?></a></p>

				<h3><?php esc_html_e( 'Hours', 'bighams' ); ?></h3>
				<table class="hours-table">
					<caption class="screen-reader-text"><?php printf( esc_html__( 'Opening hours for %s', 'bighams' ), esc_html( $loc['title'] ) ); ?></caption>
					<tbody>
						<?php foreach ( $loc['hours_rows'] as $row ) : ?>
							<tr><th scope="row"><?php echo esc_html( $row['days'] ); ?></th><td><?php echo esc_html( $row['value'] ); ?></td></tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php if ( $loc['hours_note'] ) : ?>
					<p class="loc-detail__note"><?php echo esc_html( $loc['hours_note'] ); ?></p>
				<?php endif; ?>
				<?php if ( $loc['happy_hour'] ) : ?>
					<p class="loc-detail__happy"><?php echo bht_icon( 'clock' ); // phpcs:ignore ?> <strong><?php esc_html_e( 'Happy Hour:', 'bighams' ); ?></strong> <?php echo esc_html( $loc['happy_hour'] ); ?></p>
				<?php endif; ?>

				<?php if ( $loc['features'] ) : ?>
					<h3><?php esc_html_e( 'Amenities', 'bighams' ); ?></h3>
					<ul class="checklist">
						<?php foreach ( $loc['features'] as $f ) : ?>
							<li><?php echo bht_icon( 'check' ); // phpcs:ignore ?><span><?php echo esc_html( $f ); ?></span></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<div class="btn-row">
					<a class="btn btn--outline" href="<?php echo esc_url( $loc['directions_url'] ); ?>" target="_blank" rel="noopener"<?php echo bht_track( 'directions', $loc['id'] ); // phpcs:ignore ?>><?php echo bht_icon( 'directions' ); // phpcs:ignore ?> <?php esc_html_e( 'Get directions', 'bighams' ); ?></a>
					<?php if ( $loc['catering_url'] ) : ?>
						<a class="btn btn--outline" href="<?php echo esc_url( $loc['catering_url'] ); ?>" target="_blank" rel="noopener"<?php echo bht_track( 'catering_order', $loc['id'] ); // phpcs:ignore ?>><?php esc_html_e( 'Order catering', 'bighams' ); ?></a>
					<?php endif; ?>
				</div>
				<?php if ( $loc['ubereats_url'] || $loc['doordash_url'] ) : ?>
					<p class="loc-detail__delivery"><?php esc_html_e( 'Delivery:', 'bighams' ); ?>
						<?php if ( $loc['ubereats_url'] ) : ?><a href="<?php echo esc_url( $loc['ubereats_url'] ); ?>" target="_blank" rel="noopener"<?php echo bht_track( 'delivery', $loc['id'] ); // phpcs:ignore ?>>Uber Eats</a><?php endif; ?>
						<?php if ( $loc['ubereats_url'] && $loc['doordash_url'] ) : ?> · <?php endif; ?>
						<?php if ( $loc['doordash_url'] ) : ?><a href="<?php echo esc_url( $loc['doordash_url'] ); ?>" target="_blank" rel="noopener"<?php echo bht_track( 'delivery', $loc['id'] ); // phpcs:ignore ?>>DoorDash</a><?php endif; ?>
					</p>
				<?php endif; ?>
			</div>
			<div class="loc-detail__map">
				<iframe title="<?php echo esc_attr( sprintf( __( 'Map of %s', 'bighams' ), $loc['title'] ) ); ?>" src="<?php echo esc_url( bht_map_embed( $loc ) ); ?>" width="800" height="600" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
			</div>
		</div>
	</section>

	<section class="section" aria-labelledby="about-loc-title">
		<div class="container container--narrow">
			<h2 id="about-loc-title" class="section-title"><?php printf( esc_html__( 'About Our %s Location', 'bighams' ), esc_html( $loc['short_name'] ) ); ?></h2>
			<?php if ( get_the_content() ) : ?>
				<div class="entry-content"><?php the_content(); ?></div>
			<?php else : ?>
				<div class="entry-content">
					<p>
						<?php
						printf(
							/* translators: 1: name 2: short 3: city */
							esc_html__( 'Our %2$s smokehouse serves the same slow-smoked brisket, St. Louis-style ribs, sausage, turkey and pulled pork %1$s has been known for in %3$s since 1978 — with scratch-made sides, cobblers and banana pudding. Dine in, carry out, or order ahead online for pickup.', 'bighams' ),
							esc_html( $name ),
							esc_html( $loc['short_name'] ),
							esc_html( $loc['city'] )
						);
						?>
					</p>
					<?php if ( $near_tt ) : ?>
						<p><?php esc_html_e( 'Located on 19th Street a short drive west of the Texas Tech campus, it\'s an easy stop for students, families and game-day crowds — and a convenient pickup spot for team and department catering.', 'bighams' ); ?></p>
					<?php endif; ?>
					<p><?php printf( wp_kses( __( 'Planning an event nearby? <a href="%s">See our catering packages</a>.', 'bighams' ), array( 'a' => array( 'href' => array() ) ) ), esc_url( home_url( '/catering/' ) ) ); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<?php
	$others = array_filter( bht_locations(), fn( $l ) => (int) $l['id'] !== (int) $loc['id'] );
	if ( $others ) :
		?>
		<section class="section section--paper" aria-labelledby="other-locs-title">
			<div class="container">
				<h2 id="other-locs-title" class="section-title"><?php esc_html_e( 'Other Bigham\'s Locations', 'bighams' ); ?></h2>
				<div class="loc-grid loc-grid--2">
					<?php foreach ( $others as $o ) : ?>
						<?php get_template_part( 'template-parts/location-card', null, array( 'loc' => $o ) ); ?>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php get_template_part( 'template-parts/faq-list', null, array( 'topic' => 'locations', 'title' => __( 'Location & Hours FAQ', 'bighams' ) ) ); ?>
	<?php get_template_part( 'template-parts/cta-band' ); ?>
	<?php
endwhile;

get_footer();
