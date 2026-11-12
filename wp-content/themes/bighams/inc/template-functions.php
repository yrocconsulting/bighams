<?php
/**
 * Guarded wrappers around the Bigham's Core plugin API + template helpers.
 * The theme must never fatal if the plugin is inactive.
 */

defined( 'ABSPATH' ) || exit;

function bht_has_core() {
	return function_exists( 'bh_get_locations' );
}

function bht_setting( $key, $default = '' ) {
	if ( function_exists( 'bh_setting' ) ) {
		return bh_setting( $key, $default );
	}
	$fallback = array(
		'business_name' => "Bigham's Smokehouse",
		'tagline'       => 'Lubbock BBQ since 1978',
		'phone'         => '(806) 797-9931',
		'email'         => 'info@bighamsbbq.com',
		'founded'       => '1978',
	);
	return $fallback[ $key ] ?? $default;
}

function bht_locations() {
	return function_exists( 'bh_get_locations' ) ? (array) bh_get_locations() : array();
}

function bht_location( $post ) {
	return function_exists( 'bh_get_location' ) ? (array) bh_get_location( $post ) : array();
}

function bht_menu( $group = 'restaurant' ) {
	return function_exists( 'bh_get_menu' ) ? (array) bh_get_menu( $group ) : array();
}

function bht_featured_items( $limit = 8 ) {
	return function_exists( 'bh_get_featured_items' ) ? (array) bh_get_featured_items( $limit ) : array();
}

function bht_packages() {
	return function_exists( 'bh_get_packages' ) ? (array) bh_get_packages() : array();
}

function bht_faqs( $topic = '' ) {
	return function_exists( 'bh_get_faqs' ) ? (array) bh_get_faqs( $topic ) : array();
}

function bht_reviews( $limit = 8 ) {
	return function_exists( 'bh_get_reviews' ) ? (array) bh_get_reviews( $limit ) : array();
}

/**
 * Tracking attributes (already escaped by plugin).
 */
function bht_track( $event, $loc = 0 ) {
	return function_exists( 'bh_track_attrs' ) ? bh_track_attrs( $event, $loc ) : '';
}

function bht_money( $n ) {
	if ( function_exists( 'bh_money' ) ) {
		return bh_money( $n );
	}
	return ( '' === $n || null === $n ) ? '' : '$' . number_format( (float) $n, 2 );
}

function bht_tel( $phone ) {
	if ( function_exists( 'bh_phone_e164' ) ) {
		return bh_phone_e164( $phone );
	}
	$d = preg_replace( '/\D+/', '', (string) $phone );
	return $d ? '+' . ( 10 === strlen( $d ) ? '1' . $d : $d ) : '';
}

function bht_socials() {
	if ( function_exists( 'bh_social_links' ) ) {
		return bh_social_links();
	}
	return array(
		'facebook'  => 'https://www.facebook.com/104975899545443',
		'instagram' => 'https://www.instagram.com/bighamsbbq',
	);
}

/**
 * Inquiry form, or a call-us fallback.
 */
function bht_inquiry_form( $args = array() ) {
	if ( function_exists( 'bh_catering_form' ) ) {
		echo bh_catering_form( $args ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside plugin.
		return;
	}
	$phone = bht_setting( 'phone' );
	printf(
		'<p class="bh-form__notice">Call us at <a href="tel:%s">%s</a> or email <a href="mailto:%s">%s</a> to plan your event.</p>',
		esc_attr( bht_tel( $phone ) ),
		esc_html( $phone ),
		esc_attr( bht_setting( 'email' ) ),
		esc_html( bht_setting( 'email' ) )
	);
}

/**
 * Lowest catering price per person (for answer-first copy + CTA badges).
 */
function bht_catering_from_price() {
	$min = null;
	foreach ( bht_packages() as $p ) {
		if ( '' !== $p['price'] && ( null === $min || (float) $p['price'] < $min ) ) {
			$min = (float) $p['price'];
		}
	}
	return $min;
}

function bht_catering_min_guests() {
	$min = null;
	foreach ( bht_packages() as $p ) {
		if ( ! empty( $p['min_guests'] ) && ( null === $min || $p['min_guests'] < $min ) ) {
			$min = (int) $p['min_guests'];
		}
	}
	return $min ?: 10;
}

/**
 * Number of locations as a word ("three").
 */
function bht_location_count_word() {
	$n     = count( bht_locations() ) ?: 3;
	$words = array( 1 => 'one', 'two', 'three', 'four', 'five', 'six' );
	return $words[ $n ] ?? (string) $n;
}

/**
 * Image from Customizer (attachment ID) or placeholder div.
 *
 * @param string $mod   theme_mod key.
 * @param string $size  image size.
 * @param array  $attr  img attributes.
 * @param string $label placeholder label.
 */
function bht_mod_image( $mod, $size = 'bh-card', $attr = array(), $label = '' ) {
	$id = (int) get_theme_mod( $mod );
	if ( $id ) {
		$attr = wp_parse_args( $attr, array( 'loading' => 'lazy', 'decoding' => 'async' ) );
		echo wp_get_attachment_image( $id, $size, false, $attr );
		return;
	}
	printf(
		'<div class="bh-ph %s" role="img" aria-label="%s"><span>%s</span></div>',
		esc_attr( $attr['class'] ?? '' ),
		esc_attr( $label ?: bht_setting( 'business_name' ) ),
		esc_html( $label )
	);
}

/**
 * Post thumbnail or placeholder.
 */
function bht_thumb( $id, $size = 'bh-card', $label = '', $class = '' ) {
	if ( $id ) {
		echo wp_get_attachment_image( (int) $id, $size, false, array( 'loading' => 'lazy', 'decoding' => 'async', 'class' => $class ) );
		return;
	}
	printf( '<div class="bh-ph %s" role="img" aria-label="%s"><span>%s</span></div>', esc_attr( $class ), esc_attr( $label ), esc_html( $label ) );
}

/**
 * Logo: circle badge (uploaded Custom Logo) + Rye wordmark.
 * Style is set in Customizer → Site Identity → "Header logo style".
 */
function bht_logo( $context = 'header' ) {
	$style   = get_theme_mod( 'bh_logo_style', 'both' );
	$logo_id = (int) get_theme_mod( 'custom_logo' );
	if ( ! $logo_id ) {
		$style = 'wordmark';
	}
	if ( 'logo' === $style ) {
		the_custom_logo();
		return;
	}
	$badge = '';
	if ( 'both' === $style ) {
		$size  = 'footer' === $context ? 96 : 56;
		$badge = wp_get_attachment_image(
			$logo_id,
			array( $size, $size ),
			false,
			array(
				'class'    => 'wordmark__badge',
				'alt'      => '',
				'loading'  => 'header' === $context ? 'eager' : 'lazy',
				'decoding' => 'async',
			)
		);
	}
	printf(
		'<a class="wordmark wordmark--%1$s%2$s" href="%3$s" rel="home" aria-label="%4$s">%5$s<span class="wordmark__text"><span class="wordmark__name">Bigham\'s</span><span class="wordmark__sub">Smokehouse &middot; Est. %6$s</span></span></a>',
		esc_attr( $context ),
		$badge ? ' wordmark--badge' : '',
		esc_url( home_url( '/' ) ),
		esc_attr__( "Bigham's Smokehouse home", 'bighams' ),
		$badge, // phpcs:ignore WordPress.Security.EscapeOutput -- core image markup.
		esc_html( bht_setting( 'founded', '1978' ) )
	);
}

/**
 * Default primary nav when no menu assigned.
 */
function bht_default_nav() {
	$items = array(
		'menu'      => 'Menu',
		'locations' => 'Locations',
		'catering'  => 'Catering',
		'parties'   => 'Parties',
		'about'     => 'Our Story',
	);
	echo '<ul class="nav__list">';
	foreach ( $items as $slug => $label ) {
		$url = 'locations' === $slug && post_type_exists( 'bh_location' ) ? get_post_type_archive_link( 'bh_location' ) : home_url( '/' . $slug . '/' );
		$cur = ( is_page( $slug ) || ( 'locations' === $slug && ( is_post_type_archive( 'bh_location' ) || is_singular( 'bh_location' ) ) ) ) ? ' aria-current="page"' : '';
		printf( '<li><a href="%s"%s>%s</a></li>', esc_url( $url ), $cur, esc_html( $label ) ); // phpcs:ignore
	}
	echo '</ul>';
}

function bht_footer_default_nav() {
	$items = array(
		'menu'      => 'Menu',
		'locations' => 'Locations',
		'catering'  => 'Catering',
		'parties'   => 'Private Events',
		'about'     => 'Our Story',
		'faq'       => 'FAQ',
		'contact'   => 'Contact',
	);
	echo '<ul class="footer-nav">';
	foreach ( $items as $slug => $label ) {
		$url = 'locations' === $slug && post_type_exists( 'bh_location' ) ? get_post_type_archive_link( 'bh_location' ) : home_url( '/' . $slug . '/' );
		printf( '<li><a href="%s">%s</a></li>', esc_url( $url ), esc_html( $label ) );
	}
	echo '</ul>';
}

/**
 * URL to the locations archive.
 */
function bht_locations_url() {
	$link = post_type_exists( 'bh_location' ) ? get_post_type_archive_link( 'bh_location' ) : false;
	return $link ?: home_url( '/locations/' );
}

/**
 * Lightweight star rating markup.
 */
function bht_stars( $n = 5 ) {
	$n = max( 0, min( 5, (int) $n ) );
	return '<span class="stars" aria-label="' . esc_attr( $n ) . ' out of 5 stars">' . str_repeat( '★', $n ) . '<span class="stars__off" aria-hidden="true">' . str_repeat( '★', 5 - $n ) . '</span></span>';
}

/**
 * Google Maps embed URL (no API key).
 */
function bht_map_embed( $loc ) {
	$q = ! empty( $loc['lat'] ) && ! empty( $loc['lng'] )
		? $loc['lat'] . ',' . $loc['lng']
		: bht_setting( 'business_name' ) . ' ' . ( $loc['full_address'] ?? '' );
	return 'https://maps.google.com/maps?q=' . rawurlencode( $q ) . '&z=15&output=embed';
}

/**
 * Section heading helper: eyebrow + h2.
 */
function bht_heading( $id, $title, $eyebrow = '', $tag = 'h2', $class = '' ) {
	if ( $eyebrow ) {
		echo '<p class="eyebrow">' . esc_html( $eyebrow ) . '</p>';
	}
	printf( '<%1$s id="%2$s" class="section-title %3$s">%4$s</%1$s>', tag_escape( $tag ), esc_attr( $id ), esc_attr( $class ), esc_html( $title ) );
}

/**
 * Branded divider (inline SVG brand-iron line).
 */
function bht_divider( $class = '' ) {
	echo '<div class="brand-divider ' . esc_attr( $class ) . '" aria-hidden="true"><span></span>' . bht_icon( 'star' ) . '<span></span></div>'; // phpcs:ignore
}
