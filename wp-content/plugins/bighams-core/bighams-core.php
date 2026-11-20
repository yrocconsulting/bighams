<?php
/**
 * Plugin Name:       Bigham's Core
 * Description:       Locations, menu, catering, structured data (SEO + AI search), Toast POS sync and the Bigham's business dashboard. Pairs with the Bigham's theme.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            Yroc Consulting
 * Text Domain:       bighams
 *
 * Content and business logic live here (not in the theme) so that a future
 * redesign never loses menu, location, lead or sales data.
 */

defined( 'ABSPATH' ) || exit;

define( 'BH_CORE_VERSION', '1.0.0' );
define( 'BH_CORE_FILE', __FILE__ );
define( 'BH_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'BH_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once BH_CORE_DIR . 'includes/helpers.php';
require_once BH_CORE_DIR . 'includes/post-types.php';
require_once BH_CORE_DIR . 'includes/meta-boxes.php';
require_once BH_CORE_DIR . 'includes/data.php';
require_once BH_CORE_DIR . 'includes/schema.php';
require_once BH_CORE_DIR . 'includes/seo.php';
require_once BH_CORE_DIR . 'includes/catering-form.php';
require_once BH_CORE_DIR . 'includes/tracking.php';
require_once BH_CORE_DIR . 'includes/toast/class-toast-client.php';
require_once BH_CORE_DIR . 'includes/toast/toast-sync.php';
require_once BH_CORE_DIR . 'includes/toast/toast-menu.php';
require_once BH_CORE_DIR . 'includes/admin/settings.php';
require_once BH_CORE_DIR . 'includes/admin/dashboard.php';
require_once BH_CORE_DIR . 'includes/seed.php';
require_once BH_CORE_DIR . 'includes/image-import.php';

register_activation_hook( __FILE__, 'bh_core_activate' );
register_deactivation_hook( __FILE__, 'bh_core_deactivate' );

/**
 * Activation: tables, CPTs, starter content, cron.
 */
function bh_core_activate() {
	bh_register_post_types();
	bh_tracking_install_table();
	bh_toast_install_tables();
	bh_seed_content();
	bh_toast_schedule();
	flush_rewrite_rules();
}

function bh_core_deactivate() {
	wp_clear_scheduled_hook( 'bh_toast_sync_event' );
	flush_rewrite_rules();
}

/**
 * Run table upgrades when the plugin is updated by deploy (activation hook
 * does not fire on a file-sync deploy).
 */
/**
 * One-time settings updates for sites that already saved Settings.
 */
add_action( 'init', function () {
	if ( get_option( 'bh_migr_desserts_merge' ) ) {
		return;
	}
	$opts = get_option( 'bighams_settings' );
	if ( is_array( $opts ) && isset( $opts['toast_merge'] ) && false === stripos( $opts['toast_merge'], 'Desserts' ) ) {
		$opts['toast_merge'] = trim( $opts['toast_merge'] . "\nDesserts = Peach Cobbler, Cherry Cobbler, Apple Cobbler, Pecan Cobbler, Blackberry Cobbler, Other Sweets" );
		update_option( 'bighams_settings', $opts );
	}
	update_option( 'bh_migr_desserts_merge', 1, false );
}, 1 );

add_action( 'init', function () {
	if ( get_option( 'bh_migr_toast_inquiry' ) ) {
		return;
	}
	$anchors = array( '82nd-street' => '#82nd-st-indiana', '19th-street-quaker' => '#19th-st-quaker', 'milwaukee-avenue' => '#50th-milwaukee' );
	foreach ( $anchors as $slug => $hash ) {
		$loc = get_page_by_path( $slug, OBJECT, 'bh_location' );
		if ( $loc && ! get_post_meta( $loc->ID, '_bh_catering_url', true ) ) {
			update_post_meta( $loc->ID, '_bh_catering_url', 'https://www.toasttab.com/catering/locations/8a09b080-324a-4a97-b7d3-14270d2c8f55' . $hash );
		}
	}
	update_option( 'bh_migr_toast_inquiry', 1, false );
}, 2 );

add_action( 'plugins_loaded', function () {
	if ( get_option( 'bh_core_db_version' ) !== BH_CORE_VERSION ) {
		bh_tracking_install_table();
		bh_toast_install_tables();
		bh_toast_schedule();
		update_option( 'bh_core_db_version', BH_CORE_VERSION );
	}
} );
