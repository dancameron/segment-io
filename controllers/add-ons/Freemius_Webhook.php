<?php

/**
 * AJAX Callbacks Controller for Freemius Webhooks
 *
 * @package EDD_Segment
 * @subpackage EDD_Segment_Freemius_Webhook
 */
class EDD_Segment_Freemius_Webhook extends EDD_Segment_Controller {

	public static function init() {

		// AJAX action to send is - sgmnt_reg or sgmnt_demo
		add_action( 'wp_ajax_freemius_webhook',  array( __CLASS__, 'maybe_a_freemius_webhook' ), 10, 0 );
		add_action( 'wp_ajax_nopriv_freemius_webhook',  array( __CLASS__, 'maybe_a_freemius_webhook' ), 10, 0 );
	}

	/**
	 * Used from an external API call to wp admin ajax to handle
	 * freemius installations and uninstallations
	 *
	 */
	public static function maybe_a_freemius_webhook() {

		$input = @file_get_contents( 'php://input' );

		$event_json = json_decode( $input );

		if ( ! isset( $event_json->id ) ) {
			self::ajax_fail( 'Hey man! This be no webhook.' );
			exit();
		}

		require_once EDD_SEGMENT_PATH.'/controllers/add-ons/freemius-sdk/Freemius.php';
		$fs = new Freemius_Api(
			'sprout-invoices',
			'234',
			'pk_22ac32f2f35fd0e09e656f4671a0e',
			EDD_SEG_IO_FREEMIUS_PRIVATE_KEY
		);

		$fs_event = $fs->Api( "/events/{$event_json->id}.json" );

		$props = array(
			'email' => $fs_event->user->email,
			'name' => $fs_event->user->first . ' ' . $fs_event->user->last,
			);

		switch ( $fs_event->type ) {
			case 'install.installed':

				self::new_install( $props );

				break;
			case 'install.uninstalled':
				/*/
				define( 'FS__UNINSTALL_REASON_NO_LONGER_NEEDED', 1 );
				define( 'FS__UNINSTALL_REASON_FOUND_BETTER_PLUGIN', 2 );
				define( 'FS__UNINSTALL_REASON_USED_FOR_SHORT_PERIOD', 3 );
				define( 'FS__UNINSTALL_REASON_BROKE_WEBSITE', 4 );
				define( 'FS__UNINSTALL_REASON_STOPPED_WORKING', 5 );
				define( 'FS__UNINSTALL_REASON_CANT_CONTINUE_PAYING', 6 );
				define( 'FS__UNINSTALL_REASON_OTHER', 7 );
				define( 'FS__UNINSTALL_REASON_DID_NOT_WORK_ANONYMOUS', 8 );
				define( 'FS__UNINSTALL_REASON_DONT_LIKE_INFO_SHARE', 9 );
				define( 'FS__UNINSTALL_REASON_UNCLEAR_HOW_WORKS', 10 );
				define( 'FS__UNINSTALL_REASON_MISSING_FEATURE', 11 );
				define( 'FS__UNINSTALL_REASON_DID_NOT_WORK', 12 );
				define( 'FS__UNINSTALL_REASON_EXPECTED_SOMETHING_ELSE', 13 );
				define( 'FS__UNINSTALL_REASON_EXPECTED_TO_WORK_DIFFERENTLY', 14 );
				/**/

				$fs_uninstall = $fs->Api( "/installs/{$event_json->install->id}/uninstall.json" );

				$props['uninstall_code'] = $fs_uninstall->reason_id;
				switch ( $fs_uninstall->reason_id ) {
					case 1:
						$props['uninstall_reason'] = 'No longer needed';
						break;
					case 2:
						$props['uninstall_reason'] = 'Found alternative';
						break;
					case 3:
						$props['uninstall_reason'] = 'Used for short period';
						break;
					case 4:
						$props['uninstall_reason'] = 'Broke website';
						break;
					case 5:
						$props['uninstall_reason'] = 'Stopped working';
						break;
					case 10:
						$props['uninstall_reason'] = 'Unclear how it works';
						break;
					case 7:
					case 9:
						$props['uninstall_reason'] = 'Other';
						break;
					default:
						$props['uninstall_reason'] = 'Unknown';
						break;
				}

				self::new_uninstall( $props );

				break;
		}

		http_response_code( 200 );
	}

	public static function new_install( $props = array() ) {
		$user_id = EDD_Segment_Identity::get_uid( $props['email'] );

		// Send identity
		$traits = array(
				'name' => ( isset( $props['name'] ) ) ? $props['name'] : '',
				'email' => $email,
				);
		do_action( 'edd_segment_identify', $user_id, $traits );

		// Send event
		$event_props = array(
				'name' => 'sprout-invoices',
				'time' => time(),
				'email' => $email,
			);
		do_action( 'edd_segment_track', $user_id, 'Free Install', $event_props );
	}

	public static function new_uninstall( $props = array() ) {

		$user_id = EDD_Segment_Identity::get_uid( $props['email'] );

		// Send identity
		$traits = array(
				'name' => ( isset( $props['name'] ) ) ? $props['name'] : '',
				'email' => $email,
				);
		do_action( 'edd_segment_identify', $user_id, $traits );

		// Send event
		$event_props = array(
				'name' => 'sprout-invoices',
				'time' => time(),
				'email' => $email,
				'uninstall_code' => $props['uninstall_code'],
				'uninstall_reason' => $props['uninstall_reason'],
			);
		do_action( 'edd_segment_track', $user_id, 'Free Install', $event_props );
	}
}
