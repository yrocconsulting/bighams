<?php
/**
 * Site header: announcement bar, sticky header, nav, order button.
 */

defined( 'ABSPATH' ) || exit;
$announcement = bht_setting( 'announcement' );
$announce_url = bht_setting( 'announcement_url' );
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main"><?php esc_html_e( 'Skip to content', 'bighams' ); ?></a>

<?php if ( $announcement ) : ?>
	<div class="announce" role="region" aria-label="<?php esc_attr_e( 'Announcement', 'bighams' ); ?>">
		<div class="container announce__inner">
			<?php if ( $announce_url ) : ?>
				<a href="<?php echo esc_url( $announce_url ); ?>"><?php echo esc_html( $announcement ); ?> <?php echo bht_icon( 'arrow' ); // phpcs:ignore ?></a>
			<?php else : ?>
				<span><?php echo esc_html( $announcement ); ?></span>
			<?php endif; ?>
		</div>
	</div>
<?php endif; ?>

<header class="site-header" id="site-header">
	<div class="container site-header__inner">
		<div class="site-header__brand"><?php bht_logo( 'header' ); ?></div>

		<nav class="nav" id="primary-nav" aria-label="<?php esc_attr_e( 'Primary', 'bighams' ); ?>">
			<?php
			if ( has_nav_menu( 'primary' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => 'nav__list',
						'depth'          => 2,
					)
				);
			} else {
				bht_default_nav();
			}
			?>
			<div class="nav__mobile-extra">
				<a class="btn btn--ghost" href="tel:<?php echo esc_attr( bht_tel( bht_setting( 'phone' ) ) ); ?>"><?php echo bht_icon( 'phone' ); // phpcs:ignore ?> <?php echo esc_html( bht_setting( 'phone' ) ); ?></a>
				<?php if ( bht_setting( 'jobs_url' ) ) : ?>
					<a class="nav__jobs" href="<?php echo esc_url( bht_setting( 'jobs_url' ) ); ?>" rel="noopener" target="_blank"><?php esc_html_e( 'Now hiring — apply', 'bighams' ); ?></a>
				<?php endif; ?>
			</div>
		</nav>

		<div class="site-header__actions">
			<?php $bh_phone = bht_setting( 'phone', '(806) 797-9931' ); ?>
			<a class="site-header__cater" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $bh_phone ) ); ?>"><?php echo esc_html( $bh_phone ); ?></a>
			<button class="btn btn--primary" type="button" data-order-open aria-haspopup="dialog" aria-controls="order-modal">
				<?php echo bht_icon( 'bag' ); // phpcs:ignore ?><span><?php esc_html_e( 'Order Online', 'bighams' ); ?></span>
			</button>
			<button class="nav-toggle" type="button" aria-controls="primary-nav" aria-expanded="false" data-nav-toggle>
				<span class="nav-toggle__bars" aria-hidden="true"><span></span><span></span><span></span></span>
				<span class="screen-reader-text"><?php esc_html_e( 'Menu', 'bighams' ); ?></span>
			</button>
		</div>
	</div>
</header>

<main id="main" class="site-main" tabindex="-1">
