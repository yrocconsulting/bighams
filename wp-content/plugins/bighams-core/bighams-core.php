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
add_action( 'plugins_loaded', function () {
	if ( get_option( 'bh_core_db_version' ) !== BH_CORE_VERSION ) {
		bh_tracking_install_table();
		bh_toast_install_tables();
		bh_toast_schedule();
		update_option( 'bh_core_db_version', BH_CORE_VERSION );
	}
} );
