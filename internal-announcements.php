<?php
/**
 * Plugin Name:       Internal Announcements
 * Description:       Company announcements / news feed with categories, pinning, and per-user read tracking.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * License:           GPL-2.0-or-later
 * Text Domain:       internal-announcements
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'IA_VERSION',     '1.0.0' );
define( 'IA_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'IA_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'IA_TABLE_READS', 'announcement_reads' ); // $wpdb->prefix prepended at runtime.

require_once IA_PLUGIN_DIR . 'includes/class-announcement-cpt.php';
require_once IA_PLUGIN_DIR . 'includes/class-announcement-read-tracker.php';
require_once IA_PLUGIN_DIR . 'includes/class-announcement-shortcode.php';
require_once IA_PLUGIN_DIR . 'includes/class-announcement-ajax.php';
require_once IA_PLUGIN_DIR . 'includes/class-announcement-admin.php';

// ---------------------------------------------------------------------------
// Activation
// ---------------------------------------------------------------------------

register_activation_hook( __FILE__, 'ia_activate' );

function ia_activate(): void {
	// Register CPT + taxonomy so seed_default_terms() can use them.
	Announcement_CPT::register();
	Announcement_CPT::seed_default_terms();
	Announcement_Read_Tracker::create_table();
}

// ---------------------------------------------------------------------------
// Boot
// ---------------------------------------------------------------------------

add_action( 'init', array( 'Announcement_CPT', 'register' ) );

add_action( 'plugins_loaded', function (): void {
	new Announcement_Shortcode();
	new Announcement_Ajax();
	new Announcement_Admin();
} );
