<?php
/**
 * wp bighams launch-check — pre/post launch sanity checks for the live site.
 */

defined( 'ABSPATH' ) || exit;

function bh_launch_checks() {
	$home   = home_url( '/' );
	$host   = (string) wp_parse_url( $home, PHP_URL_HOST );
	$prod   = bh_setting( 'production_domain', 'bighamsbbq.com' );
	$checks = array();
	$add    = function ( $ok, $label, $fix = '' ) use ( &$checks ) {
		$checks[] = array( 'ok' => (bool) $ok, 'label' => $label, 'fix' => $fix );
	};

	$add( $host === $prod || $host === 'www.' . $prod, "Site address is $host", "Run: wp search-replace 'https://bighams.yroc.host' 'https://$prod' --all-tables, then confirm Settings → General." );
	$add( 0 === strpos( $home, 'https://' ), 'Site address uses https', 'Install the SSL certificate in SiteGround (Security → SSL Manager), then update the site address to https.' );
	$add( bh_is_production(), 'Production mode on (search engines allowed by the Bigham\'s plugin)', "Set Bigham's → Settings → Production domain to the live domain." );
	$add( '1' === (string) get_option( 'blog_public' ), 'WordPress lets search engines index the site', 'Settings → Reading → untick "Discourage search engines from indexing this site" (or: wp option update blog_public 1).' );
	$add( '/%postname%/' === get_option( 'permalink_structure' ), 'Pretty permalinks', 'Settings → Permalinks → Post name.' );
	$add( 'Bigham\'s Smokehouse' === html_entity_decode( get_bloginfo( 'name' ), ENT_QUOTES ), 'Site title is "Bigham\'s Smokehouse"', 'Settings → General → Site Title.' );
	$add( (int) get_option( 'page_on_front' ) > 0, 'Homepage is set', 'Settings → Reading → A static page → Home.' );
	$smtp = defined( 'WPMS_PLUGIN_VER' ) || class_exists( 'FluentMail\\App\\Hooks\\Handlers\\AdminMenuHandler' ) || function_exists( 'post_smtp_start' ) || defined( 'SG_SMTP_VERSION' );
	$add( $smtp, 'SMTP plugin for reliable email (WP Mail SMTP, FluentSMTP or Post SMTP)', 'Install an SMTP plugin with the client\'s Microsoft 365 mailbox so alerts and password emails arrive.' );
	$add( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON, 'Real server cron (WP-Cron disabled)', 'SiteGround → Devs → Cron Jobs: every 15 min `wp cron event run --due-now`, then define DISABLE_WP_CRON in wp-config.php.' );
	$client = new BH_Toast_Client();
	$add( $client->is_configured(), 'Toast credentials present', "Bigham's → Settings → Toast POS." );
	$last = get_option( 'bh_toast_last_sync' );
	$add( $last && time() - (int) $last['time'] < 3 * HOUR_IN_SECONDS, 'Toast synced in the last 3 hours', 'Settings → Toast tools → Sync now; check cron.' );
	$no_guid = array_filter( bh_get_locations(), fn( $l ) => ! $l['toast_guid'] );
	$add( ! $no_guid, 'Every location has a Toast GUID', 'Add it on the Location edit screen.' );
	$shared = array_filter( bh_get_locations(), fn( $l ) => bh_phone_e164( $l['phone'] ) === bh_phone_e164( bh_setting( 'phone' ) ) );
	$add( count( $shared ) < 2, 'Each location has its own phone number', 'Enter each store\'s Google Business Profile number on its Location.' );
	$no_maps = array_filter( bh_get_locations(), fn( $l ) => ! $l['lat'] || false === strpos( $l['maps_url'], 'google' ) || false !== strpos( $l['maps_url'], 'search/?api' ) );
	$add( ! $no_maps, 'Every location has map coordinates and a Google Business Profile link', 'Add them on each Location (improves local and AI search).' );
	$add( (bool) bh_setting( 'ga4_id' ), 'Google Analytics 4 ID set', "Bigham's → Settings → Analytics (optional)." );
	$tests = get_posts( array( 'post_type' => 'bh_lead', 'posts_per_page' => -1, 'fields' => 'ids', 's' => 'Test' ) );
	$add( ! $tests, 'No test catering leads left', 'Delete them under Bigham\'s → Catering Leads.' );
	$add( 'https://bighamsbbq.com' !== bh_setting( 'jobs_url' ) && (bool) bh_setting( 'jobs_url' ), 'Jobs link set', "Bigham's → Settings → Social & Profiles." );
	return $checks;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command(
		'bighams launch-check',
		function () {
			$todo = 0;
			foreach ( bh_launch_checks() as $c ) {
				WP_CLI::line( ( $c['ok'] ? '  OK   ' : '  TODO ' ) . $c['label'] . ( $c['ok'] ? '' : "\n         → " . $c['fix'] ) );
				$todo += $c['ok'] ? 0 : 1;
			}
			$todo ? WP_CLI::warning( "$todo item(s) to do." ) : WP_CLI::success( 'Ready.' );
		},
		array( 'shortdesc' => 'Check the live site is set up correctly (domain, SSL, indexing, email, cron, Toast).' )
	);
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command(
		'bighams go-live',
		function ( $args, $assoc ) {
			$from = untrailingslashit( $assoc['from'] ?? 'https://bighams.yroc.host' );
			$to   = untrailingslashit( $assoc['to'] ?? 'https://bighamsbbq.com' );
			$dry  = ! empty( $assoc['dry-run'] );
			if ( ! preg_match( '#^https://[a-z0-9.-]+$#', $to ) ) {
				WP_CLI::error( '--to must look like https://bighamsbbq.com' );
			}
			WP_CLI::line( "Replacing $from → $to in the database" . ( $dry ? ' (dry run)' : '' ) . '…' );
			WP_CLI::runcommand( sprintf( 'search-replace %s %s --all-tables --precise --skip-columns=guid --report-changed-only%s', escapeshellarg( $from ), escapeshellarg( $to ), $dry ? ' --dry-run' : '' ) );
			// Also the protocol-less form (e.g. inside serialized JSON or CSS).
			WP_CLI::runcommand( sprintf( 'search-replace %s %s --all-tables --precise --skip-columns=guid --report-changed-only%s', escapeshellarg( '//' . wp_parse_url( $from, PHP_URL_HOST ) ), escapeshellarg( '//' . wp_parse_url( $to, PHP_URL_HOST ) ), $dry ? ' --dry-run' : '' ) );
			if ( $dry ) {
				WP_CLI::success( 'Dry run only. Nothing changed.' );
				return;
			}
			$opts                      = (array) get_option( 'bighams_settings', array() );
			$opts['production_domain'] = preg_replace( '#^www\.#', '', (string) wp_parse_url( $to, PHP_URL_HOST ) );
			update_option( 'bighams_settings', $opts );
			update_option( 'blog_public', '1' );
			if ( 'My WordPress' === get_option( 'blogname' ) ) {
				update_option( 'blogname', "Bigham's Smokehouse" );
			}
			foreach ( array( 'bh_llms_txt', 'bh_llms_txt_full' ) as $t ) {
				delete_transient( $t );
			}
			flush_rewrite_rules();
			wp_cache_flush();
			WP_CLI::success( "Live settings applied for $to. Run: wp bighams launch-check" );
		},
		array(
			'shortdesc' => 'Switch the site from the staging address to the live domain (search-replace, indexing on, caches cleared).',
			'synopsis'  => array(
				array( 'type' => 'assoc', 'name' => 'from', 'optional' => true, 'default' => 'https://bighams.yroc.host' ),
				array( 'type' => 'assoc', 'name' => 'to', 'optional' => true, 'default' => 'https://bighamsbbq.com' ),
				array( 'type' => 'flag', 'name' => 'dry-run', 'optional' => true ),
			),
		)
	);
}
