<?php
/**
 * Data access layer used by the theme, schema, llms.txt and dashboard.
 * Every public function returns plain arrays so templates stay simple.
 */

defined( 'ABSPATH' ) || exit;

/**
 * All published locations.
 *
 * @return array<int,array>
 */
function bh_get_locations() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$posts = get_posts(
		array(
			'post_type'      => 'bh_location',
			'posts_per_page' => 20,
			'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
			'post_status'    => 'publish',
		)
	);
	$cache = array_map( 'bh_get_location', $posts );
	return $cache;
}

/**
 * A single location as an array.
 *
 * @param int|WP_Post $post Post.
 */
function bh_get_location( $post ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return array();
	}
	$m = function ( $k ) use ( $post ) {
		return (string) get_post_meta( $post->ID, '_bh_' . $k, true );
	};
	$hours  = get_post_meta( $post->ID, '_bh_hours', true );
	$hours  = is_array( $hours ) ? $hours : array();
	$street = $m( 'street' );
	$city   = $m( 'city' ) ?: 'Lubbock';
	$state  = $m( 'state' ) ?: 'TX';
	$zip    = $m( 'zip' );
	$full   = trim( sprintf( '%s, %s, %s %s', $street, $city, $state, $zip ), ', ' );
	$phone  = $m( 'phone' ) ?: bh_setting( 'phone' );

	return array(
		'id'             => $post->ID,
		'title'          => bh_title( $post ),
		'short_name'     => $m( 'short_name' ) ?: bh_title( $post ),
		'slug'           => $post->post_name,
		'permalink'      => get_permalink( $post ),
		'excerpt'        => get_the_excerpt( $post ),
		'street'         => $street,
		'city'           => $city,
		'state'          => $state,
		'zip'            => $zip,
		'full_address'   => $full,
		'cross_street'   => $m( 'cross_street' ),
		'phone'          => $phone,
		'phone_e164'     => bh_phone_e164( $phone ),
		'lat'            => $m( 'lat' ),
		'lng'            => $m( 'lng' ),
		'maps_url'       => $m( 'maps_url' ) ?: 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( bh_setting( 'business_name' ) . ' ' . $full ),
		'directions_url' => 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode( bh_setting( 'business_name' ) . ' ' . $full ),
		'order_url'      => $m( 'order_url' ),
		'catering_url'   => $m( 'catering_url' ) ?: bh_setting( 'catering_order_url' ),
		'ubereats_url'   => $m( 'ubereats_url' ),
		'doordash_url'   => $m( 'doordash_url' ),
		'toast_guid'     => $m( 'toast_guid' ),
		'features'       => bh_lines( $m( 'features' ) ),
		'happy_hour'     => $m( 'happy_hour' ),
		'hours_note'     => $m( 'hours_note' ),
		'hours'          => $hours,
		'hours_rows'     => bh_hours_rows( $hours ),
		'status'         => bh_open_status( $hours ),
		'image_id'       => get_post_thumbnail_id( $post ),
	);
}

/**
 * Format "13:00" → "1pm" / "1:30pm".
 */
function bh_fmt_time( $hm ) {
	if ( ! $hm ) {
		return '';
	}
	list( $h, $i ) = array_map( 'intval', explode( ':', $hm ) );
	$suffix        = $h >= 12 ? 'pm' : 'am';
	$h12           = $h % 12 ?: 12;
	return $i ? sprintf( '%d:%02d%s', $h12, $i, $suffix ) : $h12 . $suffix;
}

/**
 * Group consecutive days with identical hours: [ ['Tue–Thu', '11am–8pm'], ... ].
 */
function bh_hours_rows( $hours ) {
	$short = array( 'mon' => 'Mon', 'tue' => 'Tue', 'wed' => 'Wed', 'thu' => 'Thu', 'fri' => 'Fri', 'sat' => 'Sat', 'sun' => 'Sun' );
	$rows  = array();
	$prev  = null;
	foreach ( $short as $k => $label ) {
		$d   = $hours[ $k ] ?? array( 'closed' => '1' );
		$val = ( ! empty( $d['closed'] ) || empty( $d['open'] ) ) ? 'Closed' : bh_fmt_time( $d['open'] ) . '–' . bh_fmt_time( $d['close'] );
		if ( $prev && $prev['value'] === $val ) {
			$rows[ count( $rows ) - 1 ]['to'] = $label;
		} else {
			$rows[] = array( 'from' => $label, 'to' => '', 'value' => $val );
		}
		$prev = end( $rows );
	}
	return array_map(
		function ( $r ) {
			return array(
				'days'  => $r['to'] ? $r['from'] . '–' . $r['to'] : $r['from'],
				'value' => $r['value'],
			);
		},
		$rows
	);
}

/**
 * Open/closed status right now (site timezone).
 *
 * @return array{open:bool,label:string,today:string}
 */
function bh_open_status( $hours ) {
	$now   = bh_now();
	$key   = strtolower( $now->format( 'D' ) );
	$today = $hours[ $key ] ?? array();
	$hm    = $now->format( 'H:i' );
	if ( empty( $today ) || ! empty( $today['closed'] ) || empty( $today['open'] ) ) {
		return array( 'open' => false, 'label' => 'Closed today', 'today' => 'Closed' );
	}
	$todays = bh_fmt_time( $today['open'] ) . '–' . bh_fmt_time( $today['close'] );
	if ( $hm >= $today['open'] && $hm < $today['close'] ) {
		return array( 'open' => true, 'label' => 'Open now · until ' . bh_fmt_time( $today['close'] ), 'today' => $todays );
	}
	if ( $hm < $today['open'] ) {
		return array( 'open' => false, 'label' => 'Opens ' . bh_fmt_time( $today['open'] ), 'today' => $todays );
	}
	return array( 'open' => false, 'label' => 'Closed now', 'today' => $todays );
}

/**
 * Menu sections with their items.
 *
 * @param string $group restaurant|catering|happy-hour|specials|'' (all).
 * @return array<int,array{term:WP_Term,slug:string,name:string,description:string,note:string,group:string,items:array}>
 */
function bh_get_menu( $group = 'restaurant' ) {
	$terms = get_terms(
		array(
			'taxonomy'   => 'bh_menu_section',
			'hide_empty' => true,
		)
	);
	if ( is_wp_error( $terms ) ) {
		return array();
	}
	$sections = array();
	foreach ( $terms as $term ) {
		$tgroup = get_term_meta( $term->term_id, 'bh_group', true ) ?: 'restaurant';
		if ( $group && $tgroup !== $group ) {
			continue;
		}
		$items = get_posts(
			array(
				'post_type'      => 'bh_menu_item',
				'posts_per_page' => 100,
				'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
				'tax_query'      => array(
					array(
						'taxonomy'         => 'bh_menu_section',
						'terms'            => $term->term_id,
						'include_children' => false,
					),
				),
			)
		);
		$sections[] = array(
			'term'        => $term,
			'slug'        => $term->slug,
			'name'        => html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' ),
			'description' => $term->description,
			'note'        => (string) get_term_meta( $term->term_id, 'bh_note', true ),
			'group'       => $tgroup,
			'order'       => (int) get_term_meta( $term->term_id, 'bh_order', true ),
			'items'       => array_map( 'bh_get_menu_item', $items ),
		);
	}
	usort( $sections, fn( $a, $b ) => $a['order'] <=> $b['order'] );
	return $sections;
}

function bh_get_menu_item( $post ) {
	$post   = get_post( $post );
	$prices = bh_parse_price_lines( get_post_meta( $post->ID, '_bh_prices', true ) );
	$unit   = (string) get_post_meta( $post->ID, '_bh_unit', true );
	return array(
		'id'          => $post->ID,
		'title'       => bh_title( $post ),
		'description' => wp_strip_all_tags( $post->post_content ),
		'prices'      => $prices,
		'unit'        => $unit,
		'price_label' => bh_price_label( $prices, $unit ),
		'badges'      => array_filter( array_map( 'trim', explode( ',', (string) get_post_meta( $post->ID, '_bh_badges', true ) ) ) ),
		'diet'        => array_filter( array_map( 'trim', explode( ',', (string) get_post_meta( $post->ID, '_bh_diet', true ) ) ) ),
		'featured'    => (bool) get_post_meta( $post->ID, '_bh_featured', true ),
		'image_id'    => get_post_thumbnail_id( $post ),
	);
}

/**
 * Short price label: "$13.95", "$2.49+", "$29.95 / lb".
 */
function bh_price_label( $prices, $unit = '' ) {
	$nums = array_filter( array_map( fn( $p ) => $p['price'], $prices ), 'strlen' );
	if ( ! $nums ) {
		return '';
	}
	if ( 1 === count( $nums ) ) {
		$label = bh_money( reset( $nums ) );
		$suf   = $unit ?: ( $prices[0]['label'] ?? '' );
		return $suf ? $label . ' / ' . $suf : $label;
	}
	return 'From ' . bh_money( min( array_map( 'floatval', $nums ) ) );
}

/**
 * Featured menu items (homepage carousel).
 */
function bh_get_featured_items( $limit = 8 ) {
	$posts = get_posts(
		array(
			'post_type'      => 'bh_menu_item',
			'posts_per_page' => $limit,
			'meta_key'       => '_bh_featured', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => '1', // phpcs:ignore WordPress.DB.SlowDBQuery
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		)
	);
	return array_map( 'bh_get_menu_item', $posts );
}

/**
 * Catering packages.
 */
function bh_get_packages() {
	$posts = get_posts(
		array(
			'post_type'      => 'bh_catering_pkg',
			'posts_per_page' => 30,
			'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		)
	);
	return array_map(
		function ( $p ) {
			$m = fn( $k ) => (string) get_post_meta( $p->ID, '_bh_' . $k, true );
			return array(
				'id'          => $p->ID,
				'title'       => bh_title( $p ),
				'description' => wp_strip_all_tags( $p->post_content ),
				'tagline'     => $m( 'tagline' ),
				'price'       => $m( 'price' ),
				'unit'        => $m( 'unit' ) ?: 'per person',
				'minimum'     => $m( 'minimum' ),
				'min_guests'  => (int) $m( 'min_guests' ),
				'includes'    => bh_lines( $m( 'includes' ) ),
				'addons'      => bh_parse_price_lines( $m( 'addons' ) ),
				'featured'    => (bool) $m( 'featured' ),
				'image_id'    => get_post_thumbnail_id( $p ),
			);
		},
		$posts
	);
}

/**
 * FAQs, optionally by topic slug (general, catering, locations, menu, parties).
 */
function bh_get_faqs( $topic = '' ) {
	$args = array(
		'post_type'      => 'bh_faq',
		'posts_per_page' => 50,
		'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'ASC' ),
	);
	if ( $topic ) {
		$args['tax_query'] = array( array( 'taxonomy' => 'bh_faq_topic', 'field' => 'slug', 'terms' => (array) $topic ) ); // phpcs:ignore
	}
	return array_map(
		fn( $p ) => array(
			'id'       => $p->ID,
			'question' => bh_title( $p ),
			'answer'   => apply_filters( 'the_content', $p->post_content ),
			'text'     => wp_strip_all_tags( $p->post_content ),
		),
		get_posts( $args )
	);
}

/**
 * Hand-picked reviews.
 */
function bh_get_reviews( $limit = 8 ) {
	return array_map(
		function ( $p ) {
			return array(
				'id'     => $p->ID,
				'title'  => bh_title( $p ),
				'text'   => wp_strip_all_tags( $p->post_content ),
				'author' => (string) get_post_meta( $p->ID, '_bh_author', true ),
				'source' => (string) get_post_meta( $p->ID, '_bh_source', true ) ?: 'Google',
				'rating' => (int) get_post_meta( $p->ID, '_bh_rating', true ) ?: 5,
				'url'    => (string) get_post_meta( $p->ID, '_bh_url', true ),
			);
		},
		get_posts(
			array(
				'post_type'      => 'bh_review',
				'posts_per_page' => $limit,
				'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
			)
		)
	);
}

/**
 * HTML data-attributes for first-party click tracking (see tracking.php).
 *
 * @param string $event    order|catering_order|call|directions|delivery|...
 * @param int    $location Location post ID.
 */
function bh_track_attrs( $event, $location = 0 ) {
	return sprintf( ' data-bh-track="%s" data-bh-loc="%d"', esc_attr( $event ), (int) $location );
}
