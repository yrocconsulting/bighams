<?php
/**
 * Shared helpers + settings access.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Default business settings. Editable in Bigham's → Settings.
 */
function bh_default_settings() {
	return array(
		'business_name'    => "Bigham's Smokehouse",
		'legal_name'       => "Bigham's Smokehouse",
		'tagline'          => 'Lubbock BBQ since 1978',
		'description'      => "Bigham's Smokehouse is a family-owned Texas barbecue restaurant in Lubbock, TX, first opened in 1978 by Don Bigham and now run by his wife and son, Joyce and Nick Bigham. Three Lubbock locations serve slow-smoked brisket, ribs, sausage, turkey and pulled pork for dine-in, carry-out, drive-thru and delivery, plus full-service catering for events of every size.",
		'founded'          => '1978',
		'founder'          => 'Don Bigham',
		'owners'           => 'Joyce Bigham, Nick Bigham',
		'phone'            => '(806) 797-9931',
		'email'            => 'info@bighamsbbq.com',
		'catering_phone'   => '(806) 797-9931',
		'catering_email'   => 'info@bighamsbbq.com',
		'catering_notify'  => '',
		'city'             => 'Lubbock',
		'region'           => 'TX',
		'price_range'      => '$$',
		'cuisines'         => 'Barbecue, Texas BBQ, Southern, American',
		'facebook'         => 'https://www.facebook.com/104975899545443',
		'instagram'        => 'https://www.instagram.com/bighamsbbq',
		'google'           => '',
		'yelp'             => '',
		'tiktok'           => '',
		'jobs_url'         => 'https://bighams-smokehouse.r365hire.com/account',
		'catering_order_url' => 'https://www.toasttab.com/catering/locations/8a09b080-324a-4a97-b7d3-14270d2c8f55',
		'gift_card_url'    => '',
		'rating_value'     => '',
		'rating_count'     => '',
		'announcement'     => '',
		'announcement_url' => '',
		'lead_webhook_url' => '',
		'production_domain' => 'bighamsbbq.com',
		'ga4_id'           => '',
		// Toast.
		'toast_env'           => 'production',
		'toast_client_id'     => '',
		'toast_client_secret' => '',
		'toast_sync_menu'     => '0',
	);
}

/**
 * Read a setting.
 *
 * @param string $key     Key.
 * @param mixed  $default Fallback.
 * @return mixed
 */
function bh_setting( $key, $default = '' ) {
	static $cache = null;
	if ( null === $cache ) {
		$cache = wp_parse_args( (array) get_option( 'bighams_settings', array() ), bh_default_settings() );
	}
	if ( isset( $cache[ $key ] ) && '' !== $cache[ $key ] ) {
		return $cache[ $key ];
	}
	return $default;
}

/**
 * Phone to E.164 (US).
 */
function bh_phone_e164( $phone ) {
	$digits = preg_replace( '/\D+/', '', (string) $phone );
	if ( 10 === strlen( $digits ) ) {
		$digits = '1' . $digits;
	}
	return $digits ? '+' . $digits : '';
}

/**
 * Social profile URLs (for sameAs + footer).
 */
function bh_social_links() {
	$out = array();
	foreach ( array( 'facebook', 'instagram', 'google', 'yelp', 'tiktok' ) as $k ) {
		$url = bh_setting( $k );
		if ( $url ) {
			$out[ $k ] = esc_url_raw( $url );
		}
	}
	return $out;
}

/**
 * Parse "Label | Price | Note" lines into price rows.
 *
 * @param string $raw Textarea value.
 * @return array<int,array{label:string,price:string,note:string}>
 */
function bh_parse_price_lines( $raw ) {
	$rows = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $raw ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		$parts = array_map( 'trim', explode( '|', $line ) );
		if ( 1 === count( $parts ) ) {
			// "12.95" alone, or "Single 2.49".
			if ( preg_match( '/^(.*?)\s*\$?(\d+(?:\.\d{1,2})?)$/', $parts[0], $m ) ) {
				$rows[] = array( 'label' => trim( $m[1] ), 'price' => $m[2], 'note' => '' );
			} else {
				$rows[] = array( 'label' => $parts[0], 'price' => '', 'note' => '' );
			}
			continue;
		}
		$rows[] = array(
			'label' => $parts[0],
			'price' => ltrim( $parts[1], '$' ),
			'note'  => $parts[2] ?? '',
		);
	}
	return $rows;
}

/**
 * Format a price for display.
 */
function bh_money( $price ) {
	if ( '' === $price || null === $price ) {
		return '';
	}
	return '$' . number_format( (float) $price, 2 );
}

/**
 * Split a newline list.
 */
function bh_lines( $raw ) {
	return array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $raw ) ) ) );
}

/**
 * Site timezone DateTime.
 */
function bh_now() {
	return new DateTimeImmutable( 'now', wp_timezone() );
}

/**
 * Plain-text title (no texturized entities) for data, schema and llms.txt.
 * Templates must still escape on output.
 */
function bh_title( $post ) {
	return html_entity_decode( wp_strip_all_tags( get_the_title( $post ) ), ENT_QUOTES, 'UTF-8' );
}
