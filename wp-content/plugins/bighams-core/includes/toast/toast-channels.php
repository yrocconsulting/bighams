<?php
/**
 * How Bigham's orders are classified, based on how their Toast account
 * actually tags orders (checked with `wp bighams toast-inspect`, Nov 2026):
 *
 *   Catering  source "Catering" / "Catering Online Ordering" / "Invoice",
 *             dining option *Catering-Pickup / *Catering-Delivery /
 *             *Catering Online Ordering - Pick Up / *EZ Cater,
 *             or revenue center "*Catering & Events"
 *   Events    dining option *Event (private parties / events)
 *   Delivery  source API or DoorDash / UberEats / GrubHub dining options
 *   Online    source "Branded Online Ordering" (Bigham's own Toast ordering)
 *   Drive-thru, Dine-in, Take out & call-in, Too Good To Go
 *
 * Catering and event orders are also stored one row per order (no guest
 * names or contact details) so the dashboard can list upcoming events.
 */

defined( 'ABSPATH' ) || exit;

function bh_toast_channel_labels() {
	return array(
		'dine_in'       => 'Dine-in',
		'drive_thru'    => 'Drive-thru',
		'takeout'       => 'Take out & call-in',
		'online'        => 'Online ordering (Toast)',
		'delivery_apps' => 'Delivery apps',
		'catering'      => 'Catering',
		'events'        => 'Events & parties',
		'tgtg'          => 'Too Good To Go',
		'other'         => 'Other',
	);
}

/**
 * Strip Toast's leading "*" and tidy a dining option / revenue center name.
 */
function bh_toast_clean_name( $name ) {
	return trim( ltrim( trim( (string) $name ), '*' ), " \t\"" );
}

/**
 * Classify one order.
 *
 * @param array  $o    Toast order.
 * @param array  $dopt ['name','behavior'] of its dining option.
 * @param string $rc   Revenue center name.
 * @return array{channel:string,service:string}
 */
function bh_toast_classify( $o, $dopt, $rc = '' ) {
	$source = strtolower( (string) ( $o['source'] ?? '' ) );
	$name   = bh_toast_clean_name( $dopt['name'] ?? '' );
	$lname  = strtolower( $name );
	$beh    = strtoupper( (string) ( $dopt['behavior'] ?? '' ) );
	$lrc    = strtolower( bh_toast_clean_name( $rc ) );

	// Events & private parties.
	if ( preg_match( '/\b(event|party|private)\b/', $lname ) ) {
		return array( 'channel' => 'events', 'service' => 'Event / private party' );
	}
	// Catering (Toast Catering & Events, EZ Cater, catering dining options or revenue center).
	if ( false !== strpos( $lname, 'ez cater' ) || false !== strpos( $lname, 'ezcater' ) ) {
		return array( 'channel' => 'catering', 'service' => 'EZ Cater' );
	}
	if ( in_array( $source, array( 'catering', 'catering online ordering', 'invoice' ), true ) || false !== strpos( $lname, 'cater' ) || false !== strpos( $lrc, 'cater' ) ) {
		if ( ! in_array( $source, array( 'catering', 'catering online ordering', 'invoice' ), true ) && false === strpos( $lname, 'cater' ) ) {
			$service = 'Catering (rung in store)';
		} elseif ( 'catering online ordering' === $source || false !== strpos( $lname, 'online' ) ) {
			$service = 'Online catering order';
		} elseif ( 'DELIVERY' === $beh || false !== strpos( $lname, 'deliver' ) ) {
			$service = 'Catering delivery';
		} else {
			$service = 'Catering pickup';
		}
		return array( 'channel' => 'catering', 'service' => $service );
	}
	// Third-party delivery apps.
	if ( 'api' === $source || preg_match( '/doordash|uber ?eats|grub ?hub/', $lname ) ) {
		return array( 'channel' => 'delivery_apps', 'service' => $name ?: 'Delivery app' );
	}
	if ( false !== strpos( $lname, 'too good to go' ) ) {
		return array( 'channel' => 'tgtg', 'service' => 'Too Good To Go' );
	}
	// Bigham's own Toast online ordering (incl. curbside and Toast Delivery Services).
	if ( false !== strpos( $source, 'online' ) ) {
		return array( 'channel' => 'online', 'service' => $name ?: 'Online ordering' );
	}
	if ( false !== strpos( $lname, 'drive' ) ) {
		return array( 'channel' => 'drive_thru', 'service' => 'Drive-thru' );
	}
	if ( false !== strpos( $lname, 'dine' ) ) {
		return array( 'channel' => 'dine_in', 'service' => 'Dine-in' );
	}
	if ( preg_match( '/take ?out|call-?in|curbside|to go/', $lname ) || 'TAKE_OUT' === $beh ) {
		return array( 'channel' => 'takeout', 'service' => $name ?: 'Take out' );
	}
	if ( 'DINE_IN' === $beh ) {
		return array( 'channel' => 'dine_in', 'service' => 'Dine-in' );
	}
	return array( 'channel' => 'other', 'service' => $name ?: ( $o['source'] ?? 'Other' ) );
}

/**
 * Revenue center GUID → name for a location (cached a day).
 */
function bh_toast_revenue_centers( BH_Toast_Client $client, $guid ) {
	$key    = 'bh_toast_rc_' . md5( $guid );
	$cached = get_transient( $key );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	$res = $client->get( '/config/v2/revenueCenters', array(), $guid );
	$map = array();
	if ( ! is_wp_error( $res ) ) {
		foreach ( (array) $res as $rc ) {
			$map[ $rc['guid'] ?? '' ] = (string) ( $rc['name'] ?? '' );
		}
	}
	set_transient( $key, $map, DAY_IN_SECONDS );
	return $map;
}

/**
 * Net amount of an order (pre-tax, after discounts), skipping voided checks.
 */
function bh_toast_order_net( $o ) {
	$net = 0.0;
	foreach ( (array) ( $o['checks'] ?? array() ) as $c ) {
		if ( empty( $c['voided'] ) && empty( $c['deleted'] ) ) {
			$net += (float) ( $c['amount'] ?? 0 );
		}
	}
	return $net;
}

/**
 * One catering/event order → a row for the events table (no guest details).
 */
function bh_toast_event_row( $o, $cls, $ymd, $loc_id ) {
	$promised = (string) ( $o['promisedDate'] ?? $o['estimatedFulfillmentDate'] ?? '' );
	$at       = null;
	if ( $promised ) {
		try {
			$at = ( new DateTimeImmutable( $promised ) )->setTimezone( wp_timezone() )->format( 'Y-m-d H:i:s' );
		} catch ( Exception $e ) {
			$at = null;
		}
	}
	return array(
		'order_guid'  => substr( (string) ( $o['guid'] ?? md5( wp_json_encode( $o ) ) ), 0, 40 ),
		'day'         => $ymd,
		'location_id' => (int) $loc_id,
		'kind'        => $cls['channel'],
		'service'     => substr( $cls['service'], 0, 60 ),
		'guests'      => (int) ( $o['numberOfGuests'] ?? 0 ),
		'net_sales'   => round( bh_toast_order_net( $o ), 2 ),
		'promised_at' => $at,
		'synced_at'   => current_time( 'mysql' ),
	);
}

/**
 * Replace the stored catering/event orders for one location and business day.
 */
function bh_toast_store_events( $loc_id, $ymd, $rows ) {
	global $wpdb;
	$t = bh_toast_tables();
	// phpcs:disable WordPress.DB.DirectDatabaseQuery
	$wpdb->delete( $t['events'], array( 'day' => $ymd, 'location_id' => (int) $loc_id ) );
	foreach ( $rows as $r ) {
		$wpdb->replace( $t['events'], $r );
	}
	// phpcs:enable
}

/**
 * Pull catering & event orders already booked for upcoming days.
 */
function bh_toast_sync_upcoming( $days = 30 ) {
	$client = new BH_Toast_Client();
	if ( ! $client->is_configured() ) {
		return 0;
	}
	$n = 0;
	foreach ( bh_get_locations() as $loc ) {
		if ( ! $loc['toast_guid'] ) {
			continue;
		}
		$dopts = $client->dining_options( $loc['toast_guid'] );
		$dopts = is_wp_error( $dopts ) ? array() : $dopts;
		$rcs   = bh_toast_revenue_centers( $client, $loc['toast_guid'] );
		for ( $i = 1; $i <= $days; $i++ ) {
			$day    = bh_now()->modify( "+$i days" );
			$orders = $client->orders_for_business_date( $loc['toast_guid'], $day->format( 'Ymd' ) );
			if ( is_wp_error( $orders ) ) {
				continue;
			}
			$rows = array();
			foreach ( $orders as $o ) {
				if ( ! empty( $o['voided'] ) || ! empty( $o['deleted'] ) ) {
					continue;
				}
				$cls = bh_toast_classify( $o, $dopts[ $o['diningOption']['guid'] ?? '' ] ?? array(), $rcs[ $o['revenueCenter']['guid'] ?? '' ] ?? '' );
				if ( in_array( $cls['channel'], array( 'catering', 'events' ), true ) ) {
					$rows[] = bh_toast_event_row( $o, $cls, $day->format( 'Y-m-d' ), $loc['id'] );
				}
			}
			bh_toast_store_events( $loc['id'], $day->format( 'Y-m-d' ), $rows );
			$n += count( $rows );
		}
	}
	update_option( 'bh_toast_upcoming_sync', time(), false );
	return $n;
}

/* Upcoming catering/events refresh every 3 hours (rides the hourly sync). */
add_action( 'bh_toast_sync_event', function () {
	if ( 0 === (int) bh_now()->format( 'G' ) % 3 || ! get_option( 'bh_toast_upcoming_sync' ) ) {
		bh_toast_sync_upcoming( 30 );
	}
}, 20 );

/* ------------------------------------------------------------ Queries */

/**
 * Totals per channel for a date range: [channel => [orders, sales, guests]].
 */
function bh_toast_channel_totals( $from, $to, $location_id = null ) {
	global $wpdb;
	$t    = bh_toast_tables();
	$sql  = "SELECT channel, SUM(orders) orders, SUM(sales) sales, SUM(guests) guests FROM {$t['channels']} WHERE day BETWEEN %s AND %s";
	$args = array( $from, $to );
	if ( null !== $location_id ) {
		$sql   .= ' AND location_id = %d';
		$args[] = (int) $location_id;
	}
	$sql .= ' GROUP BY channel ORDER BY sales DESC';
	$out  = array();
	foreach ( (array) $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A ) as $r ) { // phpcs:ignore
		$out[ $r['channel'] ] = array( 'orders' => (int) $r['orders'], 'sales' => (float) $r['sales'], 'guests' => (int) $r['guests'] );
	}
	return $out;
}

/**
 * Catering / event orders between two dates.
 */
function bh_toast_event_orders( $from, $to, $kinds = array( 'catering', 'events' ) ) {
	global $wpdb;
	$t  = bh_toast_tables();
	$in = implode( ',', array_fill( 0, count( $kinds ), '%s' ) );
	return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t['events']} WHERE day BETWEEN %s AND %s AND kind IN ($in) ORDER BY day ASC, promised_at ASC", array_merge( array( $from, $to ), $kinds ) ), ARRAY_A ); // phpcs:ignore
}

function bh_toast_has_channel_data() {
	global $wpdb;
	$t = bh_toast_tables();
	return (bool) $wpdb->get_var( "SELECT 1 FROM {$t['channels']} LIMIT 1" ); // phpcs:ignore
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command(
		'bighams toast-sync',
		function ( $args, $assoc ) {
			$days = max( 0, min( 365, (int) ( $assoc['days'] ?? 2 ) ) );
			for ( $i = $days - 1; $i >= 0; $i-- ) {
				$log = bh_toast_sync_day( bh_now()->modify( "-$i days" ) );
				if ( 0 === $i % 15 || 0 === $i ) {
					WP_CLI::line( bh_now()->modify( "-$i days" )->format( 'Y-m-d' ) . ': ' . implode( ' · ', $log ) );
				}
			}
			$up = (int) ( $assoc['upcoming'] ?? 30 );
			if ( $up ) {
				WP_CLI::line( 'Upcoming catering & events found: ' . bh_toast_sync_upcoming( $up ) );
			}
			WP_CLI::success( "Synced $days days." );
		},
		array( 'shortdesc' => 'Re-sync Toast sales for the last N days (--days=90) and upcoming catering/events (--upcoming=30).' )
	);
}
