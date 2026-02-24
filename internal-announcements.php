<?php
/**
 * Plugin Name:       Internal Announcements
 * Plugin URI:        https://github.com/edwinkrisnha/Internal-Announcements-Wordpress-Plugin
 * Description:       Company announcements / news feed with categories, pinning, and a "New" badge for recent posts.
 * Version:           1.1.0
 * Author:            Edwin Krisnha
 * Author URI:        https://github.com/edwinkrisnha
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       internal-announcements
 * Requires at least: 6.0
 * Requires PHP:      8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'IA_VERSION',    '1.1.0' );
define( 'IA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'IA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once IA_PLUGIN_DIR . 'includes/class-announcement-cpt.php';
require_once IA_PLUGIN_DIR . 'includes/class-announcement-shortcode.php';
require_once IA_PLUGIN_DIR . 'includes/class-announcement-admin.php';

// ---------------------------------------------------------------------------
// Activation
// ---------------------------------------------------------------------------

register_activation_hook( __FILE__, 'ia_activate' );

function ia_activate(): void {
	// Register CPT + taxonomy so seed_default_terms() can use them.
	Announcement_CPT::register();
	Announcement_CPT::seed_default_terms();
}

// ---------------------------------------------------------------------------
// Boot
// ---------------------------------------------------------------------------

add_action( 'init', array( 'Announcement_CPT', 'register' ) );

add_action( 'plugins_loaded', function (): void {
	new Announcement_Shortcode();
	new Announcement_Admin();
} );
