<?php
/**
 * Plugin Name:       Slotwise Appointments
 * Plugin URI:        https://tajamulh193.github.io/tajamulhussain/projects/slotwise-booking-plugin/
 * Description:       Online appointment booking for clinics, salons and consultants. Services with durations, opening hours, live slot availability, double-booking protection and email confirmations.
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            Tujummal Hussain
 * License:           GPL v2 or later
 * Text Domain:       slotwise
 *
 * @package Slotwise
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SLOTWISE_VERSION', '1.0.0' );
define( 'SLOTWISE_FILE', __FILE__ );
define( 'SLOTWISE_DIR', plugin_dir_path( __FILE__ ) );
define( 'SLOTWISE_URL', plugin_dir_url( __FILE__ ) );

require_once SLOTWISE_DIR . 'includes/class-slotwise-settings.php';
require_once SLOTWISE_DIR . 'includes/class-slotwise-post-types.php';
require_once SLOTWISE_DIR . 'includes/class-slotwise-availability.php';
require_once SLOTWISE_DIR . 'includes/class-slotwise-rest.php';
require_once SLOTWISE_DIR . 'includes/class-slotwise-shortcode.php';
require_once SLOTWISE_DIR . 'includes/class-slotwise-admin.php';

add_action( 'init', array( 'Slotwise_Post_Types', 'register' ) );
add_action( 'rest_api_init', array( 'Slotwise_REST', 'register_routes' ) );
add_action( 'init', array( 'Slotwise_Shortcode', 'register' ) );

if ( is_admin() ) {
	Slotwise_Admin::init();
}

/**
 * Activation: register post types so rewrite rules include them, then flush.
 */
function slotwise_activate() {
	Slotwise_Post_Types::register();
	if ( false === get_option( Slotwise_Settings::OPTION ) ) {
		add_option( Slotwise_Settings::OPTION, Slotwise_Settings::defaults() );
	}
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'slotwise_activate' );

/**
 * Deactivation: tidy rewrite rules. Data is kept until the plugin is deleted.
 */
function slotwise_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'slotwise_deactivate' );
