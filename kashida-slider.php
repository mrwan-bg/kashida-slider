<?php
/**
 * Plugin Name:       Kashida Slider
 * Plugin URI:        https://kashida.ly/
 * Description:       A lightweight visual slider and carousel builder by Kashida Media Services. Create custom slides and responsive carousels from blog posts, selected WordPress pages, WooCommerce products, and portfolio projects. Customize image shapes, spacing, borders, and responsive layouts.
 * Version:           1.4.16
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Mrwan elGobee
 * Author URI:        https://kashida.ly/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       kashida-slider
 * Domain Path:       /languages
 *
 * @package KashidaSlider
 */

defined( 'ABSPATH' ) || exit;

define( 'KS_VERSION', '1.4.16' );
define( 'KS_FILE', __FILE__ );
define( 'KS_DIR', plugin_dir_path( __FILE__ ) );
define( 'KS_URL', plugin_dir_url( __FILE__ ) );
define( 'KS_SCHEMA_VERSION', 1 );

require_once KS_DIR . 'includes/class-ks-schema.php';
require_once KS_DIR . 'includes/class-ks-storage.php';
require_once KS_DIR . 'includes/class-ks-source.php';
require_once KS_DIR . 'includes/class-ks-renderer.php';
if ( is_admin() ) {
	require_once KS_DIR . 'includes/class-ks-help.php';
	require_once KS_DIR . 'includes/class-ks-admin.php';
}

/**
 * Required capability for managing sliders.
 *
 * @return string
 */
function ks_capability() {
	return apply_filters( 'ks_capability', 'manage_options' );
}

add_action( 'init', array( 'KS_Storage', 'register' ) );
add_action( 'init', array( 'KS_Source', 'hooks' ) );
add_action( 'init', array( 'KS_Renderer', 'hooks' ) );
if ( is_admin() ) {
	add_action( 'init', array( 'KS_Admin', 'hooks' ) );
}

register_activation_hook( __FILE__, 'ks_activate' );
function ks_activate() {
	if ( version_compare( PHP_VERSION, '7.4', '<' ) || version_compare( get_bloginfo( 'version' ), '6.0', '<' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die( esc_html__( 'Kashida Slider requires PHP 7.4+ and WordPress 6.0+.', 'kashida-slider' ) );
	}
	KS_Storage::register();
	add_option( 'ks_schema_version', KS_SCHEMA_VERSION, '', false );
}
