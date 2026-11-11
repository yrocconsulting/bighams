<?php
/**
 * Homepage.
 */

defined( 'ABSPATH' ) || exit;
get_header();

$locations = bht_locations();
$featured  = bht_featured_items( 10 );
$packages  = bht_packages();
$from      = bht_catering_from_price();
$founded   = (int) bht_setting( 'founded', '1978' );
$years     = (int) gmdate( 'Y' ) - $founded;
$video     = get_theme_mod( 'bh_hero_video' );
$hero_id   = (int) get_theme_mod( 'bh_hero_image' );
?>

<section class="hero" aria-labelledby="hero-title">
	<div class="hero__media">
		<?php if ( $hero_id ) : ?>
			<?php echo wp_get_attachment_image( $hero_id, 'bh-hero', false, array( 'class' => 'hero__img', 'loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '100vw', 'alt' => '' ) ); ?>
		<?php else : ?>
			<div class="hero__ph" aria-hidden="true"></div>
		<?php endif; ?>
		<?php if ( $video ) : ?>
			<video class="hero__video" muted playsinline loop preload="none" data-hero-video<?php echo $hero_id ? ' poster="' . esc_url( wp_get_attachment_image_url( $hero_id, 'bh-hero' ) ) . '"' : ''; ?> aria-hidden="true">
				<source src="<?php echo esc_url( $video ); ?>" type="video/mp4">
			</video>
		<?php endif; ?>
		<div class="hero__smoke" aria-hidden="true"></div>
	</div>
	<div class="container hero__content">
		<p class="eyebrow eyebrow--light"><?php echo esc_html( bht_mod( 'bh_hero_eyebrow' ) ); ?></p>
		<h1 id="hero-title" class="hero__title"><?php echo esc_html( bht_mod( 'bh_hero_headline' ) ); ?></h1>
		<p class="hero__sub"><?php echo esc_html( bht_mod( 'bh_hero_subhead' ) ); ?></p>
		<div class="hero__actions">
			<button class="btn btn--primary btn--lg" type="button" data-order-open aria-haspopup="dialog" aria-controls="order-modal"><?php echo bht_icon( 'bag' ); // phpcs:ignore ?> <?php esc_html_e( 'Order Online', 'bighams' ); ?></button>
			<a class="btn btn--outline-light btn--lg" href="<?php echo esc_url( home_url( '/catering/' ) ); ?>"><?php echo bht_icon( 'users' ); // phpcs:ignore ?> <?php esc_html_e( 'Cater Your Event', 'bighams' ); ?></a>
		</div>
		<?php if ( $locations ) : ?>
			<ul class="hero__locs" aria-label="<?php esc_attr_e( 'Locations open today', 'bighams' ); ?>">
				<?php foreach ( $locations as $loc ) : ?>
					<li><a href="<?php echo esc_url( $loc['permalink'] ); ?>"><span class="status__dot <?php echo $loc['status']['open'] ? 'is-open' : ''; ?>" aria-hidden="true"></span><?php echo esc_html( $loc['short_name'] ); ?> <span class="hero__loc-status"><?php echo esc_html( $loc['status']['label'] ); ?></span></a></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
	<div class="torn torn--bottom" aria-hidden="true"></div>
</section>

<section class="section section--paper story" aria-labelledby="story-title">
	<div class="container story__grid">
		<div class="story__copy">
			<?php bht_heading( 'story-title', __( 'A Lil\' Bit About the Smokehouse', 'bighams' ), sprintf( __( 'Family-owned since %d', 'bighams' ), $founded ) ); ?>
			<p class="lead">
				<?php
				printf(
					/* translators: 1: year 2: founder 3: owners 4: location count */
					esc_html__( 'Bigham\'s Smokehouse first opened in %1$d when %2$s fired up his pit in Lubbock. Today it\'s still locally owned and operated by his wife and son, %3$s — with %4$s Lubbock locations for dine-in, carry-out, drive-thru, delivery and catering.', 'bighams' ),
					(int) $founded,
					esc_html( bht_setting( 'founder', 'Don Bigham' ) ),
					esc_html( str_replace( ', ', ' and ', bht_setting( 'owners', 'Joyce and Nick Bigham' ) ) ),
					esc_html( bht_location_count_word() )
				);
				?>
			</p>
			<p><?php esc_html_e( 'Everything is smoked low and slow, sliced by hand and served with scratch-made sides, warm Texas toast and a whole lot of West Texas hospitality.', 'bighams' ); ?></p>
			<dl class="stats">
				<div><dt><?php esc_html_e( 'Smoking since', 'bighams' ); ?></dt><dd><?php echo esc_html( $founded ); ?></dd></div>
				<div><dt><?php esc_html_e( 'Years in Lubbock', 'bighams' ); ?></dt><dd><?php echo esc_html( $years ); ?>+</dd></div>
				<div><dt><?php esc_html_e( 'Lubbock locations', 'bighams' ); ?></dt><dd><?php echo esc_html( count( $locations ) ?: 3 ); ?></dd></div>
				<div><dt><?php esc_html_e( 'Catering guests', 'bighams' ); ?></dt><dd>10–500+</dd></div>
			</dl>
			<a class="link-arrow" href="<?php echo esc_url( home_url( '/about/' ) ); ?>"><?php esc_html_e( 'Read our story', 'bighams' ); ?> <?php echo bht_icon( 'arrow' ); // phpcs:ignore ?></a>
		</div>
		<div class="story__media">
			<?php bht_mod_image( 'bh_about_image', 'bh-card', array( 'class' => 'story__img' ), __( 'Since 1978', 'bighams' ) ); ?>
			<div class="story__stamp" aria-hidden="true"><span>Est.</span><strong><?php echo esc_html( $founded ); ?></strong><span>Lubbock, TX</span></div>
		</div>
	</div>
</section>

<?php if ( $featured ) : ?>
<section class="section section--dark featured" aria-labelledby="featured-title">
	<div class="container">
		<div class="section-head section-head--split">
			<div><?php bht_heading( 'featured-title', __( 'Straight Off the Pit', 'bighams' ), __( 'Our menu', 'bighams' ) ); ?></div>
			<div class="scroller-nav" data-scroller-nav="featured-track">
				<button type="button" data-dir="-1" aria-label="<?php esc_attr_e( 'Previous items', 'bighams' ); ?>"><?php echo bht_icon( 'arrow', 'flip' ); // phpcs:ignore ?></button>
				<button type="button" data-dir="1" aria-label="<?php esc_attr_e( 'Next items', 'bighams' ); ?>"><?php echo bht_icon( 'arrow' ); // phpcs:ignore ?></button>
			</div>
		</div>
		<ul class="scroller featured__track" id="featured-track" tabindex="0" aria-label="<?php esc_attr_e( 'Featured menu items', 'bighams' ); ?>">
			<?php foreach ( $featured as $item ) : ?>
				<li><?php get_template_part( 'template-parts/menu-item', null, array( 'item' => $item, 'style' => 'card' ) ); ?></li>
			<?php endforeach; ?>
		</ul>
		<div class="featured__actions">
			<a class="btn btn--light" href="<?php echo esc_url( home_url( '/menu/' ) ); ?>"><?php esc_html_e( 'See the full menu', 'bighams' ); ?></a>
			<button class="btn btn--outline-light" type="button" data-order-open aria-haspopup="dialog" aria-controls="order-modal"><?php esc_html_e( 'Order for pickup', 'bighams' ); ?></button>
		</div>
	</div>
</section>
<?php endif; ?>

<section class="section catering-band" aria-labelledby="catering-band-title">
	<div class="catering-band__bg" aria-hidden="true"><?php bht_mod_image( 'bh_catering_image', 'bh-wide', array( 'class' => 'catering-band__img', 'alt' => '' ) ); ?></div>
	<div class="container catering-band__inner">
		<div class="catering-band__copy">
			<?php bht_heading( 'catering-band-title', __( 'Bringing the Smokehouse to Your Event', 'bighams' ), __( 'Catering', 'bighams' ) ); ?>
			<p class="lead">
				<?php
				if ( $from ) {
					printf(
						/* translators: 1: price 2: min guests */
						esc_html__( 'Office lunches, weddings, tailgates and holiday parties — Texas BBQ packages start at %1$s per person for groups of %2$d or more. Pickup, delivery or full-service.', 'bighams' ),
						esc_html( bht_money( $from ) ),
						(int) bht_catering_min_guests()
					);
				} else {
					esc_html_e( 'Office lunches, weddings, tailgates and holiday parties — Texas BBQ packages for groups of any size. Pickup, delivery or full-service.', 'bighams' );
				}
				?>
			</p>
			<ul class="checklist checklist--light">
				<li><?php echo bht_icon( 'check' ); // phpcs:ignore ?><span><?php esc_html_e( 'Order online with just 24 hours notice', 'bighams' ); ?></span></li>
				<li><?php echo bht_icon( 'check' ); // phpcs:ignore ?><span><?php esc_html_e( 'Pickup, delivery, or catered & served', 'bighams' ); ?></span></li>
				<li><?php echo bht_icon( 'check' ); // phpcs:ignore ?><span><?php esc_html_e( 'Custom menus for 200+ guests', 'bighams' ); ?></span></li>
			</ul>
			<div class="btn-row">
				<a class="btn btn--primary btn--lg" href="<?php echo esc_url( home_url( '/catering/#inquiry' ) ); ?>"><?php esc_html_e( 'Get a catering quote', 'bighams' ); ?></a>
				<a class="btn btn--outline-light btn--lg" href="<?php echo esc_url( home_url( '/catering/' ) ); ?>"><?php esc_html_e( 'View packages', 'bighams' ); ?></a>
			</div>
		</div>
		<?php if ( $packages ) : ?>
			<div class="catering-band__pkgs">
				<?php foreach ( array_slice( $packages, 0, 3 ) as $pkg ) : ?>
					<a class="mini-pkg" href="<?php echo esc_url( home_url( '/catering/#pkg-' . $pkg['id'] ) ); ?>">
						<span class="mini-pkg__title"><?php echo esc_html( $pkg['title'] ); ?></span>
						<?php if ( '' !== $pkg['price'] ) : ?>
							<span class="mini-pkg__price"><?php echo esc_html( bht_money( $pkg['price'] ) ); ?> <small><?php echo esc_html( $pkg['unit'] ); ?></small></span>
						<?php endif; ?>
						<?php if ( $pkg['minimum'] ) : ?>
							<span class="mini-pkg__min"><?php echo esc_html( $pkg['minimum'] ); ?></span>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>

<?php if ( $locations ) : ?>
<section class="section section--paper locations" aria-labelledby="locations-title">
	<div class="container">
		<div class="section-head section-head--center">
			<?php bht_heading( 'locations-title', sprintf( __( '%s Lubbock Locations', 'bighams' ), ucfirst( bht_location_count_word() ) ), __( 'Find your Bigham\'s', 'bighams' ) ); ?>
			<p><?php esc_html_e( 'Dine in, swing through the drive-thru, or order ahead for pickup and delivery.', 'bighams' ); ?></p>
		</div>
		<div class="loc-grid">
			<?php foreach ( $locations as $loc ) : ?>
				<?php get_template_part( 'template-parts/location-card', null, array( 'loc' => $loc ) ); ?>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<section class="section section--dark split parties-band" aria-labelledby="parties-title">
	<div class="container split__grid">
		<div class="split__media"><?php bht_mod_image( 'bh_parties_image', 'bh-card', array( 'class' => 'split__img' ), __( 'Private dining', 'bighams' ) ); ?></div>
		<div class="split__copy">
			<?php bht_heading( 'parties-title', __( 'Private Dining & Parties', 'bighams' ), __( 'Groups & events', 'bighams' ) ); ?>
			<p class="lead"><?php esc_html_e( 'Birthdays, rehearsal dinners, team banquets and holiday parties. Reserve our private dining room at 82nd Street for up to 20 guests, or rent out an entire dining room.', 'bighams' ); ?></p>
			<div class="btn-row">
				<a class="btn btn--primary" href="<?php echo esc_url( home_url( '/parties/#inquiry' ) ); ?>"><?php esc_html_e( 'Book your party', 'bighams' ); ?></a>
				<a class="btn btn--outline-light" href="<?php echo esc_url( home_url( '/parties/' ) ); ?>"><?php esc_html_e( 'Party details', 'bighams' ); ?></a>
			</div>
		</div>
	</div>
</section>

<?php get_template_part( 'template-parts/reviews' ); ?>

<?php
get_template_part(
	'template-parts/faq-list',
	null,
	array(
		'topic' => 'general',
		'title' => __( 'Questions About Bigham\'s', 'bighams' ),
		'class' => 'section--paper',
	)
);
?>

<?php
$home_content = is_page() ? trim( (string) get_post_field( 'post_content', get_queried_object_id() ) ) : '';
if ( $home_content ) :
	?>
	<section class="section entry-content-wrap"><div class="container container--narrow entry-content"><?php echo apply_filters( 'the_content', $home_content ); // phpcs:ignore ?></div></section>
<?php endif; ?>

<?php get_template_part( 'template-parts/cta-band' ); ?>

<?php
get_footer();
