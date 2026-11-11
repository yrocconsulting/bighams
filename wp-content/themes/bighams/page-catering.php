<?php
/**
 * Template Name: Catering
 *
 * Auto-applies to the page with slug "catering".
 */

defined( 'ABSPATH' ) || exit;
get_header();

$packages  = bht_packages();
$alacarte  = bht_menu( 'catering' );
$locations = bht_locations();
$from      = bht_catering_from_price();
$phone     = bht_setting( 'catering_phone', bht_setting( 'phone' ) );

$summary = $from
	? sprintf(
		/* translators: 1: price 2: min guests 3: phone */
		__( 'Bigham\'s Smokehouse caters authentic Texas BBQ across Lubbock for groups from %2$d to 500+ guests. Packages start at %1$s per person and include slow-smoked meats, scratch-made sides, bread, sauce and paper goods. Order online with 24 hours notice for pickup or delivery, or call %3$s for full-service catering.', 'bighams' ),
		bht_money( $from ),
		bht_catering_min_guests(),
		$phone
	)
	: sprintf( __( 'Bigham\'s Smokehouse caters authentic Texas BBQ across Lubbock for groups of every size. Order online with 24 hours notice, or call %s for full-service catering.', 'bighams' ), $phone );

$actions  = '<a class="btn btn--primary btn--lg" href="#inquiry">' . esc_html__( 'Get a quote', 'bighams' ) . '</a>';
$actions .= '<a class="btn btn--outline-light btn--lg" href="#order-catering">' . esc_html__( 'Order catering online', 'bighams' ) . '</a>';

while ( have_posts() ) :
	the_post();
	get_template_part(
		'template-parts/page-hero',
		null,
		array(
			'eyebrow'   => __( 'Lubbock BBQ catering', 'bighams' ),
			'summary'   => $summary,
			'actions'   => $actions,
			'image_mod' => 'bh_catering_image',
		)
	);
	?>

	<section class="section section--paper steps" aria-labelledby="steps-title">
		<div class="container">
			<div class="section-head section-head--center"><?php bht_heading( 'steps-title', __( 'Catering in 3 Easy Steps', 'bighams' ), __( 'How it works', 'bighams' ) ); ?></div>
			<ol class="steps__list">
				<li class="step"><span class="step__num">1</span><?php echo bht_icon( 'flame' ); // phpcs:ignore ?><h3><?php esc_html_e( 'Pick your package', 'bighams' ); ?></h3><p><?php esc_html_e( 'Choose a package or build an a la carte spread. Plan on about ½ lb of meat per guest.', 'bighams' ); ?></p></li>
				<li class="step"><span class="step__num">2</span><?php echo bht_icon( 'calendar' ); // phpcs:ignore ?><h3><?php esc_html_e( 'Order ahead', 'bighams' ); ?></h3><p><?php esc_html_e( 'Order online at least 24 hours ahead. Groups over 200 or a full buyout need 7 days notice.', 'bighams' ); ?></p></li>
				<li class="step"><span class="step__num">3</span><?php echo bht_icon( 'truck' ); // phpcs:ignore ?><h3><?php esc_html_e( 'Pickup, delivery or full service', 'bighams' ); ?></h3><p><?php esc_html_e( 'Grab it from your closest location, have it delivered (fee applies), or let our team cater and serve.', 'bighams' ); ?></p></li>
			</ol>
		</div>
	</section>

	<?php if ( $packages ) : ?>
		<section class="section packages" aria-labelledby="packages-title">
			<div class="container">
				<div class="section-head section-head--center">
					<?php bht_heading( 'packages-title', __( 'Catering Packages', 'bighams' ), __( 'Crowd favorites', 'bighams' ) ); ?>
					<p><?php esc_html_e( 'Every package comes with BBQ sauce, pickles, onions, peppers and paper goods.', 'bighams' ); ?></p>
				</div>
				<div class="pkg-grid">
					<?php foreach ( $packages as $pkg ) : ?>
						<div id="pkg-<?php echo esc_attr( $pkg['id'] ); ?>"><?php get_template_part( 'template-parts/package-card', null, array( 'pkg' => $pkg ) ); ?></div>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $locations ) : ?>
		<section class="section section--dark order-catering" id="order-catering" aria-labelledby="order-catering-title">
			<div class="container">
				<div class="section-head section-head--center">
					<?php bht_heading( 'order-catering-title', __( 'Order Catering Online', 'bighams' ), __( 'Choose the location nearest you', 'bighams' ) ); ?>
					<p><?php esc_html_e( 'Pricing, availability and checkout happen securely on Toast. 24 hours notice required.', 'bighams' ); ?></p>
				</div>
				<div class="order-catering__grid">
					<?php foreach ( $locations as $loc ) : ?>
						<div class="order-catering__card">
							<h3><?php echo esc_html( $loc['title'] ); ?></h3>
							<p><?php echo esc_html( $loc['full_address'] ); ?></p>
							<?php if ( $loc['catering_url'] ) : ?>
								<a class="btn btn--primary btn--block" href="<?php echo esc_url( $loc['catering_url'] ); ?>" target="_blank" rel="noopener"<?php echo bht_track( 'catering_order', $loc['id'] ); // phpcs:ignore ?>><?php esc_html_e( 'Order catering', 'bighams' ); ?><span class="screen-reader-text"> — <?php echo esc_html( $loc['title'] ); ?></span></a>
							<?php else : ?>
								<a class="btn btn--primary btn--block" href="tel:<?php echo esc_attr( bht_tel( $phone ) ); ?>"><?php esc_html_e( 'Call to order', 'bighams' ); ?></a>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $alacarte ) : ?>
		<section class="section section--paper alacarte" aria-labelledby="alacarte-title">
			<div class="container">
				<div class="section-head section-head--center">
					<?php bht_heading( 'alacarte-title', __( 'A La Carte Catering Menu', 'bighams' ), __( 'Build your own spread', 'bighams' ) ); ?>
				</div>
				<?php foreach ( $alacarte as $s ) : ?>
					<section class="menu-section" id="<?php echo esc_attr( $s['slug'] ); ?>" aria-labelledby="sec-<?php echo esc_attr( $s['slug'] ); ?>">
						<header class="menu-section__head">
							<h3 id="sec-<?php echo esc_attr( $s['slug'] ); ?>" class="menu-section__title"><?php echo esc_html( $s['name'] ); ?></h3>
							<?php if ( $s['note'] ) : ?><p class="menu-section__note"><?php echo esc_html( $s['note'] ); ?></p><?php endif; ?>
							<?php if ( $s['description'] ) : ?><p class="menu-section__desc"><?php echo esc_html( $s['description'] ); ?></p><?php endif; ?>
						</header>
						<div class="menu-grid menu-grid--compact">
							<?php foreach ( $s['items'] as $item ) : ?>
								<?php get_template_part( 'template-parts/menu-item', null, array( 'item' => $item ) ); ?>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

	<section class="section inquiry" aria-labelledby="inquiry-title">
		<div class="container inquiry__grid">
			<div class="inquiry__copy">
				<?php bht_heading( 'inquiry-title', __( 'Request a Catering Quote', 'bighams' ), __( 'Let\'s plan your event', 'bighams' ) ); ?>
				<p class="lead"><?php esc_html_e( 'Tell us a little about your event and our catering office will follow up within one business day with a menu and quote.', 'bighams' ); ?></p>
				<ul class="checklist">
					<li><?php echo bht_icon( 'check' ); // phpcs:ignore ?><span><?php esc_html_e( 'Corporate lunches & meetings', 'bighams' ); ?></span></li>
					<li><?php echo bht_icon( 'check' ); // phpcs:ignore ?><span><?php esc_html_e( 'Weddings & rehearsal dinners', 'bighams' ); ?></span></li>
					<li><?php echo bht_icon( 'check' ); // phpcs:ignore ?><span><?php esc_html_e( 'Birthdays, reunions & holiday parties', 'bighams' ); ?></span></li>
					<li><?php echo bht_icon( 'check' ); // phpcs:ignore ?><span><?php esc_html_e( 'Schools, churches, teams & tailgates', 'bighams' ); ?></span></li>
				</ul>
				<p class="inquiry__call"><?php esc_html_e( 'Prefer to talk?', 'bighams' ); ?> <a href="tel:<?php echo esc_attr( bht_tel( $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a></p>
			</div>
			<div class="inquiry__form card"><?php bht_inquiry_form( array( 'type' => 'catering', 'id' => 'inquiry' ) ); ?></div>
		</div>
	</section>

	<?php if ( get_the_content() ) : ?>
		<section class="section section--paper"><div class="container container--narrow entry-content"><?php the_content(); ?></div></section>
	<?php endif; ?>

	<?php get_template_part( 'template-parts/faq-list', null, array( 'topic' => 'catering', 'title' => __( 'Catering FAQ', 'bighams' ), 'class' => 'section--paper', 'open_first' => true ) ); ?>

	<?php
	get_template_part(
		'template-parts/cta-band',
		null,
		array(
			'title' => __( 'Your Event, Our Pit', 'bighams' ),
			'text'  => __( 'Order catering online in minutes or talk to our catering office.', 'bighams' ),
		)
	);
endwhile;

get_footer();
