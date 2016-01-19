<?php

/**
 * Load the SI application
 * (function called at the bottom of this page)
 * 
 * @package EDD_Segment
 * @return void
 */
function edd_segment_load() {
	if ( class_exists( 'EDD_Segment' ) ) {
		edd_segment_deactivate_plugin();
		return; // already loaded, or a name collision
	}

	do_action( 'edd_segment_preload' );

	//////////
	// Load //
	//////////

	// Master class
	require_once EDD_SEGMENT_PATH.'/Segment.php';

	// controllers
	require_once EDD_SEGMENT_PATH.'/controllers/_Controller.php';
	EDD_Segment_Controller::init();

	if ( !class_exists( 'SA_Settings_API' ) ) {
		require_once EDD_SEGMENT_PATH.'/controllers/_Settings.php';
		SA_Settings_API::init();
	}

	require_once EDD_SEGMENT_PATH.'/controllers/Settings.php';
	EDD_Segment_Settings::init();

	require_once EDD_SEGMENT_PATH.'/controllers/Identity.php';
	EDD_Segment_Identity::init();

	require_once EDD_SEGMENT_PATH.'/controllers/integrations/Segment_API.php';
	EDD_Segment_Tracker::init();

	require_once EDD_SEGMENT_PATH.'/controllers/integrations/CustomerIO_API.php';
	EDD_CustomerIO_Tracker::init();

	if ( file_exists( EDD_SEGMENT_PATH.'/controllers/add-ons/AJAX_Callbacks.php' ) ) {
		require_once EDD_SEGMENT_PATH.'/controllers/add-ons/AJAX_Callbacks.php';
		EDD_Segment_AJAX_Callbacks::init();
	}

	if ( function_exists( 'EDD_Payments_Query' ) ) {
		require_once EDD_SEGMENT_PATH.'/controllers/add-ons/EDD_Hooks.php';
		EDD_Segment_Hooks::init();
	}

	require_once EDD_SEGMENT_PATH.'/controllers/Updates.php';
	EDD_Segment_Updates::init();

	require_once EDD_SEGMENT_PATH.'/template-tags/edd-segment.php';
	do_action( 'edd_segment_loaded' );
}

/**
 * Minimum supported version of WordPress
 */
define( 'EDD_SEGMENT_SUPPORTED_WP_VERSION', version_compare( get_bloginfo( 'version' ), '3.7', '>=' ) );
/**
 * Minimum supported version of PHP
 */
define( 'EDD_SEGMENT_SUPPORTED_PHP_VERSION', version_compare( phpversion(), '5.2.4', '>=' ) );

/**
 * Compatibility check
 */
if ( EDD_SEGMENT_SUPPORTED_WP_VERSION && EDD_SEGMENT_SUPPORTED_PHP_VERSION ) {
	edd_segment_load();
} else {
	/**
	 * Disable SI and add fail notices if compatibility check fails
	 * @return string inserted within the WP dashboard
	 */
	edd_segment_deactivate_plugin();
	add_action( 'admin_head', 'edd_segment_fail_notices' );
	function edd_segment_fail_notices() {
		if ( !EDD_SEGMENT_SUPPORTED_WP_VERSION ) {
			printf( '<div class="error"><p><strong>EDD Segment</strong> requires WordPress %s or higher. Please upgrade WordPress and activate the EDD Segment Plugin again.</p></div>', EDD_SEGMENT_SUPPORTED_WP_VERSION );
		}
		if ( !EDD_SEGMENT_SUPPORTED_PHP_VERSION ) {
			printf( '<div class="error"><p><strong>EDD Segment</strong> requires PHP version %s or higher to be installed on your server. Talk to your web host about using a secure version of PHP.</p></div>', EDD_SEGMENT_SUPPORTED_PHP_VERSION );
		}
	}
}