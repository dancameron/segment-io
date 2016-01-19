<?php 

/**
 * EDD Segment API Controller
 *
 * @package EDD_Segment
 * @subpackage EDD_Segment API
 */
class EDD_Segment_Tracker extends EDD_Segment_Controller {
	private static $write_key;

	public static function init() {
		self::$write_key = EDD_Segment_Settings::get_write_key();
		if ( self::$write_key ) {
			add_action( 'edd_segment_identify',  array( __CLASS__, 'identify' ), 10, 2 );
			add_action( 'edd_segment_track',  array( __CLASS__, 'track' ), 10, 3 );
		}
	}

	public static function load_segment_api() {
		if ( !class_exists('Analytics' ) ) {
			require_once EDD_SEGMENT_PATH.'/lib/segment-php/lib/Segment.php';
			class_alias('Segment', 'Analytics');
			Analytics::init( self::$write_key );
		}
	}

	public static function identify( $user_id = 0, $traits = array() ) {
		if ( !$user_id && is_user_logged_in() ) {
			$user_id = get_current_user_id();
		}

		// A user is necessary
		if ( !$user_id ) {
			return;
		}

		$user = get_userdata( $user_id );
		$user_traits = array();
		// fill in any traits not passed to the method
		if ( is_a( $user, 'WP_User' ) ) {
			$user_traits = array(
				'name' => $user->first_name .  ' ' . $user->last_name,
				'email' => $user->user_email
				);
		}
		// merge with existing
		$traits = wp_parse_args( $traits, $user_traits );

		// traits are necessary
		if ( empty( $traits ) ) {
			return;
		}

		self::load_segment_api();
		Analytics::identify( array(
			'userId' => $user_id,
			'traits' => $traits
			) );
	}

	public static function track( $user_id = 0, $event = '', $props = array() ) {
		// an event is required
		if ( $event == '' ) {
			return;
		}

		if ( !$user_id && is_user_logged_in() ) {
			$user_id = get_current_user_id();
		}

		// A user is necessary
		if ( !$user_id ) {
			return;
		}

		self::load_segment_api();
		Analytics::track( array(
			'userId' => $user_id,
			'event' => $event,
			'properties' => $props
			) );
	}

}