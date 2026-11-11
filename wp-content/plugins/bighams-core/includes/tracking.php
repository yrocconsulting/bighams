<?php
/**
 * First-party, cookie-free click tracking for the actions that make money:
 * Order Online, Catering Order, Call, Directions, Delivery apps, Leads.
 * Counts are aggregated per day/event/location (no personal data stored).
 */

defined( 'ABSPATH' ) || exit;

function bh_tracking_table() {
	global $wpdb;
	return $wpdb->prefix . 'bh_events';
}

function bh_tracking_install_table() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$table   = bh_tracking_table();
	$charset = $wpdb->get_charset_collate();
	dbDelta(
		"CREATE TABLE {$table} (
			day date NOT NULL,
			event varchar(40) NOT NULL,
			location_id bigint(20) unsigned NOT NULL DEFAULT 0,
			page varchar(191) NOT NULL DEFAULT '',
			hits int(10) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (day,event,location_id,page)
		) {$charset};"
	);
}

function bh_track_events() {
	return array(
		'order'          => 'Order Online clicks',
		'catering_order' => 'Catering order clicks',
		'call'           => 'Call clicks',
		'directions'     => 'Directions clicks',
		'delivery'       => 'Delivery app clicks',
		'lead_catering'  => 'Catering inquiries',
		'lead_party'     => 'Private event inquiries',
		'menu_view'      => 'Menu views',
	);
}

/**
 * Increment a counter.
 */
function bh_track_event( $event, $location_id = 0, $page = '' ) {
	global $wpdb;
	if ( ! array_key_exists( $event, bh_track_events() ) ) {
		return;
	}
	$page = substr( (string) wp_parse_url( $page, PHP_URL_PATH ), 0, 191 );
	$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->prepare(
			'INSERT INTO ' . bh_tracking_table() . ' (day,event,location_id,page,hits) VALUES (%s,%s,%d,%s,1) ON DUPLICATE KEY UPDATE hits = hits + 1', // phpcs:ignore WordPress.DB.PreparedSQL
			bh_now()->format( 'Y-m-d' ),
			$event,
			(int) $location_id,
			$page
		)
	);
}

add_action( 'rest_api_init', function () {
	register_rest_route(
		'bighams/v1',
		'/track',
		array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true',
			'args'                => array(
				'e' => array( 'required' => true, 'type' => 'string' ),
				'l' => array( 'type' => 'integer', 'default' => 0 ),
				'p' => array( 'type' => 'string', 'default' => '' ),
			),
			'callback'            => function ( WP_REST_Request $r ) {
				// Ignore logged-in staff so their testing doesn't skew stats.
				if ( ! is_user_logged_in() ) {
					bh_track_event( sanitize_key( $r['e'] ), absint( $r['l'] ), esc_url_raw( $r['p'] ) );
				}
				return new WP_REST_Response( null, 204 );
			},
		)
	);
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_script( 'bh-track', BH_CORE_URL . 'assets/track.js', array(), BH_CORE_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
	wp_localize_script( 'bh-track', 'bhTrack', array( 'url' => esc_url_raw( rest_url( 'bighams/v1/track' ) ) ) );
} );

/**
 * Totals per event between two dates.
 *
 * @return array<string,int>
 */
function bh_tracking_totals( $from, $to, $location_id = null ) {
	global $wpdb;
	$sql  = 'SELECT event, SUM(hits) AS n FROM ' . bh_tracking_table() . ' WHERE day BETWEEN %s AND %s';
	$args = array( $from, $to );
	if ( null !== $location_id ) {
		$sql   .= ' AND location_id = %d';
		$args[] = (int) $location_id;
	}
	$sql .= ' GROUP BY event';
	$rows = $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A ); // phpcs:ignore
	$out  = array_fill_keys( array_keys( bh_track_events() ), 0 );
	foreach ( (array) $rows as $r ) {
		$out[ $r['event'] ] = (int) $r['n'];
	}
	return $out;
}

/**
 * Daily series for one event.
 */
function bh_tracking_series( $event, $days = 30 ) {
	global $wpdb;
	$from = bh_now()->modify( '-' . ( $days - 1 ) . ' days' )->format( 'Y-m-d' );
	$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT day, SUM(hits) n FROM ' . bh_tracking_table() . ' WHERE event = %s AND day >= %s GROUP BY day', $event, $from ), OBJECT_K ); // phpcs:ignore
	$out  = array();
	for ( $i = $days - 1; $i >= 0; $i-- ) {
		$d         = bh_now()->modify( "-$i days" )->format( 'Y-m-d' );
		$out[ $d ] = isset( $rows[ $d ] ) ? (int) $rows[ $d ]->n : 0;
	}
	return $out;
}

/**
 * Event counts per location.
 */
function bh_tracking_by_location( $event, $from, $to ) {
	global $wpdb;
	return $wpdb->get_results( $wpdb->prepare( 'SELECT location_id, SUM(hits) n FROM ' . bh_tracking_table() . ' WHERE event = %s AND day BETWEEN %s AND %s GROUP BY location_id ORDER BY n DESC', $event, $from, $to ), ARRAY_A ); // phpcs:ignore
}
