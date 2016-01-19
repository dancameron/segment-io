<?php

/**
 * @package EDD_Segment
 * @version 0.1
 */

/*
 * Plugin Name: EDD Segment
 * Plugin URI: https://sproutapps.co/edd-segment/
 * Description: Hooks up your EDD run site with Segment and/or Customer.io. Learn more at <a href="https://sproutapps.co/edd-segment-io">Sprout Apps</a>.
 * Author: Sprout Apps
 * Version: 1.1
 * Author URI: https://sproutapps.co
 * Text Domain: sprout-apps
 * Domain Path: languages
*/


/**
 * SI directory
 */
define( 'EDD_SEGMENT_PATH', WP_PLUGIN_DIR . '/' . basename( dirname( __FILE__ ) ) );
/**
 * Plugin File
 */
define( 'EDD_SEGMENT_PLUGIN_FILE', __FILE__ );

/**
 * SI URL
 */
define( 'EDD_SEGMENT_URL', plugins_url( '', __FILE__ ) );
/**
 * URL to resources directory
 */
define( 'EDD_SEGMENT_RESOURCES', plugins_url( 'resources/', __FILE__ ) );


/**
 * Load plugin
 */
require_once EDD_SEGMENT_PATH . '/load.php';

/**
 * do_action when plugin is activated.
 * @package EDD_Segment
 * @ignore
 */
register_activation_hook( __FILE__, 'edd_segment_plugin_activated' );
function edd_segment_plugin_activated() {
	do_action( 'edd_segment_plugin_activation_hook' );
}
/**
 * do_action when plugin is deactivated.
 * @package EDD_Segment
 * @ignore
 */
register_deactivation_hook( __FILE__, 'edd_segment_plugin_deactivated' );
function edd_segment_plugin_deactivated() {
	do_action( 'edd_segment_plugin_deactivation_hook' );
}

function edd_segment_deactivate_plugin() {
	if ( is_admin() && ( !defined( 'DOING_AJAX' ) || !DOING_AJAX ) ) {
		require_once ABSPATH.'/wp-admin/includes/plugin.php';
		deactivate_plugins( __FILE__ );
	}
}