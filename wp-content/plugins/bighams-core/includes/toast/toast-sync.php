<?php
/**
 * Toast → WordPress sync.
 *
 * Hourly cron pulls today's + yesterday's orders for each location that has
 * a Toast GUID and stores aggregated stats only (no guest PII):
 *   wp_bh_toast_daily   one row per business day per location
 *   wp_bh_toast_items   item quantities/sales per day per location
 *   wp_bh_toast_hourly  orders/sales per hour (busiest-times chart)
 *
 * Optional menu check compares Toast prices to website prices and flags
 * mismatches on the dashboard (it never overwrites the website menu).
 */

defined( 'ABSPATH' ) || exit;

function bh_toast_tables() {
	global $wpdb;
	return array(
		'daily'  => $wpdb->prefix . 'bh_toast_daily',
		'items'  => $wpdb->prefix . 'bh_toast_items',
		'hourly' => $wpdb->prefix . 'bh_toast_hourly',
	);
}

function bh_toast_install_tables() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$t = bh_toast_tables();
	$c = $wpdb->get_charset_collate();
	dbDelta(
		"CREATE TABLE {$t['daily']} (
			day date NOT NULL,
			location_id bigint(20) unsigned NOT NULL,
			orders int(10) unsigned NOT NULL DEFAULT 0,
			guests int(10) unsigned NOT NULL DEFAULT 0,
			net_sales decimal(12,2) NOT NULL DEFAULT 0,
			tax decimal(12,2) NOT NULL DEFAULT 0,
			tips decimal(12,2) NOT NULL DEFAULT 0,
			discounts decimal(12,2) NOT NULL DEFAULT 0,
			online_orders int(10) unsigned NOT NULL DEFAULT 0,
			online_sales decimal(12,2) NOT NULL DEFAULT 0,
			catering_orders int(10) unsigned NOT NULL DEFAULT 0,
			catering_sales decimal(12,2) NOT NULL DEFAULT 0,
			voids int(10) unsigned NOT NULL DEFAULT 0,
			channels longtext NULL,
			synced_at datetime NOT NULL,
			PRIMARY KEY  (day,location_id)
		) {$c};"
	);
	dbDelta(
		"CREATE TABLE {$t['items']} (
			day date NOT NULL,
			location_id bigint(20) unsigned NOT NULL,
			item_key char(32) NOT NULL,
			item_name varchar(191) NOT NULL,
			qty decimal(10,2) NOT NULL DEFAULT 0,
			sales decimal(12,2) NOT NULL DEFAULT 0,
			PRIMARY KEY  (day,location_id,item_key),
			KEY item_name (item_name)
		) {$c};"
	);
	dbDelta(
		"CREATE TABLE {$t['hourly']} (
			day date NOT NULL,
			location_id bigint(20) unsigned NOT NULL,
			hour tinyint(3) unsigned NOT NULL,
			orders int(10) unsigned NOT NULL DEFAULT 0,
			sales decimal(12,2) NOT NULL DEFAULT 0,
			PRIMARY KEY  (day,location_id,hour)
		) {$c};"
	);
}

function bh_toast_schedule() {
	if ( ! wp_next_scheduled( 'bh_toast_sync_event' ) ) {
		wp_schedule_event( time() + 120, 'hourly', 'bh_toast_sync_event' );
	}
}

add_action( 'bh_toast_sync_event', function () {
	$client = new BH_Toast_Client();
	if ( ! $client->is_configured() ) {
		return;
	}
	$today = bh_now();
	bh_toast_sync_day( $today->modify( '-1 day' ) );
	bh_toast_sync_day( $today );
	// Daily menu price check at ~6am.
	if ( '1' === bh_setting( 'toast_sync_menu' ) && (int) $today->format( 'G' ) === 6 ) {
		bh_toast_check_menu();
	}
} );

/* Backfill: one single-event per day so long ranges don't time out. */
add_action( 'bh_toast_backfill_day', function ( $ymd ) {
	bh_toast_sync_day( new DateTimeImmutable( $ymd, wp_timezone() ) );
} );

function bh_toast_schedule_backfill( $days ) {
	$days = min( 365, max( 1, (int) $days ) );
	for ( $i = 1; $i <= $days; $i++ ) {
		$ymd = bh_now()->modify( "-$i days" )->format( 'Y-m-d' );
		wp_schedule_single_event( time() + $i * 20, 'bh_toast_backfill_day', array( $ymd ) );
	}
	return $days;
}

/**
 * Sync every connected location for one business day.
 */
function bh_toast_sync_day( DateTimeImmutable $day ) {
	$client = new BH_Toast_Client();
	$log    = array();
	foreach ( bh_get_locations() as $loc ) {
		if ( ! $loc['toast_guid'] ) {
			continue;
		}
		$res   = bh_toast_sync_location_day( $client, $loc, $day );
		$log[] = $loc['short_name'] . ': ' . ( is_wp_error( $res ) ? 'ERROR ' . $res->get_error_message() : $res . ' orders' );
	}
	update_option(
		'bh_toast_last_sync',
		array(
			'time' => time(),
			'day'  => $day->format( 'Y-m-d' ),
			'log'  => $log,
		),
		false
	);
	return $log;
}

/**
 * Aggregate one location/day.
 *
 * @return int|WP_Error Order count.
 */
function bh_toast_sync_location_day( BH_Toast_Client $client, array $loc, DateTimeImmutable $day ) {
	global $wpdb;
	$orders = $client->orders_for_business_date( $loc['toast_guid'], $day->format( 'Ymd' ) );
	if ( is_wp_error( $orders ) ) {
		return $orders;
	}
	$dopts = $client->dining_options( $loc['toast_guid'] );
	$dopts = is_wp_error( $dopts ) ? array() : $dopts;

	$agg = array(
		'orders' => 0, 'guests' => 0, 'net_sales' => 0.0, 'tax' => 0.0, 'tips' => 0.0, 'discounts' => 0.0,
		'online_orders' => 0, 'online_sales' => 0.0, 'catering_orders' => 0, 'catering_sales' => 0.0, 'voids' => 0,
	);
	$channels = array();
	$items    = array();
	$hourly   = array();
	$tz       = wp_timezone();

	foreach ( $orders as $o ) {
		if ( ! empty( $o['voided'] ) || ! empty( $o['deleted'] ) ) {
			$agg['voids']++;
			continue;
		}
		$net = 0.0;
		foreach ( (array) ( $o['checks'] ?? array() ) as $chk ) {
			if ( ! empty( $chk['voided'] ) || ! empty( $chk['deleted'] ) ) {
				continue;
			}
			$net             += (float) ( $chk['amount'] ?? 0 );
			$agg['tax']      += (float) ( $chk['taxAmount'] ?? 0 );
			foreach ( (array) ( $chk['appliedDiscounts'] ?? array() ) as $d ) {
				$agg['discounts'] += (float) ( $d['discountAmount'] ?? 0 );
			}
			foreach ( (array) ( $chk['payments'] ?? array() ) as $pay ) {
				$agg['tips'] += (float) ( $pay['tipAmount'] ?? 0 );
			}
			foreach ( (array) ( $chk['selections'] ?? array() ) as $sel ) {
				if ( ! empty( $sel['voided'] ) ) {
					continue;
				}
				$name = trim( (string) ( $sel['displayName'] ?? '' ) );
				if ( '' === $name ) {
					continue;
				}
				$k = md5( strtolower( $name ) );
				if ( ! isset( $items[ $k ] ) ) {
					$items[ $k ] = array( 'name' => $name, 'qty' => 0, 'sales' => 0 );
				}
				$items[ $k ]['qty']   += (float) ( $sel['quantity'] ?? 1 );
				$items[ $k ]['sales'] += (float) ( $sel['price'] ?? 0 );
			}
		}
		$agg['orders']++;
		$agg['guests']    += (int) ( $o['numberOfGuests'] ?? 0 );
		$agg['net_sales'] += $net;

		$source  = (string) ( $o['source'] ?? 'Unknown' );
		$dopt    = $dopts[ $o['diningOption']['guid'] ?? '' ] ?? array( 'name' => '', 'behavior' => '' );
		$channel = $dopt['name'] ?: $source;
		if ( ! isset( $channels[ $channel ] ) ) {
			$channels[ $channel ] = array( 'orders' => 0, 'sales' => 0 );
		}
		$channels[ $channel ]['orders']++;
		$channels[ $channel ]['sales'] += $net;

		$is_online = in_array( strtolower( $source ), array( 'online', 'order-and-pay', 'api', 'grubhub', 'uber eats', 'doordash' ), true ) || false !== stripos( $source, 'online' );
		if ( $is_online ) {
			$agg['online_orders']++;
			$agg['online_sales'] += $net;
		}
		if ( false !== stripos( $source . ' ' . $dopt['name'], 'cater' ) || 'invoice' === strtolower( $source ) ) {
			$agg['catering_orders']++;
			$agg['catering_sales'] += $net;
		}

		$opened = $o['openedDate'] ?? ( $o['createdDate'] ?? '' );
		if ( $opened ) {
			try {
				$h = (int) ( new DateTimeImmutable( $opened ) )->setTimezone( $tz )->format( 'G' );
				if ( ! isset( $hourly[ $h ] ) ) {
					$hourly[ $h ] = array( 'orders' => 0, 'sales' => 0 );
				}
				$hourly[ $h ]['orders']++;
				$hourly[ $h ]['sales'] += $net;
			} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement
			}
		}
	}

	$t   = bh_toast_tables();
	$ymd = $day->format( 'Y-m-d' );
	$lid = (int) $loc['id'];
	// phpcs:disable WordPress.DB.DirectDatabaseQuery
	$wpdb->replace(
		$t['daily'],
		array_merge(
			$agg,
			array(
				'day'         => $ymd,
				'location_id' => $lid,
				'channels'    => wp_json_encode( $channels ),
				'synced_at'   => current_time( 'mysql' ),
			)
		)
	);
	$wpdb->delete( $t['items'], array( 'day' => $ymd, 'location_id' => $lid ) );
	foreach ( $items as $k => $it ) {
		$wpdb->insert( $t['items'], array( 'day' => $ymd, 'location_id' => $lid, 'item_key' => $k, 'item_name' => substr( $it['name'], 0, 191 ), 'qty' => $it['qty'], 'sales' => $it['sales'] ) );
	}
	$wpdb->delete( $t['hourly'], array( 'day' => $ymd, 'location_id' => $lid ) );
	foreach ( $hourly as $h => $v ) {
		$wpdb->insert( $t['hourly'], array( 'day' => $ymd, 'location_id' => $lid, 'hour' => $h, 'orders' => $v['orders'], 'sales' => $v['sales'] ) );
	}
	// phpcs:enable
	return $agg['orders'];
}

/**
 * Compare Toast menu prices to website menu items with a single price.
 * Stores mismatches for the dashboard; links Toast GUIDs by exact name.
 */
function bh_toast_check_menu() {
	$client = new BH_Toast_Client();
	$loc    = null;
	foreach ( bh_get_locations() as $l ) {
		if ( $l['toast_guid'] ) {
			$loc = $l;
			break;
		}
	}
	if ( ! $loc ) {
		return new WP_Error( 'bh_toast_noloc', 'No location has a Toast GUID yet.' );
	}
	$data = $client->menus( $loc['toast_guid'] );
	if ( is_wp_error( $data ) ) {
		return $data;
	}
	$toast = array();
	$walk  = function ( $groups ) use ( &$walk, &$toast ) {
		foreach ( (array) $groups as $g ) {
			foreach ( (array) ( $g['menuItems'] ?? array() ) as $it ) {
				$toast[ strtolower( trim( $it['name'] ?? '' ) ) ] = array( 'guid' => $it['guid'] ?? '', 'price' => $it['price'] ?? null, 'name' => $it['name'] ?? '' );
			}
			$walk( $g['menuGroups'] ?? array() );
		}
	};
	foreach ( (array) ( $data['menus'] ?? array() ) as $menu ) {
		$walk( $menu['menuGroups'] ?? array() );
	}
	$mismatch = array();
	$matched  = 0;
	foreach ( get_posts( array( 'post_type' => 'bh_menu_item', 'posts_per_page' => -1 ) ) as $p ) {
		$key = strtolower( trim( $p->post_title ) );
		if ( ! isset( $toast[ $key ] ) ) {
			continue;
		}
		$matched++;
		update_post_meta( $p->ID, '_bh_toast_guid', $toast[ $key ]['guid'] );
		$prices = bh_parse_price_lines( get_post_meta( $p->ID, '_bh_prices', true ) );
		if ( 1 === count( $prices ) && null !== $toast[ $key ]['price'] && abs( (float) $prices[0]['price'] - (float) $toast[ $key ]['price'] ) > 0.009 ) {
			$mismatch[] = array( 'id' => $p->ID, 'name' => $p->post_title, 'site' => (float) $prices[0]['price'], 'toast' => (float) $toast[ $key ]['price'] );
		}
	}
	$result = array( 'time' => time(), 'toast_items' => count( $toast ), 'matched' => $matched, 'mismatch' => $mismatch );
	update_option( 'bh_toast_menu_check', $result, false );
	return $result;
}

/* ---------------------------------------------------------------------------
 * Query helpers for the dashboard
 * ------------------------------------------------------------------------ */

function bh_toast_has_data() {
	global $wpdb;
	$t = bh_toast_tables();
	return (bool) $wpdb->get_var( "SELECT 1 FROM {$t['daily']} LIMIT 1" ); // phpcs:ignore
}

/**
 * Totals for a date range, optionally per location.
 */
function bh_toast_totals( $from, $to, $location_id = null ) {
	global $wpdb;
	$t    = bh_toast_tables();
	$sql  = "SELECT COALESCE(SUM(orders),0) orders, COALESCE(SUM(guests),0) guests, COALESCE(SUM(net_sales),0) net_sales, COALESCE(SUM(tips),0) tips, COALESCE(SUM(discounts),0) discounts, COALESCE(SUM(online_orders),0) online_orders, COALESCE(SUM(online_sales),0) online_sales, COALESCE(SUM(catering_orders),0) catering_orders, COALESCE(SUM(catering_sales),0) catering_sales FROM {$t['daily']} WHERE day BETWEEN %s AND %s";
	$args = array( $from, $to );
	if ( null !== $location_id ) {
		$sql   .= ' AND location_id = %d';
		$args[] = (int) $location_id;
	}
	$row               = $wpdb->get_row( $wpdb->prepare( $sql, $args ), ARRAY_A ); // phpcs:ignore
	$row               = array_map( 'floatval', (array) $row );
	$row['avg_ticket'] = $row['orders'] ? $row['net_sales'] / $row['orders'] : 0;
	$row['online_pct'] = $row['orders'] ? 100 * $row['online_orders'] / $row['orders'] : 0;
	return $row;
}

/**
 * Daily net sales series per location: [ 'Y-m-d' => [loc_id => sales] ].
 */
function bh_toast_daily_series( $days = 30 ) {
	global $wpdb;
	$t    = bh_toast_tables();
	$from = bh_now()->modify( '-' . ( $days - 1 ) . ' days' )->format( 'Y-m-d' );
	$rows = $wpdb->get_results( $wpdb->prepare( "SELECT day, location_id, net_sales, orders FROM {$t['daily']} WHERE day >= %s ORDER BY day", $from ), ARRAY_A ); // phpcs:ignore
	$out  = array();
	for ( $i = $days - 1; $i >= 0; $i-- ) {
		$out[ bh_now()->modify( "-$i days" )->format( 'Y-m-d' ) ] = array();
	}
	foreach ( $rows as $r ) {
		$out[ $r['day'] ][ (int) $r['location_id'] ] = (float) $r['net_sales'];
	}
	return $out;
}

function bh_toast_top_items( $from, $to, $limit = 10, $location_id = null ) {
	global $wpdb;
	$t    = bh_toast_tables();
	$sql  = "SELECT item_name, SUM(qty) qty, SUM(sales) sales FROM {$t['items']} WHERE day BETWEEN %s AND %s";
	$args = array( $from, $to );
	if ( null !== $location_id ) {
		$sql   .= ' AND location_id = %d';
		$args[] = (int) $location_id;
	}
	$sql   .= ' GROUP BY item_name ORDER BY sales DESC LIMIT %d';
	$args[] = (int) $limit;
	return $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A ); // phpcs:ignore
}

/**
 * Average orders by weekday × hour (heatmap).
 *
 * @return array<int,array<int,float>> [weekday 0=Mon..6][hour]
 */
function bh_toast_heatmap( $days = 56 ) {
	global $wpdb;
	$t    = bh_toast_tables();
	$from = bh_now()->modify( "-$days days" )->format( 'Y-m-d' );
	$rows = $wpdb->get_results( $wpdb->prepare( "SELECT WEEKDAY(day) wd, hour, SUM(orders) n, COUNT(DISTINCT day) d FROM {$t['hourly']} WHERE day >= %s GROUP BY wd, hour", $from ), ARRAY_A ); // phpcs:ignore
	$out  = array();
	foreach ( $rows as $r ) {
		$out[ (int) $r['wd'] ][ (int) $r['hour'] ] = $r['d'] ? (float) $r['n'] / (int) $r['d'] : 0;
	}
	return $out;
}

function bh_toast_channels( $from, $to ) {
	global $wpdb;
	$t    = bh_toast_tables();
	$rows = $wpdb->get_col( $wpdb->prepare( "SELECT channels FROM {$t['daily']} WHERE day BETWEEN %s AND %s", $from, $to ) ); // phpcs:ignore
	$out  = array();
	foreach ( $rows as $json ) {
		foreach ( (array) json_decode( (string) $json, true ) as $name => $v ) {
			if ( ! isset( $out[ $name ] ) ) {
				$out[ $name ] = array( 'orders' => 0, 'sales' => 0 );
			}
			$out[ $name ]['orders'] += (int) $v['orders'];
			$out[ $name ]['sales']  += (float) $v['sales'];
		}
	}
	uasort( $out, fn( $a, $b ) => $b['sales'] <=> $a['sales'] );
	return $out;
}
