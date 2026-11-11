<?php
/**
 * Footer: NAP for every location, hours, social, nav, mobile action bar, order modal.
 */

defined( 'ABSPATH' ) || exit;
$locations = bht_locations();
$phone     = bht_setting( 'phone' );
?>
</main>

<footer class="site-footer" aria-labelledby="footer-title">
	<h2 id="footer-title" class="screen-reader-text"><?php esc_html_e( 'Site footer', 'bighams' ); ?></h2>
	<div class="site-footer__top">
		<div class="container footer-grid">
			<div class="footer-brand">
				<?php bht_logo( 'footer' ); ?>
				<p class="footer-brand__desc">
					<?php
					printf(
						/* translators: 1: founding year 2: number of locations */
						esc_html__( 'Family-owned Texas barbecue in Lubbock since %1$s. Slow-smoked brisket, ribs, sausage and turkey at %2$s locations, plus catering for groups of every size.', 'bighams' ),
						esc_html( bht_setting( 'founded', '1978' ) ),
						esc_html( bht_location_count_word() )
					);
					?>
				</p>
				<p class="footer-brand__contact">
					<a href="tel:<?php echo esc_attr( bht_tel( $phone ) ); ?>"><?php echo bht_icon( 'phone' ); // phpcs:ignore ?> <?php echo esc_html( $phone ); ?></a><br>
					<a href="mailto:<?php echo esc_attr( bht_setting( 'email' ) ); ?>"><?php echo bht_icon( 'mail' ); // phpcs:ignore ?> <?php echo esc_html( bht_setting( 'email' ) ); ?></a>
				</p>
				<ul class="social">
					<?php foreach ( bht_socials() as $net => $url ) : ?>
						<li><a href="<?php echo esc_url( $url ); ?>" rel="noopener me" target="_blank"><?php echo bht_icon( $net ); // phpcs:ignore ?><span class="screen-reader-text"><?php echo esc_html( ucfirst( $net ) ); ?></span></a></li>
					<?php endforeach; ?>
				</ul>
			</div>

			<?php foreach ( $locations as $loc ) : ?>
				<div class="footer-loc">
					<h3 class="footer-loc__title"><a href="<?php echo esc_url( $loc['permalink'] ); ?>"><?php echo esc_html( $loc['title'] ); ?></a></h3>
					<address>
						<?php echo esc_html( bht_setting( 'business_name' ) ); ?><br>
						<?php echo esc_html( $loc['street'] ); ?><br>
						<?php echo esc_html( $loc['city'] . ', ' . $loc['state'] . ' ' . $loc['zip'] ); ?><br>
						<a href="tel:<?php echo esc_attr( $loc['phone_e164'] ); ?>"><?php echo esc_html( $loc['phone'] ); ?></a>
					</address>
					<dl class="hours hours--compact">
						<?php foreach ( $loc['hours_rows'] as $row ) : ?>
							<div><dt><?php echo esc_html( $row['days'] ); ?></dt><dd><?php echo esc_html( $row['value'] ); ?></dd></div>
						<?php endforeach; ?>
					</dl>
					<a class="footer-loc__dir" href="<?php echo esc_url( $loc['directions_url'] ); ?>" target="_blank" rel="noopener"<?php echo bht_track( 'directions', $loc['id'] ); // phpcs:ignore ?>><?php esc_html_e( 'Get directions', 'bighams' ); ?></a>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<div class="site-footer__bottom">
		<div class="container footer-bottom">
			<nav aria-label="<?php esc_attr_e( 'Footer', 'bighams' ); ?>">
				<?php
				if ( has_nav_menu( 'footer' ) ) {
					wp_nav_menu(
						array(
							'theme_location' => 'footer',
							'container'      => false,
							'menu_class'     => 'footer-nav',
							'depth'          => 1,
						)
					);
				} else {
					bht_footer_default_nav();
				}
				?>
			</nav>
			<div class="footer-bottom__meta">
				<?php if ( bht_setting( 'jobs_url' ) ) : ?>
					<a href="<?php echo esc_url( bht_setting( 'jobs_url' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Careers', 'bighams' ); ?></a>
				<?php endif; ?>
				<?php if ( bht_setting( 'gift_card_url' ) ) : ?>
					<a href="<?php echo esc_url( bht_setting( 'gift_card_url' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Gift Cards', 'bighams' ); ?></a>
				<?php endif; ?>
				<?php if ( get_privacy_policy_url() ) : ?>
					<a href="<?php echo esc_url( get_privacy_policy_url() ); ?>"><?php esc_html_e( 'Privacy', 'bighams' ); ?></a>
				<?php endif; ?>
				<span>&copy; <?php echo esc_html( gmdate( 'Y' ) . ' ' . bht_setting( 'business_name' ) ); ?></span>
				<span><?php esc_html_e( 'Website by', 'bighams' ); ?> <a href="https://yrocconsulting.com" rel="noopener" target="_blank">Yroc Consulting</a></span>
			</div>
		</div>
	</div>
</footer>

<nav class="action-bar" aria-label="<?php esc_attr_e( 'Quick actions', 'bighams' ); ?>">
	<button type="button" data-order-open aria-haspopup="dialog" aria-controls="order-modal"><?php echo bht_icon( 'bag' ); // phpcs:ignore ?><span><?php esc_html_e( 'Order', 'bighams' ); ?></span></button>
	<a href="<?php echo esc_url( home_url( '/catering/' ) ); ?>"><?php echo bht_icon( 'users' ); // phpcs:ignore ?><span><?php esc_html_e( 'Catering', 'bighams' ); ?></span></a>
	<a href="tel:<?php echo esc_attr( bht_tel( $phone ) ); ?>"><?php echo bht_icon( 'phone' ); // phpcs:ignore ?><span><?php esc_html_e( 'Call', 'bighams' ); ?></span></a>
	<a href="<?php echo esc_url( bht_locations_url() ); ?>"><?php echo bht_icon( 'pin' ); // phpcs:ignore ?><span><?php esc_html_e( 'Locations', 'bighams' ); ?></span></a>
</nav>

<?php get_template_part( 'template-parts/order-modal' ); ?>

<?php wp_footer(); ?>
</body>
</html>
