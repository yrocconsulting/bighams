<?php
/**
 * Bigham's → Dashboard.
 *
 *  1. Sales (Toast): net sales, orders, avg ticket, online share, catering — vs prior period
 *  2. Sales by location + daily trend (stacked bars)
 *  3. Top items, channel mix, busiest-times heatmap
 *  4. Catering pipeline (website leads → booked revenue)
 *  5. Website conversions (order / call / directions clicks per location)
 *  6. Menu price mismatches + AI-search readiness checklist
 *
 * Charts are dependency-free inline SVG with native tooltips + table fallbacks.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( false !== strpos( $hook, 'bighams' ) || in_array( get_current_screen()->post_type ?? '', array( 'bh_lead', 'bh_location', 'bh_menu_item' ), true ) || 'index.php' === $hook ) {
		wp_enqueue_style( 'bh-admin', BH_CORE_URL . 'assets/admin.css', array(), BH_CORE_VERSION . '.' . filemtime( BH_CORE_DIR . 'assets/admin.css' ) );
	}
} );

/** Fixed location → series color (entity-bound, never rank-bound). */
function bh_location_colors() {
	$palette = array( 'var(--bh-s1)', 'var(--bh-s2)', 'var(--bh-s3)', 'var(--bh-s4)' );
	$out     = array();
	$i       = 0;
	$all     = get_posts( array( 'post_type' => 'bh_location', 'posts_per_page' => 20, 'orderby' => 'ID', 'order' => 'ASC', 'post_status' => 'any', 'fields' => 'ids' ) );
	foreach ( $all as $id ) {
		$out[ $id ] = $palette[ $i % count( $palette ) ];
		$i++;
	}
	return $out;
}

function bh_dash_period() {
	$days = isset( $_GET['days'] ) ? (int) $_GET['days'] : 30; // phpcs:ignore WordPress.Security.NonceVerification
	return in_array( $days, array( 7, 30, 90, 365 ), true ) ? $days : 30;
}

function bh_delta_html( $now, $prev ) {
	if ( ! $prev ) {
		return '<span class="bh-delta bh-delta--na">no prior data</span>';
	}
	$pct = ( $now - $prev ) / $prev * 100;
	$cls = $pct >= 0 ? 'up' : 'down';
	$arr = $pct >= 0 ? '▲' : '▼';
	return sprintf( '<span class="bh-delta bh-delta--%s">%s %s%% vs prior</span>', $cls, $arr, number_format( abs( $pct ), 1 ) );
}

function bh_render_dashboard_page() {
	$days      = bh_dash_period();
	$to        = bh_now()->format( 'Y-m-d' );
	$from      = bh_now()->modify( '-' . ( $days - 1 ) . ' days' )->format( 'Y-m-d' );
	$prev_to   = bh_now()->modify( "-$days days" )->format( 'Y-m-d' );
	$prev_from = bh_now()->modify( '-' . ( 2 * $days - 1 ) . ' days' )->format( 'Y-m-d' );
	$locs      = bh_get_locations();
	$colors    = bh_location_colors();
	$has_toast = bh_toast_has_data();
	?>
	<div class="wrap bh-admin bh-dash">
		<div class="bh-dash__head">
			<h1>Bigham's Dashboard</h1>
			<nav class="bh-range" aria-label="Date range">
				<?php foreach ( array( 7 => '7 days', 30 => '30 days', 90 => '90 days', 365 => '12 months' ) as $d => $label ) : ?>
					<a class="<?php echo $d === $days ? 'is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'days', $d, admin_url( 'admin.php?page=bighams' ) ) ); ?>"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>
		</div>
		<p class="bh-sub"><?php echo esc_html( wp_date( 'M j, Y', strtotime( $from ) ) . ' – ' . wp_date( 'M j, Y', strtotime( $to ) ) ); ?></p>

		<?php bh_dash_sales( $has_toast, $from, $to, $prev_from, $prev_to, $locs, $colors, $days ); ?>
		<?php bh_dash_catering( $from, $to, $prev_from, $prev_to ); ?>
		<?php bh_dash_web( $from, $to, $prev_from, $prev_to, $locs ); ?>
		<?php bh_dash_health( $locs ); ?>
	</div>
	<?php
}

/* ---------------------------------------------------------------- Sales */

function bh_dash_sales( $has_toast, $from, $to, $prev_from, $prev_to, $locs, $colors, $days ) {
	echo '<h2 class="bh-h2">Sales <small>from Toast POS</small></h2>';
	if ( ! $has_toast ) {
		echo '<div class="bh-card bh-card--setup"><h3>Connect Toast to see sales here</h3><ol>';
		echo '<li>In Toast Web go to <strong>Integrations → Toast API access → Manage credentials</strong> and create a read-only client (orders, menus, config, restaurants) for all 3 locations.</li>';
		echo '<li>Paste the Client ID + Secret in <a href="' . esc_url( admin_url( 'admin.php?page=bighams-settings' ) ) . '">Bigham\'s → Settings</a>, then add each location\'s Restaurant GUID on its <a href="' . esc_url( admin_url( 'edit.php?post_type=bh_location' ) ) . '">Location</a> page.</li>';
		echo '<li>Click <em>Test connection</em>, then <em>Backfill 90 days</em>. Sales, top items and busiest hours appear here and refresh hourly.</li>';
		echo '</ol></div>';
		return;
	}
	$now  = bh_toast_totals( $from, $to );
	$prev = bh_toast_totals( $prev_from, $prev_to );
	$tiles = array(
		array( 'Net sales', bh_money( $now['net_sales'] ), bh_delta_html( $now['net_sales'], $prev['net_sales'] ) ),
		array( 'Orders', number_format( $now['orders'] ), bh_delta_html( $now['orders'], $prev['orders'] ) ),
		array( 'Average ticket', bh_money( $now['avg_ticket'] ), bh_delta_html( $now['avg_ticket'], $prev['avg_ticket'] ) ),
		array( 'Online orders', number_format( $now['online_pct'], 1 ) . '%', number_format( $now['online_orders'] ) . ' orders · ' . bh_money( $now['online_sales'] ) ),
		array( 'Catering (Toast)', bh_money( $now['catering_sales'] ), number_format( $now['catering_orders'] ) . ' orders · ' . bh_delta_html( $now['catering_sales'], $prev['catering_sales'] ) ),
	);
	bh_dash_tiles( $tiles );

	// By location.
	echo '<div class="bh-grid bh-grid--2">';
	echo '<div class="bh-card"><h3>Sales by location</h3><table class="bh-table"><thead><tr><th>Location</th><th class="num">Net sales</th><th class="num">Orders</th><th class="num">Avg ticket</th><th class="num">Online</th><th class="num">vs prior</th></tr></thead><tbody>';
	foreach ( $locs as $l ) {
		$t = bh_toast_totals( $from, $to, $l['id'] );
		$p = bh_toast_totals( $prev_from, $prev_to, $l['id'] );
		printf(
			'<tr><td><span class="bh-key" style="background:%s"></span>%s%s</td><td class="num">%s</td><td class="num">%s</td><td class="num">%s</td><td class="num">%s%%</td><td class="num">%s</td></tr>',
			esc_attr( $colors[ $l['id'] ] ?? 'var(--bh-s1)' ),
			esc_html( $l['short_name'] ),
			$l['toast_guid'] ? '' : ' <em class="bh-muted">(no Toast GUID)</em>',
			esc_html( bh_money( $t['net_sales'] ) ),
			esc_html( number_format( $t['orders'] ) ),
			esc_html( bh_money( $t['avg_ticket'] ) ),
			esc_html( number_format( $t['online_pct'], 0 ) ),
			wp_kses_post( bh_delta_html( $t['net_sales'], $p['net_sales'] ) )
		);
	}
	echo '</tbody></table></div>';

	// Daily stacked bars.
	$series_days = min( $days, 90 );
	echo '<div class="bh-card"><h3>Daily net sales <small>last ' . (int) $series_days . ' days</small></h3>';
	bh_svg_stacked_bars( bh_toast_daily_series( $series_days ), $locs, $colors );
	echo '</div></div>';

	echo '<div class="bh-grid bh-grid--3">';
	// Top items.
	echo '<div class="bh-card"><h3>Top sellers</h3><table class="bh-table"><thead><tr><th>Item</th><th class="num">Qty</th><th class="num">Sales</th></tr></thead><tbody>';
	$top = bh_toast_top_items( $from, $to, 12 );
	$max = $top ? max( array_map( fn( $r ) => (float) $r['sales'], $top ) ) : 0;
	foreach ( $top as $r ) {
		printf(
			'<tr><td>%s<span class="bh-inline-bar" style="width:%s%%"></span></td><td class="num">%s</td><td class="num">%s</td></tr>',
			esc_html( $r['item_name'] ),
			esc_attr( $max ? round( 100 * $r['sales'] / $max ) : 0 ),
			esc_html( number_format( (float) $r['qty'] ) ),
			esc_html( bh_money( $r['sales'] ) )
		);
	}
	echo '</tbody></table></div>';

	// Channels.
	echo '<div class="bh-card"><h3>How guests order</h3><table class="bh-table"><thead><tr><th>Channel / dining option</th><th class="num">Orders</th><th class="num">Sales</th><th class="num">Share</th></tr></thead><tbody>';
	$ch    = bh_toast_channels( $from, $to );
	$total = array_sum( array_map( fn( $c ) => $c['sales'], $ch ) );
	foreach ( array_slice( $ch, 0, 10, true ) as $name => $c ) {
		printf( '<tr><td>%s</td><td class="num">%s</td><td class="num">%s</td><td class="num">%s%%</td></tr>', esc_html( $name ), esc_html( number_format( $c['orders'] ) ), esc_html( bh_money( $c['sales'] ) ), esc_html( $total ? number_format( 100 * $c['sales'] / $total, 1 ) : '0' ) );
	}
	echo '</tbody></table></div>';

	// Heatmap.
	echo '<div class="bh-card"><h3>Busiest times <small>avg orders, last 8 weeks, all locations</small></h3>';
	bh_heatmap( bh_toast_heatmap( 56 ) );
	echo '</div></div>';
}

function bh_dash_tiles( $tiles ) {
	echo '<div class="bh-tiles">';
	foreach ( $tiles as $t ) {
		printf( '<div class="bh-tile"><div class="bh-tile__label">%s</div><div class="bh-tile__value">%s</div><div class="bh-tile__meta">%s</div></div>', esc_html( $t[0] ), esc_html( $t[1] ), wp_kses_post( $t[2] ) );
	}
	echo '</div>';
}

/**
 * Stacked daily bars (one series per location) as inline SVG.
 */
function bh_svg_stacked_bars( $series, $locs, $colors ) {
	$w      = 640;
	$h      = 220;
	$pad_l  = 48;
	$pad_b  = 22;
	$n      = max( 1, count( $series ) );
	$totals = array_map( 'array_sum', $series );
	$max    = max( 1, max( $totals ?: array( 1 ) ) );
	$step   = ( $w - $pad_l ) / $n;
	$bw     = max( 2, $step - 2 ); // 2px surface gap between bars.
	$nice   = bh_nice_max( $max );
	echo '<svg class="bh-chart" viewBox="0 0 ' . (int) $w . ' ' . (int) $h . '" role="img" aria-label="Daily net sales by location">';
	// Grid + y labels.
	for ( $g = 0; $g <= 4; $g++ ) {
		$y = $h - $pad_b - ( $h - $pad_b - 8 ) * $g / 4;
		printf( '<line x1="%d" x2="%d" y1="%.1f" y2="%.1f" class="bh-grid-line"/>', $pad_l, $w, $y, $y );
		printf( '<text x="%d" y="%.1f" class="bh-axis" text-anchor="end">%s</text>', $pad_l - 6, $y + 4, esc_html( '$' . bh_short_num( $nice * $g / 4 ) ) );
	}
	$i = 0;
	foreach ( $series as $day => $by_loc ) {
		$x = $pad_l + $i * $step + 1;
		$y = $h - $pad_b;
		$k = 0;
		$stack = array_values( array_filter( $locs, fn( $l ) => ! empty( $by_loc[ $l['id'] ] ) ) );
		foreach ( $stack as $l ) {
			$v   = $by_loc[ $l['id'] ];
			$bh  = ( $h - $pad_b - 8 ) * $v / $nice;
			$top = $k === count( $stack ) - 1;
			$y  -= $bh;
			$seg_h = max( 0, $bh - ( $top ? 0 : 2 ) ); // 2px gap between stacked segments.
			printf(
				'<rect x="%.1f" y="%.1f" width="%.1f" height="%.1f" rx="%d" fill="%s"><title>%s — %s: %s</title></rect>',
				$x, $y + ( $top ? 0 : 2 ), $bw, $seg_h, $top && $bw > 6 ? 2 : 0, esc_attr( $colors[ $l['id'] ] ?? 'var(--bh-s1)' ),
				esc_html( wp_date( 'D M j', strtotime( $day ) ) ), esc_html( $l['short_name'] ), esc_html( bh_money( $v ) )
			);
			$k++;
		}
		// Invisible full-height hit target with total.
		printf( '<rect x="%.1f" y="0" width="%.1f" height="%d" fill="transparent"><title>%s total: %s</title></rect>', $x, $bw, $h - $pad_b, esc_html( wp_date( 'D M j', strtotime( $day ) ) ), esc_html( bh_money( array_sum( $by_loc ) ) ) );
		if ( 0 === $i || $i === $n - 1 || ( $n > 14 && 0 === $i % 7 && $i < $n - 4 ) ) {
			printf( '<text x="%.1f" y="%d" class="bh-axis" text-anchor="middle">%s</text>', $x + $bw / 2, $h - 6, esc_html( wp_date( 'M j', strtotime( $day ) ) ) );
		}
		$i++;
	}
	echo '</svg><ul class="bh-legend">';
	foreach ( $locs as $l ) {
		printf( '<li><span class="bh-key" style="background:%s"></span>%s</li>', esc_attr( $colors[ $l['id'] ] ?? 'var(--bh-s1)' ), esc_html( $l['short_name'] ) );
	}
	echo '</ul>';
}

function bh_nice_max( $v ) {
	$exp  = pow( 10, floor( log10( max( 1, $v ) ) ) );
	$frac = $v / $exp;
	$nice = $frac <= 1 ? 1 : ( $frac <= 2 ? 2 : ( $frac <= 5 ? 5 : 10 ) );
	return $nice * $exp;
}

function bh_short_num( $v ) {
	if ( $v >= 1000000 ) {
		return round( $v / 1000000, 1 ) . 'M';
	}
	if ( $v >= 1000 ) {
		return round( $v / 1000, 1 ) . 'k';
	}
	return (string) round( $v );
}

/**
 * Weekday × hour heatmap (sequential single-hue).
 */
function bh_heatmap( $grid ) {
	if ( ! $grid ) {
		echo '<p class="bh-muted">No hourly data yet.</p>';
		return;
	}
	$hours = range( 10, 21 );
	$max   = 0;
	foreach ( $grid as $row ) {
		$max = max( $max, max( $row ?: array( 0 ) ) );
	}
	$days = array( 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun' );
	echo '<table class="bh-heat"><thead><tr><th></th>';
	foreach ( $hours as $h ) {
		echo '<th>' . esc_html( bh_fmt_time( sprintf( '%02d:00', $h ) ) ) . '</th>';
	}
	echo '</tr></thead><tbody>';
	foreach ( $days as $wd => $label ) {
		echo '<tr><th>' . esc_html( $label ) . '</th>';
		foreach ( $hours as $h ) {
			$v    = $grid[ $wd ][ $h ] ?? 0;
			$step = $max ? (int) min( 5, ceil( 5 * $v / $max ) ) : 0;
			printf( '<td class="bh-heat--%d" title="%s %s: %s orders avg">%s</td>', $step, esc_attr( $label ), esc_attr( bh_fmt_time( sprintf( '%02d:00', $h ) ) ), esc_attr( number_format( $v, 1 ) ), $v >= 1 ? esc_html( round( $v ) ) : '' );
		}
		echo '</tr>';
	}
	echo '</tbody></table>';
}

/* ------------------------------------------------------------- Catering */

function bh_leads_between( $from, $to ) {
	return get_posts(
		array(
			'post_type'      => 'bh_lead',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'date_query'     => array( array( 'after' => $from . ' 00:00:00', 'before' => $to . ' 23:59:59', 'inclusive' => true ) ),
		)
	);
}

function bh_dash_catering( $from, $to, $prev_from, $prev_to ) {
	$leads = bh_leads_between( $from, $to );
	$prev  = bh_leads_between( $prev_from, $prev_to );
	$by    = array_fill_keys( array_keys( bh_lead_statuses() ), 0 );
	$pipe  = 0;
	$won   = 0;
	$guests = 0;
	foreach ( $leads as $l ) {
		$s           = get_post_meta( $l->ID, '_bh_status', true ) ?: 'new';
		$by[ $s ]    = ( $by[ $s ] ?? 0 ) + 1;
		$val         = (float) get_post_meta( $l->ID, '_bh_quote', true ) ?: (float) get_post_meta( $l->ID, '_bh_estimate', true );
		$guests     += (int) get_post_meta( $l->ID, '_bh_guests', true );
		if ( in_array( $s, array( 'booked', 'completed' ), true ) ) {
			$won += $val;
		} elseif ( 'lost' !== $s ) {
			$pipe += $val;
		}
	}
	$closed = $by['booked'] + $by['completed'] + $by['lost'];
	$conv   = $closed ? 100 * ( $by['booked'] + $by['completed'] ) / $closed : 0;

	echo '<h2 class="bh-h2">Catering & events <small>website inquiries</small></h2>';
	bh_dash_tiles(
		array(
			array( 'New inquiries', number_format( count( $leads ) ), bh_delta_html( count( $leads ), count( $prev ) ) ),
			array( 'Guests requested', number_format( $guests ), count( $leads ) ? 'avg ' . round( $guests / count( $leads ) ) . ' per event' : '—' ),
			array( 'Open pipeline', bh_money( $pipe ), 'quoted or estimated value' ),
			array( 'Booked revenue', bh_money( $won ), 'booked + completed' ),
			array( 'Close rate', number_format( $conv, 0 ) . '%', $closed . ' decided inquiries' ),
		)
	);

	echo '<div class="bh-grid bh-grid--2"><div class="bh-card"><h3>Pipeline by status</h3><ul class="bh-funnel">';
	$max = max( 1, max( $by ) );
	foreach ( bh_lead_statuses() as $k => $label ) {
		printf( '<li><span class="bh-funnel__label">%s</span><span class="bh-funnel__bar"><span style="width:%d%%"></span></span><span class="bh-funnel__n">%d</span></li>', esc_html( $label ), (int) round( 100 * $by[ $k ] / $max ), (int) $by[ $k ] );
	}
	echo '</ul><p><a class="button" href="' . esc_url( admin_url( 'edit.php?post_type=bh_lead' ) ) . '">Work the leads →</a></p></div>';

	// Upcoming events.
	$upcoming = get_posts(
		array(
			'post_type'      => 'bh_lead',
			'posts_per_page' => 12,
			'meta_key'       => '_bh_event_date', // phpcs:ignore
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'meta_query'     => array( // phpcs:ignore
				array( 'key' => '_bh_event_date', 'value' => array( bh_now()->format( 'Y-m-d' ), bh_now()->modify( '+21 days' )->format( 'Y-m-d' ) ), 'compare' => 'BETWEEN', 'type' => 'DATE' ),
				array( 'key' => '_bh_status', 'value' => 'lost', 'compare' => '!=' ),
			),
		)
	);
	echo '<div class="bh-card"><h3>Events in the next 3 weeks</h3>';
	if ( ! $upcoming ) {
		echo '<p class="bh-muted">Nothing on the books from website inquiries.</p>';
	} else {
		echo '<table class="bh-table"><thead><tr><th>Date</th><th>Client</th><th class="num">Guests</th><th>Service</th><th>Status</th></tr></thead><tbody>';
		foreach ( $upcoming as $u ) {
			$s = get_post_meta( $u->ID, '_bh_status', true ) ?: 'new';
			printf(
				'<tr><td>%s</td><td><a href="%s">%s</a></td><td class="num">%d</td><td>%s</td><td><span class="bh-pill bh-pill--%s">%s</span></td></tr>',
				esc_html( wp_date( 'D M j', strtotime( get_post_meta( $u->ID, '_bh_event_date', true ) ) ) ),
				esc_url( get_edit_post_link( $u->ID ) ),
				esc_html( get_post_meta( $u->ID, '_bh_name', true ) ),
				(int) get_post_meta( $u->ID, '_bh_guests', true ),
				esc_html( get_post_meta( $u->ID, '_bh_service', true ) ),
				esc_attr( $s ),
				esc_html( bh_lead_statuses()[ $s ] ?? $s )
			);
		}
		echo '</tbody></table>';
	}
	echo '</div></div>';
}

/* -------------------------------------------------------------- Website */

function bh_dash_web( $from, $to, $prev_from, $prev_to, $locs ) {
	$now  = bh_tracking_totals( $from, $to );
	$prev = bh_tracking_totals( $prev_from, $prev_to );
	echo '<h2 class="bh-h2">Website actions <small>clicks that lead to revenue (staff logged in are excluded)</small></h2>';
	bh_dash_tiles(
		array(
			array( 'Order Online clicks', number_format( $now['order'] ), bh_delta_html( $now['order'], $prev['order'] ) ),
			array( 'Catering order clicks', number_format( $now['catering_order'] ), bh_delta_html( $now['catering_order'], $prev['catering_order'] ) ),
			array( 'Calls', number_format( $now['call'] ), bh_delta_html( $now['call'], $prev['call'] ) ),
			array( 'Directions', number_format( $now['directions'] ), bh_delta_html( $now['directions'], $prev['directions'] ) ),
			array( 'Delivery app clicks', number_format( $now['delivery'] ), bh_delta_html( $now['delivery'], $prev['delivery'] ) ),
		)
	);
	echo '<div class="bh-card"><h3>By location</h3><table class="bh-table"><thead><tr><th>Location</th>';
	$events = array( 'order', 'catering_order', 'call', 'directions', 'delivery' );
	foreach ( $events as $e ) {
		echo '<th class="num">' . esc_html( str_replace( array( ' clicks', ' app' ), '', bh_track_events()[ $e ] ) ) . '</th>';
	}
	echo '</tr></thead><tbody>';
	foreach ( array_merge( $locs, array( array( 'id' => 0, 'short_name' => 'Site-wide (no location)' ) ) ) as $l ) {
		$t = bh_tracking_totals( $from, $to, $l['id'] );
		echo '<tr><td>' . esc_html( $l['short_name'] ) . '</td>';
		foreach ( $events as $e ) {
			echo '<td class="num">' . esc_html( number_format( $t[ $e ] ) ) . '</td>';
		}
		echo '</tr>';
	}
	echo '</tbody></table><p class="bh-muted">Add a GA4 ID in Settings for full traffic analytics; these counts are first-party and cookie-free.</p></div>';
}

/* -------------------------------------------------------------- Health */

function bh_dash_health( $locs ) {
	$checks = array();
	foreach ( $locs as $l ) {
		$miss = array();
		foreach ( array( 'street' => 'address', 'zip' => 'ZIP', 'lat' => 'map coordinates', 'order_url' => 'Toast order link', 'toast_guid' => 'Toast GUID', 'maps_url' => 'Google Business Profile link' ) as $k => $label ) {
			if ( empty( $l[ $k ] ) ) {
				$miss[] = $label;
			}
		}
		if ( ! $l['hours'] ) {
			$miss[] = 'hours';
		}
		if ( ! $l['image_id'] ) {
			$miss[] = 'photo';
		}
		$checks[] = array( ! $miss, $l['short_name'] . ' location' . ( $miss ? ': add ' . implode( ', ', $miss ) : ' is complete' ), get_edit_post_link( $l['id'] ) );
	}
	$faqs     = count( bh_get_faqs() );
	$checks[] = array( $faqs >= 10, "$faqs FAQs published (aim for 10+ real customer questions — AI assistants quote these)", admin_url( 'edit.php?post_type=bh_faq' ) );
	$reviews  = count( bh_get_reviews( 50 ) );
	$checks[] = array( $reviews >= 3, "$reviews reviews featured on the site", admin_url( 'edit.php?post_type=bh_review' ) );
	$checks[] = array( (bool) get_theme_mod( 'custom_logo' ), 'Logo uploaded (used in Google/AI knowledge panels)', admin_url( 'customize.php' ) );
	$checks[] = array( (bool) bh_setting( 'google' ), 'Google Business Profile URL in Settings (connects the website entity to Google Maps)', admin_url( 'admin.php?page=bighams-settings' ) );
	$checks[] = array( count( bh_social_links() ) >= 3, count( bh_social_links() ) . ' social / review profiles linked (sameAs)', admin_url( 'admin.php?page=bighams-settings' ) );
	$checks[] = array( bh_is_production(), bh_is_production() ? 'Indexing enabled (production domain)' : 'Staging — indexing intentionally blocked', '' );
	$no_img   = count( get_posts( array( 'post_type' => 'bh_menu_item', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_query' => array( array( 'key' => '_thumbnail_id', 'compare' => 'NOT EXISTS' ) ) ) ) ); // phpcs:ignore
	$checks[] = array( $no_img < 10, "$no_img menu items without a photo (photos lift clicks from Google & AI results)", admin_url( 'edit.php?post_type=bh_menu_item' ) );

	$mc = get_option( 'bh_toast_menu_check' );
	echo '<div class="bh-grid bh-grid--2">';
	echo '<div class="bh-card"><h3>Search & AI readiness</h3><ul class="bh-checks">';
	$ok = 0;
	foreach ( $checks as $c ) {
		$ok += $c[0] ? 1 : 0;
		printf( '<li class="%s"><span aria-hidden="true">%s</span> <span class="screen-reader-text">%s</span>%s</li>', $c[0] ? 'ok' : 'todo', $c[0] ? '✔' : '!', $c[0] ? 'Done:' : 'To do:', $c[2] ? '<a href="' . esc_url( $c[2] ) . '">' . esc_html( $c[1] ) . '</a>' : esc_html( $c[1] ) );
	}
	echo '</ul><p><strong>' . (int) $ok . ' / ' . count( $checks ) . '</strong> complete.</p></div>';

	echo '<div class="bh-card"><h3>Menu prices vs Toast</h3>';
	if ( ! $mc ) {
		echo '<p class="bh-muted">Run <em>Check menu prices</em> in Settings once Toast is connected. The website menu is never overwritten — mismatches are listed here so you can fix them.</p>';
	} elseif ( ! $mc['mismatch'] ) {
		echo '<p>✔ All ' . (int) $mc['matched'] . ' matched items agree with Toast (checked ' . esc_html( wp_date( 'M j g:ia', $mc['time'] ) ) . ').</p>';
	} else {
		echo '<table class="bh-table"><thead><tr><th>Item</th><th class="num">Website</th><th class="num">Toast</th></tr></thead><tbody>';
		foreach ( $mc['mismatch'] as $m ) {
			printf( '<tr><td><a href="%s">%s</a></td><td class="num">%s</td><td class="num">%s</td></tr>', esc_url( get_edit_post_link( $m['id'] ) ), esc_html( $m['name'] ), esc_html( bh_money( $m['site'] ) ), esc_html( bh_money( $m['toast'] ) ) );
		}
		echo '</tbody></table>';
	}
	echo '</div></div>';
}

/* WP dashboard widget. */
add_action( 'wp_dashboard_setup', function () {
	wp_add_dashboard_widget( 'bh_summary', "Bigham's — last 7 days", function () {
		$from = bh_now()->modify( '-6 days' )->format( 'Y-m-d' );
		$to   = bh_now()->format( 'Y-m-d' );
		$t    = bh_toast_has_data() ? bh_toast_totals( $from, $to ) : null;
		$w    = bh_tracking_totals( $from, $to );
		$new  = count( get_posts( array( 'post_type' => 'bh_lead', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_bh_status', 'meta_value' => 'new' ) ) ); // phpcs:ignore
		echo '<ul>';
		if ( $t ) {
			echo '<li><strong>' . esc_html( bh_money( $t['net_sales'] ) ) . '</strong> net sales · ' . esc_html( number_format( $t['orders'] ) ) . ' orders</li>';
		}
		echo '<li><strong>' . (int) $new . '</strong> new catering inquiries waiting · <a href="' . esc_url( admin_url( 'edit.php?post_type=bh_lead' ) ) . '">view</a></li>';
		echo '<li>' . (int) $w['order'] . ' order clicks · ' . (int) $w['call'] . ' calls · ' . (int) $w['directions'] . ' directions</li>';
		echo '</ul><p><a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=bighams' ) ) . '">Open full dashboard</a></p>';
	} );
} );
