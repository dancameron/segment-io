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
			'plugin',
			EDD_SEG_IO_FREEMIUS_PLUGIN_ID,
			EDD_SEG_IO_FREEMIUS_PUBLIC_KEY,
			EDD_SEG_IO_FREEMIUS_PRIVATE_KEY
		);
		$fs_event = $fs->Api( "/events/{$event_json->id}.json" );

		$props = array(
			'email' => $fs_event->objects->user->email,
			'name' => $fs_event->objects->user->first . ' ' . $fs_event->objects->user->last,
			'site_url' => $fs_event->objects->install->url,
			);

		switch ( $fs_event->type ) {
			case 'install.installed':

				self::new_install( $props );

				break;
			case 'install.uninstalled':

				$fs_uninstall = $fs->Api( "/installs/{$fs_event->install_id}/uninstall.json" );
				$props['uninstall_code'] = $fs_uninstall->reason_id;
				$props['uninstall_reason'] = $fs_uninstall->reason;
				$props['uninstall_reason_info'] = $fs_uninstall->reason_info;

				self::new_uninstall( $props );

				break;
		}

		http_response_code( 200 );
		exit();
	}

	public static function new_install( $props = array() ) {
		$user_id = EDD_Segment_Identity::get_uid( $props['email'] );

		// Send identity
		$traits = array(
				'name' => ( isset( $props['name'] ) ) ? $props['name'] : '',
				'email' => $props['email'],
				);
		do_action( 'edd_segment_identify', $user_id, $traits );

		// Send event
		$event_props = array(
				'name' => 'sprout-invoices',
				'time' => time(),
				'email' => $props['email'],
				'site_url' => $props['site_url'],
			);
		do_action( 'edd_segment_track', $user_id, 'Free Install', $event_props );
	}

	public static function new_uninstall( $props = array() ) {

		$user_id = EDD_Segment_Identity::get_uid( $props['email'] );

		// Send identity
		$traits = array(
				'name' => ( isset( $props['name'] ) ) ? $props['name'] : '',
				'email' => $props['email'],
				);
		do_action( 'edd_segment_identify', $user_id, $traits );

		// Send event
		$event_props = array(
				'name' => 'sprout-invoices',
				'time' => time(),
				'email' => $props['email'],
				'uninstall_code' => $props['uninstall_code'],
				'uninstall_reason' => $props['uninstall_reason'],
				'site_url' => $props['site_url'],
			);
		do_action( 'edd_segment_track', $user_id, 'Free Uninstall', $event_props );
	}
}
