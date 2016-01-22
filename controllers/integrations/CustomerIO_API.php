<?php

/**
 * EDD Customer.io API Controller
 *
 * @package EDD_Customer.io
 * @subpackage EDD_Customer.io API
 */
class EDD_CustomerIO_Tracker extends EDD_Segment_Controller {
	private static $site_id;
	private static $api_key;

	public static function init() {
		self::$site_id = EDD_Segment_Settings::get_customerio_site_id();
		self::$api_key = EDD_Segment_Settings::get_customerio_api();

		// Main Tracker
		add_action( 'wp_head', array( __CLASS__, 'add_tracker_code' ) );

		// Event tracker script
		add_action( 'cio_js_track', array( __CLASS__, 'js_track' ), 0, 2 );

		// Action hooks for add-ons, e.g. EDD_Hooks
		add_action( 'edd_segment_identify',  array( __CLASS__, 'identify' ), 10, 2 );
		add_action( 'edd_segment_track',  array( __CLASS__, 'track' ), 10, 3 );

		add_action( 'edd_segment_uid_stored',  array( __CLASS__, 'maybe_store_uid' ) );
	}

	public static function maybe_store_uid( $uid ) {
		if ( $uid ) {
			setrawcookie( '_cioid', $uid, time() + ( 60 * 60 * 24 * 3 ), COOKIEPATH, COOKIE_DOMAIN );
		}
	}

	//////////////////
	// JS Tracking //
	//////////////////

	public static function add_tracker_code() {
		?>
		<script type="text/javascript">
			var _cio = _cio || [];
			(function() {
				var a,b,c;a=function(f){return function(){_cio.push([f].
				concat(Array.prototype.slice.call(arguments,0)))}};b=["load","identify",
				"sidentify","track","page"];for(c=0;c<b.length;c++){_cio[b[c]]=a(b[c])};
				var t = document.createElement('script'),
				  s = document.getElementsByTagName('script')[0];
				t.async = true;
				t.id    = 'cio-tracker';
				t.setAttribute('data-site-id', '<?php echo self::$site_id ?>' );
				t.src = 'https://assets.customer.io/assets/track.js';
				s.parentNode.insertBefore(t, s);
			})();
		</script>
		
		<?php if ( is_user_logged_in() ) :  // show for logged in users only. Other visitors can be identified via other methods.
			$uid = EDD_Segment_Identity::get_uid_from_user_id( get_current_user_id() );
			$user = get_user_by( 'id', get_current_user_id() );
			$first_name = $user->first_name;
			$email = $user->user_email;
			$created = strtotime( $user->user_registered ); ?>
			<script type="text/javascript">
				_cio.identify({
					id: '<?php echo $uid ?>',
					email: '<?php echo $email ?>',
					created_at: <?php echo $created ?>,
					first_name: '<?php echo $first_name ?>'
				});
	  		</script>
		<?php elseif ( EDD_Segment_Identity::has_uid() ) :
			$uid = EDD_Segment_Identity::get_current_visitor_uid();
			?>
			<script type="text/javascript">
				_cio.identify({
					id: '<?php echo $uid ?>'
				});
	  		</script>
		<?php endif ?>

		<?php
	}

	////////////////////
	// Site Tracking //
	////////////////////

	public static function js_track( $event = '', $props = array() ) {
		if ( ! empty( $props ) ) {
			echo '<script type="text/javascript">_cio.track("'.$event.'", '.json_encode( $props ).');</script>';
		} else {
			echo '<script type="text/javascript">_cio.track("'.$event.'");</script>';
		}
	}

	////////////
	// Hooks //
	////////////

	/**
	 * Send track data to customer.io
	 * used with action edd_segment_identify
	 */
	public static function identify( $user_id = 0, $traits = array() ) {
		if ( ! $user_id && is_user_logged_in() ) {
			$user_id = get_current_user_id();
		}

		// A user is necessary
		if ( ! $user_id ) {
			return;
		}

		// the email from traits should be used by default
		$email = ( isset( $traits['email'] ) && is_email( $traits['email'] ) ) ? $traits['email'] : false ;

		// Attempt to get email from user_id
		if ( ! $email ) {
			$user = get_userdata( $user_id );
			// fill in any traits not passed to the method
			if ( is_a( $user, 'WP_User' ) ) {
				$email = $user->user_email;
			} else {
				return false;
			}
		}

		$data = array(
			'email' => $email,
			'time' => time(),
			'created_at' => time(),
		);

		self::remote_put( $user_id, $data, false );

	}

	/**
	 * Send track data to customer.io
	 * used with action edd_segment_track
	 */
	public static function track( $user_id = 0, $event = '', $props = array() ) {
		if ( ! $user_id && is_user_logged_in() ) {
			$user_id = get_current_user_id();
		}

		// A user is necessary
		if ( ! $user_id ) {
			return;
		}

		$data = array(
			'name' => $event,
			'data' => $props,
			'time' => time(),
		);
		self::remote_put( $user_id, $data );

	}

	/////////////
	// Remote //
	/////////////


	public static function customer_endpoint( $user_id ) {
		return 'https://track.customer.io/api/v1/customers/' . $user_id;
	}

	public static function customer_event_endpoint( $user_id ) {
		return 'https://track.customer.io/api/v1/customers/' . $user_id . '/events';
	}

	/**
	 * Remote push data to customer.io
	 * @param  integer $user_id  User ID that customer.io should know about.
	 * @param  array   $data     data array relevant to event
	 * @param  boolean $is_event If set to false than this is a new customer reg.
	 * @return
	 */
	public static function remote_put( $user_id = 0, $data = array(), $is_event = true ) {
		// Get relevant endpoint
		$endpoint = ( $is_event ) ? self::customer_event_endpoint( $user_id ) : self::customer_endpoint( $user_id );
		$method = ( $is_event ) ? 'post' : 'put';
		// remote put
		$request = wp_remote_post( $endpoint, array(
			'timeout' => 15,
			'sslverify' => false,
			'method' => $method,
			'body' => $data,
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( self::$site_id . ':' . self::$api_key ),
				),
		) );
		if ( is_wp_error( $request ) || 200 !== (int) wp_remote_retrieve_response_code( $request ) ) {
			$method = ( $is_event ) ? 'POST' : 'PUT';
			$session = curl_init();
			curl_setopt( $session, CURLOPT_URL, $endpoint );
			curl_setopt( $session, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
			curl_setopt( $session, CURLOPT_HTTPGET, 1 );
			curl_setopt( $session, CURLOPT_HEADER, false );
			curl_setopt( $session, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $session, CURLOPT_CUSTOMREQUEST, $method );
			curl_setopt( $session, CURLOPT_VERBOSE, 1 );
			curl_setopt( $session, CURLOPT_POSTFIELDS, http_build_query( $data ) );
			curl_setopt( $session, CURLOPT_USERPWD, self::$site_id . ':' . self::$api_key );

			$response = curl_exec( $session );
			curl_close( $session );
			return $response;
		}

		$response = wp_remote_retrieve_body( $request );
		return $response;
	}
}
