<?php 


/**
 * EDD Segment API Controller
 *
 * @todo This should all be abstracted into their own integrations.
 *  
 * @package EDD_Segment
 * @subpackage HSD Admin Settings
 */
class EDD_Segment_Settings extends EDD_Segment_Controller {
	const SEGMENT_WRITE_KEY = 'edd_segment_addon_api_key';
	const CUSTOMERIO_API_KEY = 'edd_segment_addon_customer_api';
	const CUSTOMERIO_SITE_ID = 'edd_segment_addon_customer_site_id';
	protected static $write_key;
	protected static $customerio_api;
	protected static $customerio_site_id;

	public static function init() {
		// Store options
		self::$write_key = get_option( self::SEGMENT_WRITE_KEY, '' );
		self::$customerio_api = get_option( self::CUSTOMERIO_API_KEY, '' );
		self::$customerio_site_id = get_option( self::CUSTOMERIO_SITE_ID, '' );
		
		// Register Settings
		self::register_settings();

	}

	public static function get_write_key() {
		return self::$write_key;
	}

	public static function get_customerio_api() {
		return self::$customerio_api;
	}

	public static function get_customerio_site_id() {
		return self::$customerio_site_id;
	}


	//////////////
	// Settings //
	//////////////

	/**
	 * Hooked on init add the settings page and options.
	 *
	 */
	public static function register_settings() {
		// Option page
		$args = array(
			'slug' => self::SETTINGS_PAGE,
			'title' => 'EDD Segment Settings',
			'menu_title' => 'EDD Segment',
			'tab_title' => 'Getting Started',
			'weight' => 20,
			'reset' => FALSE, 
			'section' => self::SETTINGS_PAGE
			);
		do_action( 'sprout_settings_page', $args );

		// Settings
		$settings = array(
			'edd_segment_site_settings' => array(
				'title' => 'EDD Segment Setup',
				'weight' => 10,
				'callback' => array( __CLASS__, 'display_general_section' ),
				'settings' => array(
					self::SEGMENT_WRITE_KEY => array(
						'label' => self::__( 'Segment Write Key' ),
						'option' => array(
							'description' => self::__('Found under "Project Settings" within the "API KEYS" tab.'),
							'type' => 'text',
							'default' => self::$write_key
							)
						),
					self::CUSTOMERIO_SITE_ID => array(
						'label' => self::__( 'Customer.io Site ID' ),
						'option' => array(
							'description' => self::__('Found under "Integration".'),
							'type' => 'text',
							'default' => self::$customerio_site_id
							)
						),
					self::CUSTOMERIO_API_KEY => array(
						'label' => self::__( 'Customer.io API Key' ),
						'option' => array(
							'description' => self::__('Found under "Integration". Event information will be sent directly to customer.io.'),
							'type' => 'text',
							'default' => self::$customerio_api
							)
						)
				)
			)
		);
		do_action( 'sprout_settings', $settings, self::SETTINGS_PAGE );
	}

	//////////////////////
	// General Settings //
	//////////////////////

	public static function display_general_section() {
		echo '<p>'.self::_e( 'EDD Segment supports two integrations at the moment, Segment and customer.io. Segment can be integrated with customer.io, so you may want to just use that integration but you have the choice to use both or either/or.' ).'</p>';
	}

}