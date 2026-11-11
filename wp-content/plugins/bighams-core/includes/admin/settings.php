<?php
/**
 * Bigham's → Settings (business info, catering, integrations, Toast tools).
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', function () {
	add_submenu_page( 'bighams', "Bigham's Settings", 'Settings', 'manage_options', 'bighams-settings', 'bh_render_settings_page' );
}, 20 );

add_action( 'admin_init', function () {
	register_setting(
		'bighams_settings',
		'bighams_settings',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'bh_sanitize_settings',
		)
	);
} );

function bh_settings_sections() {
	return array(
		'Business' => array(
			'business_name' => 'Business name',
			'tagline'       => 'Tagline',
			'description'   => array( 'Business description (used by Google & AI assistants — write it as facts)', 'textarea' ),
			'founded'       => 'Year founded',
			'founder'       => 'Founder',
			'owners'        => 'Owners (comma separated)',
			'phone'         => 'Main phone',
			'email'         => 'Main email',
			'price_range'   => 'Price range ($, $$, $$$)',
			'cuisines'      => 'Cuisines (comma separated)',
			'production_domain' => 'Production domain (indexing is blocked on every other domain)',
		),
		'Catering & Events' => array(
			'catering_phone'     => 'Catering phone',
			'catering_email'     => 'Catering email',
			'catering_notify'    => 'Send new inquiry alerts to (comma separated emails)',
			'catering_order_url' => array( 'Toast catering ordering URL (default for all locations)', 'url' ),
			'lead_webhook_url'   => array( 'CRM webhook URL (optional — e.g. GoHighLevel inbound webhook; receives each inquiry as JSON)', 'url' ),
		),
		'Social & Profiles' => array(
			'facebook'  => array( 'Facebook URL', 'url' ),
			'instagram' => array( 'Instagram URL', 'url' ),
			'google'    => array( 'Google Business Profile URL', 'url' ),
			'yelp'      => array( 'Yelp URL', 'url' ),
			'tiktok'    => array( 'TikTok URL', 'url' ),
			'jobs_url'  => array( 'Jobs / careers URL', 'url' ),
			'gift_card_url' => array( 'Gift cards URL (Toast)', 'url' ),
			'rating_value' => 'Google rating (e.g. 4.6) — optional',
			'rating_count' => 'Google review count — optional',
		),
		'Site Banner' => array(
			'announcement'     => 'Announcement bar text (leave blank to hide)',
			'announcement_url' => array( 'Announcement link', 'url' ),
		),
		'Analytics' => array(
			'ga4_id' => 'Google Analytics 4 Measurement ID (G-XXXXXXX) — optional',
		),
		'Toast POS' => array(
			'toast_env'           => array( 'Environment', 'select', array( 'production' => 'Production', 'sandbox' => 'Sandbox' ) ),
			'toast_client_id'     => 'Client ID',
			'toast_client_secret' => array( 'Client secret', 'password' ),
			'toast_sync_menu'     => array( 'Check website menu prices against Toast daily', 'checkbox' ),
		),
	);
}

function bh_sanitize_settings( $in ) {
	$in  = (array) $in;
	$old = (array) get_option( 'bighams_settings', array() );
	$out = array();
	foreach ( bh_settings_sections() as $fields ) {
		foreach ( $fields as $key => $def ) {
			$type = is_array( $def ) ? $def[1] : 'text';
			$val  = $in[ $key ] ?? '';
			switch ( $type ) {
				case 'textarea':
					$out[ $key ] = sanitize_textarea_field( $val );
					break;
				case 'url':
					$out[ $key ] = esc_url_raw( $val );
					break;
				case 'checkbox':
					$out[ $key ] = $val ? '1' : '0';
					break;
				case 'password':
					// Blank = keep existing secret.
					$out[ $key ] = '' === $val ? ( $old[ $key ] ?? '' ) : sanitize_text_field( $val );
					break;
				default:
					$out[ $key ] = sanitize_text_field( $val );
			}
		}
	}
	return $out;
}

function bh_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$opts = wp_parse_args( (array) get_option( 'bighams_settings', array() ), bh_default_settings() );
	?>
	<div class="wrap bh-admin">
		<h1>Bigham's Settings</h1>
		<?php settings_errors(); ?>
		<?php if ( isset( $_GET['bh_msg'] ) ) : // phpcs:ignore ?>
			<div class="notice notice-info is-dismissible"><p><?php echo esc_html( wp_unslash( $_GET['bh_msg'] ) ); // phpcs:ignore ?></p></div>
		<?php endif; ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'bighams_settings' ); ?>
			<?php foreach ( bh_settings_sections() as $section => $fields ) : ?>
				<h2><?php echo esc_html( $section ); ?></h2>
				<?php if ( 'Toast POS' === $section ) : ?>
					<p class="description" style="max-width:760px">Get credentials in <strong>Toast Web → Integrations → Toast API access → Manage credentials</strong> (Standard API access). Create one client with read access to <em>orders, menus, config, restaurants</em> covering all three locations, then paste each location's <strong>Restaurant GUID</strong> into its Location page. You can also define <code>BH_TOAST_CLIENT_ID</code> / <code>BH_TOAST_CLIENT_SECRET</code> in wp-config.php instead.</p>
				<?php endif; ?>
				<table class="form-table"><tbody>
				<?php
				foreach ( $fields as $key => $def ) :
					$label = is_array( $def ) ? $def[0] : $def;
					$type  = is_array( $def ) ? $def[1] : 'text';
					$name  = 'bighams_settings[' . $key . ']';
					$val   = $opts[ $key ] ?? '';
					$const = ( 'toast_client_id' === $key && defined( 'BH_TOAST_CLIENT_ID' ) ) || ( 'toast_client_secret' === $key && defined( 'BH_TOAST_CLIENT_SECRET' ) );
					?>
					<tr><th scope="row"><label for="bh-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th><td>
					<?php
					if ( $const ) {
						echo '<em>Set in wp-config.php</em>';
					} elseif ( 'textarea' === $type ) {
						printf( '<textarea id="bh-%1$s" name="%2$s" rows="4" class="large-text">%3$s</textarea>', esc_attr( $key ), esc_attr( $name ), esc_textarea( $val ) );
					} elseif ( 'checkbox' === $type ) {
						printf( '<input type="checkbox" id="bh-%1$s" name="%2$s" value="1"%3$s>', esc_attr( $key ), esc_attr( $name ), checked( $val, '1', false ) );
					} elseif ( 'select' === $type ) {
						printf( '<select id="bh-%1$s" name="%2$s">', esc_attr( $key ), esc_attr( $name ) );
						foreach ( $def[2] as $k => $l ) {
							printf( '<option value="%s"%s>%s</option>', esc_attr( $k ), selected( $val, $k, false ), esc_html( $l ) );
						}
						echo '</select>';
					} elseif ( 'password' === $type ) {
						printf( '<input type="password" id="bh-%1$s" name="%2$s" value="" class="regular-text" autocomplete="new-password" placeholder="%3$s">', esc_attr( $key ), esc_attr( $name ), $val ? '•••••••• (saved — leave blank to keep)' : '' );
					} else {
						printf( '<input type="%4$s" id="bh-%1$s" name="%2$s" value="%3$s" class="regular-text">', esc_attr( $key ), esc_attr( $name ), esc_attr( $val ), 'url' === $type ? 'url' : 'text' );
					}
					?>
					</td></tr>
				<?php endforeach; ?>
				</tbody></table>
			<?php endforeach; ?>
			<?php submit_button(); ?>
		</form>

		<h2>Toast tools</h2>
		<?php
		$last = get_option( 'bh_toast_last_sync' );
		if ( $last ) {
			echo '<p>Last sync: ' . esc_html( wp_date( 'M j, Y g:ia', $last['time'] ) ) . ' (business day ' . esc_html( $last['day'] ) . ') — ' . esc_html( implode( ' · ', (array) $last['log'] ) ) . '</p>';
		}
		$next = wp_next_scheduled( 'bh_toast_sync_event' );
		if ( $next ) {
			echo '<p>Next automatic sync: ' . esc_html( wp_date( 'M j g:ia', $next ) ) . '</p>';
		}
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:flex;gap:8px;flex-wrap:wrap">
			<input type="hidden" name="action" value="bh_toast_tool">
			<?php wp_nonce_field( 'bh_toast_tool' ); ?>
			<button class="button" name="tool" value="test">Test connection</button>
			<button class="button" name="tool" value="sync">Sync today + yesterday now</button>
			<button class="button" name="tool" value="backfill30">Backfill 30 days</button>
			<button class="button" name="tool" value="backfill90">Backfill 90 days</button>
			<button class="button" name="tool" value="menu">Check menu prices</button>
		</form>
		<h2>AI search files</h2>
		<p><a href="<?php echo esc_url( home_url( '/llms.txt' ) ); ?>" target="_blank">/llms.txt</a> · <a href="<?php echo esc_url( home_url( '/llms-full.txt' ) ); ?>" target="_blank">/llms-full.txt</a> · <a href="<?php echo esc_url( home_url( '/robots.txt' ) ); ?>" target="_blank">/robots.txt</a> · <a href="<?php echo esc_url( home_url( '/wp-sitemap.xml' ) ); ?>" target="_blank">sitemap</a> · <a href="https://search.google.com/test/rich-results?url=<?php echo rawurlencode( home_url( '/' ) ); ?>" target="_blank">Rich Results Test</a> · <a href="https://validator.schema.org/#url=<?php echo rawurlencode( home_url( '/' ) ); ?>" target="_blank">Schema validator</a></p>
		<p class="description">These rebuild automatically when you edit menu items, locations, packages or FAQs.</p>
	</div>
	<?php
}

add_action( 'admin_post_bh_toast_tool', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed' );
	}
	check_admin_referer( 'bh_toast_tool' );
	$tool   = sanitize_key( $_POST['tool'] ?? '' );
	$client = new BH_Toast_Client();
	$msg    = '';
	switch ( $tool ) {
		case 'test':
			$tok = $client->token( true );
			if ( is_wp_error( $tok ) ) {
				$msg = 'Toast: ' . $tok->get_error_message();
				break;
			}
			$parts = array( 'Authenticated with Toast.' );
			foreach ( bh_get_locations() as $l ) {
				if ( ! $l['toast_guid'] ) {
					$parts[] = $l['short_name'] . ': no GUID set';
					continue;
				}
				$r       = $client->restaurant( $l['toast_guid'] );
				$parts[] = $l['short_name'] . ': ' . ( is_wp_error( $r ) ? 'ERROR ' . $r->get_error_message() : 'OK (' . ( $r['general']['name'] ?? 'connected' ) . ')' );
			}
			$msg = implode( ' · ', $parts );
			break;
		case 'sync':
			$msg = 'Synced: ' . implode( ' · ', bh_toast_sync_day( bh_now()->modify( '-1 day' ) ) ) . ' / ' . implode( ' · ', bh_toast_sync_day( bh_now() ) );
			break;
		case 'backfill30':
		case 'backfill90':
			$n   = bh_toast_schedule_backfill( 'backfill90' === $tool ? 90 : 30 );
			$msg = "Backfill of $n days queued — it runs in the background (about 1 day every 20 seconds while the site gets traffic).";
			break;
		case 'menu':
			$r   = bh_toast_check_menu();
			$msg = is_wp_error( $r ) ? $r->get_error_message() : sprintf( 'Toast items: %d · matched to website: %d · price mismatches: %d (see Dashboard).', $r['toast_items'], $r['matched'], count( $r['mismatch'] ) );
			break;
	}
	wp_safe_redirect( add_query_arg( 'bh_msg', rawurlencode( $msg ), admin_url( 'admin.php?page=bighams-settings' ) ) );
	exit;
} );

/* GA4 (only if configured and no other plugin handles it). */
add_action( 'wp_head', function () {
	$id = bh_setting( 'ga4_id' );
	if ( ! $id || ! preg_match( '/^G-[A-Z0-9]+$/', $id ) || is_user_logged_in() ) {
		return;
	}
	printf( "<script async src=\"https://www.googletagmanager.com/gtag/js?id=%1\$s\"></script>\n<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','%1\$s');</script>\n", esc_attr( $id ) );
}, 1 );
