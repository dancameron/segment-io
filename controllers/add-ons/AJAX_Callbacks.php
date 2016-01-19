<?php 

/**
 * AJAX Callbacks Controller
 *
 * @package EDD_Segment
 * @subpackage EDD_Segment AJAX
 */
class EDD_Segment_AJAX_Callbacks extends EDD_Segment_Controller {
	
	public static function init() {

		// AJAX action to send is - sgmnt_reg or sgmnt_demo
		add_action( 'wp_ajax_sgmnt_reg',  array( __CLASS__, 'maybe_a_registration' ), 10, 0 );
		add_action( 'wp_ajax_nopriv_sgmnt_reg',  array( __CLASS__, 'maybe_a_registration' ), 10, 0 );
		add_action( 'wp_ajax_sgmnt_demo',  array( __CLASS__, 'maybe_a_registration' ), 10, 0 );
		add_action( 'wp_ajax_nopriv_sgmnt_demo',  array( __CLASS__, 'maybe_a_registration' ), 10, 0 );

		// AJAX action to send is - sgmnt_free_license
		// This returns a license key that can be filtered, since I don't know what license you want to return :) 
		add_action( 'wp_ajax_sgmnt_free_license',  array( __CLASS__, 'maybe_free_license_registration' ), 10, 0 );
		add_action( 'wp_ajax_nopriv_sgmnt_free_license',  array( __CLASS__, 'maybe_free_license_registration' ), 10, 0 );
	}
	
	/**
	 * Used from an external API call to wp admin ajax to register a user 
	 * and create a new segment identity.
	 * 
	 */
	public static function maybe_a_registration() {
		if ( !isset( $_REQUEST['uid'] ) ) {
			self::ajax_fail( 'No uid submitted' );
		}
		$email = $_REQUEST['uid'];
		if ( !is_email( $email ) ) {
			self::ajax_fail( 'uid not valid' );
		}

		$user_id = EDD_Segment_Identity::get_uid( $email );
		
		// Send identity
		$traits = array(
				'name' => ( isset( $_REQUEST['name'] ) ) ? $_REQUEST['name'] : '',
				'email' => $email,
				);
		do_action( 'edd_segment_identify', $user_id, $traits );

		// Send event
		$props = array(
				'name' => ( isset( $_REQUEST['item_name'] ) ) ? $_REQUEST['item_name'] : '',
				'time' => time(),
				'email' => $email,
			);
		do_action( 'edd_segment_track', $user_id, 'Demo Product', $props );

		// Send the user_id so this offsite request can set a cookie.
		$response = array(
				'uid' => $user_id,
			);
		header( 'Content-type: application/json' );
		echo json_encode( $response );
		exit();
	}
	
	/**
	 * Used from an external API call to wp admin ajax to register a user 
	 * and create a new segment identity.
	 * 
	 */
	public static function maybe_free_license_registration() {
		if ( !isset( $_REQUEST['uid'] ) ) {
			self::ajax_fail( 'No uid submitted' );
		}
		$email = $_REQUEST['uid'];
		if ( !is_email( $email ) ) {
			self::ajax_fail( 'uid not valid' );
		}

		$user_id = EDD_Segment_Identity::get_uid( $email );
		
		// Send identity
		$traits = array(
				'name' => ( isset( $_REQUEST['name'] ) ) ? $_REQUEST['name'] : '',
				'email' => $email,
				'website' => ( isset( $_REQUEST['url'] ) ) ? $_REQUEST['url'] : '',
				);
		do_action( 'edd_segment_identify', $user_id, $traits );

		// Send event
		$props = array(
				'name' => ( isset( $_REQUEST['item_name'] ) ) ? $_REQUEST['item_name'] : '',
				'url' => ( isset( $_REQUEST['url'] ) ) ? $_REQUEST['url'] : '',
				'time' => time(),
				'email' => $email,
			);
		do_action( 'edd_segment_track', $user_id, 'Free Product Registration', $props );

		// The response sends the user's license key, 
		// which at this moment is just a random string
		// that isn't yet tied to the user.
		$response = array(
				'license_key' => wp_generate_password( 40, FALSE ),
				'uid' => $user_id,
			);
		header( 'Content-type: application/json' );
		echo json_encode( $response );
		exit();
	}
}