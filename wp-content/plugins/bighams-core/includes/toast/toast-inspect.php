<?php
/**
 * Read-only diagnostic: how does Toast tag Bigham's orders?
 *
 *   wp bighams toast-inspect [--days=30] [--ahead=21]
 *
 * Prints order counts and net sales grouped by source × dining option ×
 * revenue center for each location, plus how many orders are scheduled
 * ahead (catering/events placed in advance). No guest names, phones,
 * emails or card data are read or printed.
 */

defined( 'ABSPATH' ) || exit;

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

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command(
		'bighams toast-inspect',
		function ( $args, $assoc ) {
			$days   = max( 1, min( 90, (int) ( $assoc['days'] ?? 30 ) ) );
			$ahead  = max( 0, min( 60, (int) ( $assoc['ahead'] ?? 21 ) ) );
			$client = new BH_Toast_Client();
			if ( ! $client->is_configured() ) {
				WP_CLI::error( 'Toast credentials are not set.' );
			}
			foreach ( bh_get_locations() as $loc ) {
				if ( ! $loc['toast_guid'] ) {
					continue;
				}
				$dopts = $client->dining_options( $loc['toast_guid'] );
				$dopts = is_wp_error( $dopts ) ? array() : $dopts;
				$rcs   = bh_toast_revenue_centers( $client, $loc['toast_guid'] );
				$tally = array();
				$adv   = array();
				for ( $i = -$ahead; $i < $days; $i++ ) {
					$day    = bh_now()->modify( ( $i >= 0 ? '-' : '+' ) . abs( $i ) . ' days' );
					$orders = $client->orders_for_business_date( $loc['toast_guid'], $day->format( 'Ymd' ) );
					if ( is_wp_error( $orders ) ) {
						WP_CLI::warning( $loc['short_name'] . ' ' . $day->format( 'Y-m-d' ) . ': ' . $orders->get_error_message() );
						continue;
					}
					foreach ( $orders as $o ) {
						if ( ! empty( $o['voided'] ) || ! empty( $o['deleted'] ) ) {
							continue;
						}
						$net = 0.0;
						foreach ( (array) ( $o['checks'] ?? array() ) as $c ) {
							$net += (float) ( $c['amount'] ?? 0 );
						}
						$d   = $dopts[ $o['diningOption']['guid'] ?? '' ] ?? array( 'name' => '-', 'behavior' => '-' );
						$rc  = $rcs[ $o['revenueCenter']['guid'] ?? '' ] ?? '-';
						$key = sprintf( '%-26s | %-28s | %-12s | %s', (string) ( $o['source'] ?? '-' ), $d['name'] ?: '-', $d['behavior'] ?: '-', $rc ?: '-' );
						if ( ! isset( $tally[ $key ] ) ) {
							$tally[ $key ] = array( 0, 0.0, 0, 0 );
						}
						$tally[ $key ][0]++;
						$tally[ $key ][1] += $net;
						$tally[ $key ][2] += (int) ( $o['numberOfGuests'] ?? 0 );
						// Placed well ahead of fulfillment → likely catering / event.
						$opened  = strtotime( (string) ( $o['openedDate'] ?? $o['createdDate'] ?? '' ) );
						$promise = strtotime( (string) ( $o['promisedDate'] ?? $o['estimatedFulfillmentDate'] ?? '' ) );
						if ( $opened && $promise && $promise - $opened > 6 * HOUR_IN_SECONDS ) {
							$tally[ $key ][3]++;
						}
						if ( $i < 0 ) {
							$adv[ $key ] = ( $adv[ $key ] ?? 0 ) + 1;
						}
					}
				}
				uasort( $tally, fn( $a, $b ) => $b[1] <=> $a[1] );
				WP_CLI::line( '' );
				WP_CLI::line( '=== ' . $loc['short_name'] . " — last $days days (+ next $ahead scheduled) ===" );
				WP_CLI::line( sprintf( '%-26s | %-28s | %-12s | %-20s | %6s | %10s | %6s | %s', 'source', 'dining option', 'behavior', 'revenue center', 'orders', 'net $', 'guests', 'placed 6h+ ahead' ) );
				foreach ( $tally as $k => $v ) {
					WP_CLI::line( sprintf( '%s | %6d | %10s | %6d | %d%s', str_pad( $k, 93 ), $v[0], number_format( $v[1], 2 ), $v[2], $v[3], isset( $adv[ $k ] ) ? '  (' . $adv[ $k ] . ' upcoming)' : '' ) );
				}
			}
		},
		array( 'shortdesc' => 'Read-only: tally Toast orders by source, dining option and revenue center (no guest data).' )
	);
}
